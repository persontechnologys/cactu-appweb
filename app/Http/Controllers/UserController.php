<?php

namespace App\Http\Controllers;

use App\DataTables\UserDataTable;
use App\Http\Requests\User\StoreRq;
use App\Http\Requests\User\UpdateRq;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(UserDataTable $dataTable)
    {
        $this->authorize('viewAny', User::class);
        return $dataTable->render('usuarios.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', User::class);
        $data = array('roles' => Role::whereNotIn('name',[config('app.ROLE_ADMIN')])->get() );
        return view('usuarios.create',$data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRq $request)
    {
        $this->authorize('create', User::class);

        // un usuario nunca puede autoasignarse ni asignar a otros el rol ADMINISTRADOR
        // desde este formulario (ver SEG-001); mismo criterio ya aplicado en create().
        $request->validate([
            'roles.*' => Rule::in(Role::whereNotIn('name',[config('app.ROLE_ADMIN')])->pluck('id')),
        ]);

        try {
            DB::beginTransaction();
            $user=User::create($request->except(['password']));

            if($request->password){
                $user->password=Hash::make($request->password);
                $user->save();
            }

            $user->assignRole($request->roles);

            DB::commit();
            Session::flash('success','Usuario guardado.');
            return redirect()->route('usuarios.index');
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Error al guardar usuario: '.$th->getMessage());
            return back()->withInput()->with('danger','Ocurrio un error, vuelva intentar o consulte con administrador.');
        }
        
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
        $user=User::findOrFail($id);
        $this->authorize('update', $user);
        $data = array(
            'user' => $user,
            'roles'=>Role::get()
        );

        return view('usuarios.edit',$data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRq $request, string $id)
    {
        $user=User::findOrFail($id);
        $this->authorize('update', $user);

        // un usuario existente nunca puede ser ascendido a ADMINISTRADOR desde
        // este formulario (ver SEG-001); mismo criterio ya aplicado en create().
        $request->validate([
            'roles.*' => Rule::in(Role::whereNotIn('name',[config('app.ROLE_ADMIN')])->pluck('id')),
        ]);

        try {
            DB::beginTransaction();
            $user->update($request->except(['password']));
            if($request->password){
                $user->password=Hash::make($request->password);
                $user->save();
            }

            if(!$user->hasRole(config('app.ROLE_ADMIN'))){
                $user->syncRoles($request->roles);
            }

            DB::commit();
            return redirect()->route('usuarios.index')->with('success','Usuario actualizado.');
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Error al actualizar usuario: '.$th->getMessage());
            return back()->withInput()->with('danger','Ocurrio un error, vuelva intentar o consulte con administrador.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = User::findOrFail($id);
        $this->authorize('delete', $user);
        try {
            $user->delete();
            Session::flash('success','Usuario eliminado.!');
        } catch (\Throwable $th) {
            Session::flash('info','Usuario no eliminado.!');
        }
        return redirect()->route('usuarios.index');
    }

   
}
