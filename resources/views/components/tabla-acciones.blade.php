@props(['editRoute', 'deleteRoute', 'deleteMsg', 'accederRoute' => null, 'accederMsg' => null])

<div class="d-inline-flex">
    <div class="dropdown">
        <a href="#" class="text-body" data-bs-toggle="dropdown">
            <i class="ph-list"></i>
        </a>

        <div class="dropdown-menu dropdown-menu-end">

            @if($accederRoute)
            <a href="{{ $accederRoute }}" data-msg="¿Acceder como {{ $accederMsg }}?" onclick="event.preventDefault(); accederComoUsuario(this)" class="dropdown-item">
                <i class="ph ph-sign-in me-2"></i>
                Acceder
            </a>
            @endif

            <a href="{{ $editRoute }}" class="dropdown-item">
                <i class="ph ph-pencil-simple me-2"></i>
                Editar
            </a>

            <a href="{{ $deleteRoute }}" data-msg="{{ $deleteMsg }}" onclick="event.preventDefault(); eliminar(this)" class="dropdown-item">
                <i class="ph ph-trash me-2"></i>
                Eliminar
            </a>

        </div>
    </div>
</div>
