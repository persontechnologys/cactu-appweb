<x-tabla-acciones
    :edit-route="route('usuarios.edit', $user->id)"
    :delete-route="route('usuarios.destroy', $user->id)"
    :delete-msg="$user->apellidos_nombres"
    :acceder-route="auth()->user()->can('acceder', $user) ? route('usuarios.acceder', $user->id) : null"
    :acceder-msg="$user->name"
/>
