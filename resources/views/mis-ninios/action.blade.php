<x-tabla-acciones
    :edit-route="route('mis-ninios.edit', $n->id)"
    :delete-route="route('mis-ninios.destroy', $n->id)"
    :delete-msg="$n->nombres_completos"
/>
