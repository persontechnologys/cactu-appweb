<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdatePerfilRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class PerfilController extends Controller
{
    /**
     * Constructor para aplicar middleware de autenticación
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Mostrar la vista del perfil del usuario
     */
    public function perfil()
    {
        return view('perfil.index');
    }
    /**
     * Guardar los cambios del perfil del usuario
     */
    public function guardarPerfil(UpdatePerfilRequest $request)
    {
        $user = Auth::user();

        $user->update($request->validated());

        return redirect()->route('perfil')->with('success', 'Perfil actualizado correctamente.');
    }
    /**
     * Cambiar la contraseña del usuario
     */
    public function cambiarPassword(UpdatePasswordRequest $request)
    {
        $user = Auth::user();

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        // Opcional: Cerrar todas las sesiones excepto la actual
        // Auth::logoutOtherDevices($request->new_password);

        return redirect()->route('perfil')->with('success', 'Contraseña actualizada correctamente.');
    }
    /**
     * Alternar el estado del menú responsivo
     */
    public function menuResponsivo(Request $request)
    {
        $user = Auth::user();
        $user->menu_responsivo = !$user->menu_responsivo;
        $user->save();
        return response()->json(['status' => 'success', 'menu_responsivo' => $user->menu_responsivo]);
    }
}
