<?php

namespace App\Http\Controllers;

use App\Models\Comunidad;
use App\Models\Ninio;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;

class MisNiniosController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = array(
            'ninios'=>Auth::user()->ninios
        );
        return view('mis-ninios.index',$data);
        
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $data = array('comunidades'=>Auth::user()->comunidades);
        return view('mis-ninios.create',$data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        
        $request->validate([
            
            'numero_child'=>'required|unique:ninios,numero_child',
            'nombres_completos'=>'required|string|max:255',
            'genero'=>'required|in:Male,Female',
            'fecha_nacimiento'=>'required|date',
            'comunidad'=>[
                'required',
                 Rule::in(Auth::user()->comunidades->pluck('id')),
             ]
        ]);
        $request['comunidad_id']=$request->comunidad;
        $request['edad']=Carbon::parse($request->fecha_nacimiento)->age;
        Ninio::create($request->all());
        Session::flash('success','Niño ingresado.!');
        return redirect()->route('mis-ninios.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {

        $ninio=Ninio::findOrFail($id);
        $this->authorize('update',$ninio);
        $data = array(
            'comunidades'=>Auth::user()->comunidades,
            'ninio'=>$ninio
        );
        return view('mis-ninios.edit',$data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $ninio=Ninio::findOrFail($id);
        $this->authorize('update',$ninio);
        $request->validate([
            'numero_child'=>'required|unique:ninios,numero_child,'.$ninio->id,
            'nombres_completos'=>'required|string|max:255',
            'genero'=>'required|in:Male,Female',
            'fecha_nacimiento'=>'required|date',
            'comunidad'=>[
                'required',
                 Rule::in(Auth::user()->comunidades->pluck('id')),
             ]
        ]);
        $request['comunidad_id']=$request->comunidad;
        $request['edad']=Carbon::parse($request->fecha_nacimiento)->age;

        $ninio->update($request->all());
        Session::flash('success','Niño actualizado.!');
        return redirect()->route('mis-ninios.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $ninio=Ninio::findOrFail($id);
        $this->authorize('delete',$ninio);
        try {
            if (in_array($ninio->comunidad_id, Auth::user()->comunidades->pluck('id')->toArray())) {
                $ninio->delete();
                Session::flash('success','Niño eliminado.!');
            }else{
                Session::flash('danger','Error al eliminar niño, no pertenece a su comunidad');
            }
           
        } catch (\Throwable $th) {
            Log::error('Error al eliminar niño: '.$th->getMessage());
            Session::flash('info','Niño no eliminado.!');
        }
        return redirect()->route('mis-ninios.index');
    }

    public function cartas($id) {
        $data = array('id'=>$id);
        return view('mis-ninios.cartas.index',$data);
    }

    public function nuevaCarta($tipo,$id) {
        $ninio=Ninio::findOrFail($id);
        $data = array(
            'tipo'=>$tipo,
            'ninio'=>$ninio
        );
        return view('mis-ninios.cartas.nuevo',$data);
    }
}
