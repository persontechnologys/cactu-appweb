<?php

namespace App\Http\Controllers;

use App\Actions\RegistrarCartaAction;
use App\DataTables\Carta\NinioDataTable;
use App\DataTables\CartaDataTable;
use App\Models\Boleta;
use App\Models\Carta;
use App\Models\Ninio;
use App\Notifications\EnviarNotificacionCartaNueva;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use PDF;
class CartaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(CartaDataTable $dataTable)
    {
        // Obtener datos para los filtros
        $tiposCartas = ['Contestación', 'Agradecimiento', 'Presentación', 'Iniciada'];
        $estados = ['Enviado', 'Respondida'];
        
        // Obtener comunidades según el rol del usuario
        $user = Auth::user();
        if ($user->hasRole('ADMINISTRADOR')) {
            $comunidades = \App\Models\Comunidad::select('id', 'nombre')->orderBy('nombre')->get();
            $gestores = \App\Models\User::select('id', 'name', 'email')->orderBy('name')->get();
        } else {
            $comunidades = $user->comunidades()->select('id', 'nombre')->orderBy('nombre')->get();
            $gestores = collect([$user]); // Solo el usuario actual
        }

        return $dataTable->render('cartas.index', compact(
            'tiposCartas',
            'estados', 
            'comunidades',
            'gestores'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(NinioDataTable $dataTable)
    {
        return $dataTable->render('cartas.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, RegistrarCartaAction $registrarCarta)
    {
        set_time_limit(120);
        $request->validate([
            'ninio'=>[
                'required',
                Rule::exists('ninios','id')->where(function (Builder $query) {
                    if(!Auth::user()->hasRole('ADMINISTRADOR')){
                        return $query->whereIn('comunidad_id', Auth::user()->comunidades->pluck('id'));
                    }else{
                        return $query;
                    }
                }),
            ],
            'tipo'=>'required|in:Contestación,Presentación,Agradecimiento,Iniciada',
            'asunto'=>'required|string|max:120',
            'boleta' => 'required',
            'boleta.*' => 'image|mimes:jpeg,png,jpg|max:2050',
            "carta" => "required_if:tipo,==,Contestación|mimes:pdf",

        ]);

        try {
            $carta = $registrarCarta->registrar(
                ninioId: $request->ninio,
                tipo: $request->tipo,
                asunto: $request->asunto,
                userId: Auth::user()->id,
                boletas: $request->file('boleta'),
                archivoCartaPdf: $request->file('carta'),
            );

            $this->enviarNotificacion($carta);

            Session::flash('success','Carta de '.$carta->tipo.' enviada a '.$carta->ninio->nombres_completos);
            return redirect()->route('cartas.index');
        } catch (\Throwable $th) {
            Log::error('Error al enviar carta: '.$th->getMessage());
            Session::flash('info','Carta no enviado, vuelva a intentar o consulte con administrador.');
            return redirect()->back()->withInput();
        }

    }

    public function enviarNotificacion($carta){

        if($carta->ninio->email || $carta->ninio->fcm_token){
            $carta->ninio->notify(new EnviarNotificacionCartaNueva($carta));
        }
        
    }
    /**
     * Display the specified resource.
     */
    public function show(Carta $carta)
    {
        $this->authorize('view',$carta);

        return view('cartas.show',['carta'=>$carta]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Carta $carta)
    {
        $this->authorize('update',$carta);
        return view('cartas.edit',['carta'=>$carta]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Carta $carta)
    {
        $this->authorize('update',$carta);
        $carta->detalle=$request->detalle;
        $carta->save();
        Session::flash('success','Carta de '.$carta->tipo.' actualizado exitosamente.!');
        return redirect()->route('cartas.show',$carta);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Carta $carta)
    {
        $this->authorize('eliminar',$carta);
        try {
            if($carta->delete()){
                
                if(Storage::exists($carta->archivo_imagen??'archivo_imagen.pngx')){
                    Storage::delete($carta->archivo_imagen);
                }
                if(Storage::exists($carta->archivo_pdf??'archivo_pdf.pngx')){
                    Storage::delete($carta->archivo_pdf);
                }
                
                if($carta->boletas->count()>0){
                    foreach ($carta->boletas as $boleta) {
                        if(Storage::exists($boleta->archivo_imagen??'boleta.pngx')){
                            Storage::delete($boleta->archivo_imagen);
                        }
                    }
                }


            }
            Session::flash('success','Carta eliminado.!');
        } catch (\Throwable $th) {
            Log::error('Error al eliminar carta: '.$th->getMessage());
            Session::flash('info','Carta no eliminado.!');
        }
        return redirect()->route('cartas.index');
    }

    public function verPDF($id,$version=null) {
        
        $carta=Carta::findOrFail($id);
        $title='CARTA DE '.$carta->ninio->nombres_completos.'.pdf';
        $data = array(
            'carta'=>$carta,
            'title'=>$title
        );
        
        if($version && $version=='v2'){
            $pdf = PDF::loadView('cartas.v2.verpdf', $data);
        }else{
            $pdf = PDF::loadView('cartas.verpdf', $data);
        }
        

        

        return $pdf->inline($title );
    }
    
    public function verArchivo($idcarta,$tipo)
    {
        $carta=Carta::findOrFail($idcarta);
        switch ($tipo) {
            case 'archivo_imagen_ninio':
                return Storage::get($carta->archivo_imagen_ninio);        
                break;
            case 'archivo_pdf':
                return Storage::response($carta->archivo_pdf);        
                break;
            case 'archivo_imagen':
                return Storage::get($carta->archivo_imagen);        
                break;
            case 'archivo_familia_ninio':
                return Storage::get($carta->archivo_familia_ninio);        
                break;
            default:
                return '';
                break;
        }
    }

    public function verBoletaArchivoImagen($idBoleta) {
        $boleta=Boleta::findOrFail($idBoleta);
        return Storage::get($boleta->archivo_imagen);
    }

    public function documentos($id) {
        $carta=Carta::findOrFail($id);
        return view('cartas.documentos',['carta'=>$carta]);
    }

    public function descargarPdf($id) {
        $carta=Carta::findOrFail($id);
        $title='CARTA DE '.$carta->ninio->nombres_completos.'.pdf';
        $data = array(
            'carta'=>$carta,
            'title'=>$title
        );
        
        $pdf = PDF::loadView('cartas.verpdf', $data) ->setOption('margin-top', 5) ->setOption('margin-bottom', 1);
        return $pdf->download($title);
    }

}
