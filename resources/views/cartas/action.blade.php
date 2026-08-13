<div class="d-inline-flex">
    <div class="dropdown">
        <a href="#" class="text-body" data-bs-toggle="dropdown">
            <i class="ph-list"></i>
        </a>

        <div class="dropdown-menu dropdown-menu-end">
          @can('mostrarEnlaceVer', $c)
            <a href="{{ route('cartas.show',$c->id) }}" class="dropdown-item">
                <i class="ph ph-eye me-2"></i>
                Ver
            </a>

            <a href="{{ route('cartas.descargarPdf',$c->id) }}"  class="dropdown-item">
                <i class="ph ph-file-pdf me-2"></i>
                Descargar PDF
            </a> 
            
          @endcan
            
        
        

        <a href="{{ route('cartas.documentos',$c->id) }}"  class="dropdown-item">
            <i class="ph ph-file-search me-2"></i>
            Documentos
        </a> 


        {{-- @can('eliminar', $c) --}}
        <a href="{{ route('cartas.destroy',$c->id) }}" data-msg="Carta de {{ $c->tipo }} de {{ $c->ninio->nombres_completos }}" onclick="event.preventDefault(); eliminar(this)" class="dropdown-item">
            <i class="ph ph-trash me-2"></i>
            Eliminar
        </a>    
        {{-- @endcan --}}
            
            
            
        </div>
    </div>

    {{-- Info de la carta --}}
    <a 
        href="#" 
        class="text-body mx-2 carta-info-popover"
        data-bs-popup="popover" 
        title="<i class='ph-envelope me-1'></i> Información de la Carta" 
        data-bs-html="true" 
        data-bs-trigger="hover focus"
        data-bs-placement="left"
        data-bs-custom-class="carta-popover"
        data-bs-content="
            <div class='mb-2'>
                <div class='d-flex align-items-center mb-1'>
                    <i class='{{ $c->icono }} me-2 text-primary'></i>
                    <strong class='me-1'>Tipo:</strong> 
                    <span class='badge bg-{{ $c->color }}'>{{ $c->tipo }}</span>
                </div>
                
                <div class='d-flex align-items-center mb-1'>
                    <i class='ph-circle-wavy me-2 text-warning'></i>
                    <strong class='me-1'>Estado:</strong>
                    <span class='badge bg-{{ $c->estado_color }}'>
                        {{ $c->estado ?? 'Enviado' }}
                    </span>
                </div>
                
                @if($c->asunto)
                <div class='d-flex align-items-start mb-1'>
                    <i class='ph-chat-text me-2 text-info'></i>
                    <div>
                        <strong>Asunto:</strong><br>
                        <small class='text-muted'>{{ Str::limit($c->asunto, 50) }}</small>
                    </div>
                </div>
                @endif
                
                <div class='d-flex align-items-center mb-1'>
                    <i class='ph-baby me-2 text-success'></i>
                    <div>
                        <strong>Niño:</strong><br>
                        <small>{{ $c->ninio->nombres_completos ?? 'N/A' }}</small>
                    </div>
                </div>
                
                @if($c->comunidad)
                <div class='d-flex align-items-center mb-1'>
                    <i class='ph-buildings me-2 text-secondary'></i>
                    <div>
                        <strong>Comunidad:</strong><br>
                        <small>{{ $c->comunidad->nombre ?? 'N/A' }}</small>
                    </div>
                </div>
                @endif
                
                <div class='d-flex align-items-center mb-1'>
                    <i class='ph-user me-2 text-dark'></i>
                    <div>
                        <strong>Gestor:</strong><br>
                        <small>{{ $c->gestor->name ?? 'N/A' }}</small>
                    </div>
                </div>
                
                <div class='d-flex align-items-center'>
                    <i class='ph-calendar me-2 text-muted'></i>
                    <div>
                        <strong>Fecha:</strong><br>
                        <small>{{ $c->created_at->format('d/m/Y H:i') }}</small>
                    </div>
                </div>
                
                @if($c->archivo_imagen || $c->archivo_pdf || $c->archivo_imagen_ninio)
                <hr class='my-2'>
                <div class='d-flex align-items-center'>
                    <i class='ph-paperclip me-2 text-info'></i>
                    <small>
                        <strong>Archivos adjuntos:</strong>
                        @if($c->archivo_imagen) <span class='badge bg-light text-dark me-1'>Imagen</span> @endif
                        @if($c->archivo_pdf) <span class='badge bg-light text-dark me-1'>PDF</span> @endif
                        @if($c->archivo_imagen_ninio) <span class='badge bg-light text-dark me-1'>Img. Niño</span> @endif
                    </small>
                </div>
                @endif
                
                @if($c->boletas && $c->boletas->count() > 0)
                <hr class='my-2'>
                <div class='d-flex align-items-center'>
                    <i class='ph-receipt me-2 text-warning'></i>
                    <small>
                        <strong>Boletas:</strong>
                        <span class='badge bg-warning text-dark'>{{ $c->boletas->count() }}</span>
                    </small>
                </div>
                @endif
            </div>
        ">
        <i class="ph-info"></i>
    </a>
</div>

{{-- Estilos personalizados para el popover --}}
@push('styles')
<style>
.carta-popover {
    max-width: 350px !important;
    font-size: 0.875rem;
}

.carta-popover .popover-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    font-weight: 600;
    color: #495057;
}

.carta-popover .popover-body {
    padding: 12px;
    line-height: 1.4;
}

.carta-info-popover {
    transition: all 0.2s ease;
    border-radius: 50%;
    padding: 2px;
}

.carta-info-popover:hover {
    transform: scale(1.1);
    color: #0d6efd !important;
    background-color: rgba(13, 110, 253, 0.1);
}

.carta-popover .badge {
    font-size: 0.75rem;
    font-weight: 500;
}

.carta-popover .badge.bg-purple {
    background-color: #6f42c1 !important;
}

.carta-popover .badge.bg-pink {
    background-color: #d63384 !important;
}

.carta-popover hr {
    margin: 8px 0;
    opacity: 0.3;
}

.carta-popover .d-flex {
    align-items: flex-start;
}

.carta-popover i {
    margin-top: 2px;
    font-size: 0.9rem;
}

.carta-popover strong {
    font-weight: 600;
    color: #333;
}

.carta-popover small {
    font-size: 0.8rem;
    color: #6c757d;
}

.carta-popover .badge.bg-light {
    color: #495057 !important;
    border: 1px solid #dee2e6;
}
</style>
@endpush
