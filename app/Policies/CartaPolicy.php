<?php

namespace App\Policies;

use App\Models\Carta;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CartaPolicy
{
    
    // controla si se muestra el link "Ver"/"Descargar PDF" en la vista de
    // cartas (no es autorización de backend, eso es view()/update() — ARQ-004)
    public function mostrarEnlaceVer(User $user, Carta $carta): bool
    {
        if($carta->estado==='Respondida'){
            if($user->hasRole('ADMINISTRADOR')){
                return true;
            }else{
                if($user->id==$carta->user_id){
                    return true;
                }else{
                    return false;
                }
            }
        }
        return false;
    }
    public function eliminar(User $user, Carta $carta): bool
    {

        if($user->hasRole('ADMINISTRADOR')){
            return true;
        }else{
            if($user->id==$carta->user_id){
                return true;
            }else{
                return false;
            }
        }

        return false;
    }

    // ver/editar el detalle de la carta (CartaController::show/edit/update):
    // mismo criterio que 'eliminar' (dueño o ADMINISTRADOR). Ver SEG-007.
    public function view(User $user, Carta $carta): bool
    {
        if($user->hasRole('ADMINISTRADOR')){
            return true;
        }
        return $user->id==$carta->user_id;
    }

    public function update(User $user, Carta $carta): bool
    {
        if($user->hasRole('ADMINISTRADOR')){
            return true;
        }
        return $user->id==$carta->user_id;
    }


    // responder la carta por parte del niño
    public function responderCartaXninio(?User $user, Carta $carta): bool
    {
        if($carta->estado==='Respondida'){
            return false;
        }
        return true;
    }


    
}
