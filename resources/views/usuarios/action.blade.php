<x-tabla-acciones
    :edit-route="route('usuarios.edit', $user->id)"
    :delete-route="route('usuarios.destroy', $user->id)"
    :delete-msg="$user->apellidos_nombres"
/>
