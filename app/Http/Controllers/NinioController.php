<?php

namespace App\Http\Controllers;

use App\DataTables\NinioDataTable;
use App\Models\Comunidad;
use App\Models\Ninio;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Rap2hpoutre\FastExcel\FastExcel;

class NinioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(NinioDataTable $dataTable)
    {
        $this->authorize('viewAny', Ninio::class);

        return $dataTable->render('ninios.index', [
            'comunidades' => Comunidad::select('id', 'nombre')->orderBy('nombre')->get(),
            'estados' => ['ACTIVO', 'INACTIVO'],
            'generos' => ['Male', 'Female'],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', Ninio::class);
        $data = array('comunidades'=>Comunidad::get());
        return view('ninios.create',$data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Ninio::class);

        $request->validate([
            'numero_child'=>'required|unique:ninios,numero_child',
            'nombres_completos'=>'required|string|max:255',
            'genero'=>'required|in:Male,Female',
            'fecha_nacimiento'=>'required|date',
            'comunidad'=>'required|exists:comunidads,id'
        ]);
        $request['comunidad_id']=$request->comunidad;
        $request['edad']=Carbon::parse($request->fecha_nacimiento)->age;

        Ninio::create($request->all());
        Session::flash('success','Niño ingresado.!');
        return redirect()->route('ninios.index');

    }

    /**
     * Display the specified resource.
     */
    public function show(Ninio $ninio)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Ninio $ninio)
    {
        $this->authorize('update', $ninio);

        $data = array(
            'comunidades'=>Comunidad::get(),
            'ninio'=>$ninio
        );
        return view('ninios.edit',$data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Ninio $ninio)
    {
        $this->authorize('update', $ninio);

        $request->validate([
            'numero_child'=>'required|unique:ninios,numero_child,'.$ninio->id,
            'nombres_completos'=>'required|string|max:255',
            'genero'=>'required|in:Male,Female',
            'fecha_nacimiento'=>'required|date',
            'comunidad'=>'required|exists:comunidads,id'
        ]);
        $request['comunidad_id']=$request->comunidad;
        $request['edad']=Carbon::parse($request->fecha_nacimiento)->age;

        $ninio->update($request->all());
        Session::flash('success','Niño actualizado.!');
        return redirect()->route('ninios.index');

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Ninio $ninio)
    {
        $this->authorize('delete', $ninio);
        try {
            $ninio->delete();
            Session::flash('success','Niño eliminado.!');
        } catch (\Throwable $th) {
            Session::flash('info','Niño no eliminado.!');
        }
        return redirect()->route('ninios.index');
    }
    
    public function importar()  {
        $this->authorize('create', Ninio::class);

        return view('ninios.importar');

    }

    public function subirImportacion(Request $request){
        $this->authorize('create', Ninio::class);
        set_time_limit(120);
        $users = (new FastExcel)->import($request->file('foto'), function ($line) {
            $comunidad= Comunidad::firstOrCreate([
                'nombre' => $line['comunidad'],
            ]);

            $product = Ninio::firstOrCreate(
                [ 'numero_child' => $line['numero_child'] ],
                [ 
                    'nombres_completos' => $line['nombres_completos'], 
                    'genero' => $line['genero'], 
                    'fecha_nacimiento' => $line['fecha_nacimiento'], 
                    'edad' => $line['edad'], 
                    'comunidad_id'=>$comunidad->id,
                    'email'=>$line['email']??null,
                    'numero_celular'=>$line['numero_celular']??null
                ]
            );
            return $product;
        });
        return $users;
    }
}
