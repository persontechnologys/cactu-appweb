<x-tabla-acciones
    :edit-route="route('comunidad.edit', $comunidad->id)"
    :delete-route="route('comunidad.destroy', $comunidad->id)"
    :delete-msg="$comunidad->nombre"
/>
