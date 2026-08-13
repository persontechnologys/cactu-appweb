<?php

namespace App\Http\Controllers;

use App\DataTables\ComunidadDataTable;
use App\Models\Comunidad;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class ComunidadController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(ComunidadDataTable $comunidadDataTable)
    {
         return $comunidadDataTable->render('comunidad.index', [
             'gestores' => User::select('id', 'name', 'email')->orderBy('name')->get(),
         ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $data = array('usuarios'=>User::get());
        return view('comunidad.create',$data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre'=>'required|unique:comunidads,nombre',
            'usuario'=>'required|exists:users,id'
        ]);
        $request['user_id']=$request->usuario;
        Comunidad::create($request->all());
        Session::flash('success','Comunidad ingresado.');
        return redirect()->route('comunidad.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Comunidad $comunidad)
    {
        
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Comunidad $comunidad)
    {
        $data = array(
            'comunidad'=>$comunidad,
            'usuarios'=>User::get()
        );
        return view('comunidad.edit',$data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Comunidad $comunidad)
    {
        $request->validate([
            'nombre'=>'required|unique:comunidads,nombre,'.$comunidad->id,
            'usuario'=>'required|exists:users,id'
        ]);
        $request['user_id']=$request->usuario;
        $comunidad->update($request->all());
        Session::flash('success','Comunidad actualizado.');
        return redirect()->route('comunidad.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Comunidad $comunidad)
    {
        try {
            $comunidad->delete();
            Session::flash('success','Comunidad eliminado.');
            
        } catch (\Throwable $th) {
            Session::flash('info','Comunidad no eliminado.');
        }
        return redirect()->route('comunidad.index');
    }
}
