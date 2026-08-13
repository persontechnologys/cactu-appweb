<x-tabla-acciones
    :edit-route="route('ninios.edit', $n->id)"
    :delete-route="route('ninios.destroy', $n->id)"
    :delete-msg="$n->nombres_completos"
/>
