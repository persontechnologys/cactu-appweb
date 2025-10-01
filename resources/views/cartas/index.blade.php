@extends('layouts.app')

@section('breadcrumbs')
{{ Breadcrumbs::render('cartas.index') }}
@endsection

@section('breadcrumb_elements')
<div class="d-lg-flex mb-2 mb-lg-0">
    <a href="{{ route('cartas.create') }}" class="d-flex align-items-center text-body py-2">
        <i class="ph ph-file-plus"></i>Nuevo
    </a>
</div>
@endsection

@section('content')


{{-- Formulario de filtros --}}
<div class="card mb-3">
    <div class="card-header">
        <h6 class="card-title mb-0">
            <i class="ph-funnel me-2"></i>Filtros de Búsqueda
            <button type="button" class="btn btn-sm btn-outline-secondary ms-2" id="toggleFilters">
                <i class="ph-caret-down" id="filterIcon"></i>
            </button>
        </h6>
    </div>
    <div class="card-body" id="filterForm" style="display: none;">
        <div class="d-flex flex-wrap align-items-center gap-3 mb-2">
            <small class="text-muted fw-semibold">Tipos de carta:</small>
            <span class="badge bg-primary"><i class="ph-arrow-bend-up-left me-1"></i>Contestación</span>
            <span class="badge bg-purple"><i class="ph-heart me-1"></i>Agradecimiento</span>
            <span class="badge bg-info"><i class="ph-user-circle me-1"></i>Presentación</span>
            <span class="badge bg-pink"><i class="ph-play-circle me-1"></i>Iniciada</span>
            
            <div class="vr mx-2"></div>
            
            <small class="text-muted fw-semibold">Estados:</small>
            <span class="badge bg-success">Respondida</span>
            <span class="badge bg-warning">Enviado</span>
        </div>
        <hr>
        <form id="filtrosForm" method="GET">
            <div class="row g-3">
                <!-- Filtros básicos -->
                <div class="col-md-3">
                    <label class="form-label">Tipo de Carta</label>
                    <select name="tipo_filter" class="form-select">
                        <option value="all">Todos los tipos</option>
                        @foreach($tiposCartas as $tipo)
                            <option value="{{ $tipo }}" {{ request('tipo_filter') == $tipo ? 'selected' : '' }}>
                                {{ $tipo }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Estado</label>
                    <select name="estado_filter" class="form-select">
                        <option value="all">Todos los estados</option>
                        @foreach($estados as $estado)
                            <option value="{{ $estado }}" {{ request('estado_filter') == $estado ? 'selected' : '' }}>
                                {{ $estado }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Buscar Niño</label>
                    <input type="text" name="ninio_filter" class="form-control" 
                           placeholder="Número Child o Nombre" 
                           value="{{ request('ninio_filter') }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Comunidad</label>
                    <select name="comunidad_filter" class="form-select">
                        <option value="all">Todas las comunidades</option>
                        @foreach($comunidades as $comunidad)
                            <option value="{{ $comunidad->id }}" {{ request('comunidad_filter') == $comunidad->id ? 'selected' : '' }}>
                                {{ $comunidad->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Filtros de fechas -->
                <div class="col-12">
                    <hr class="my-2">
                    <h6 class="text-muted">Rango de Fechas</h6>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Fecha Creación - Desde</label>
                    <input type="date" name="fecha_inicio" class="form-control" value="{{ request('fecha_inicio') }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Fecha Creación - Hasta</label>
                    <input type="date" name="fecha_fin" class="form-control" value="{{ request('fecha_fin') }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Fecha Respuesta - Desde</label>
                    <input type="date" name="fecha_respuesta_inicio" class="form-control" value="{{ request('fecha_respuesta_inicio') }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Fecha Respuesta - Hasta</label>
                    <input type="date" name="fecha_respuesta_fin" class="form-control" value="{{ request('fecha_respuesta_fin') }}">
                </div>

                @if($gestores->count() > 1)
                <div class="col-md-4">
                    <label class="form-label">Gestor</label>
                    <select name="gestor_filter" class="form-select">
                        <option value="all">Todos los gestores</option>
                        @foreach($gestores as $gestor)
                            <option value="{{ $gestor->id }}" {{ request('gestor_filter') == $gestor->id ? 'selected' : '' }}>
                                {{ $gestor->name }} ({{ $gestor->email }})
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif

                <!-- Botones de acción -->
                <div class="col-12">
                    <hr class="my-2">
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-primary" id="aplicarFiltros">
                            <i class="ph-magnifying-glass me-1"></i>Aplicar Filtros
                        </button>
                        <button type="button" class="btn btn-outline-secondary" id="limpiarFiltros">
                            <i class="ph-eraser me-1"></i>Limpiar Filtros
                        </button>
                        <div class="ms-auto">
                            <span class="badge bg-info" id="filtrosActivos" style="display: none;">
                                <i class="ph-funnel me-1"></i>Filtros aplicados
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0">
            <i class="ph-table me-2"></i>Lista de Cartas
        </h6>
        <div class="d-flex align-items-center gap-2">
            <small id="registros-info" class="text-muted"></small>
            <div class="spinner-border spinner-border-sm text-primary" id="loading-spinner" style="display: none;" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            {{ $dataTable->table() }}
        </div>
    </div>
</div>

@push('styles')
<style>
.badge.bg-purple {
    background-color: #6f42c1 !important;
}

.badge.bg-pink {
    background-color: #d63384 !important;
}

.vr {
    width: 1px;
    height: 20px;
    background-color: #dee2e6;
}

/* Estilos para el formulario de filtros */
#filterForm {
    border-top: 1px solid #dee2e6;
    margin-top: -1px;
}

.card-header h6 {
    display: flex;
    align-items: center;
    margin: 0;
}

#toggleFilters {
    transition: all 0.2s ease;
}

#toggleFilters:hover {
    transform: scale(1.05);
}

#filterIcon {
    transition: transform 0.3s ease;
}

.form-label {
    font-weight: 600;
    font-size: 0.875rem;
    color: #495057;
    margin-bottom: 0.25rem;
}

.form-control, .form-select {
    font-size: 0.875rem;
}

#filtrosActivos {
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Mejorar la apariencia de los botones de filtro */
#aplicarFiltros, #limpiarFiltros {
    font-size: 0.875rem;
    padding: 0.5rem 1rem;
    font-weight: 500;
}

/* Estilo para indicar campos con filtros activos */
.form-control:not([value=""]), .form-select:not([value=""]):not([value="all"]) {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.1rem rgba(13, 110, 253, 0.1);
}

/* Responsive para móviles */
@media (max-width: 768px) {
    .col-md-3, .col-md-4 {
        margin-bottom: 1rem;
    }
    
    .d-flex.gap-2 {
        flex-direction: column;
        gap: 0.5rem !important;
    }
    
    .d-flex.gap-2 .ms-auto {
        margin: 0 !important;
    }
}

/* Estilos para el DataTable */
.dataTables_length {
    margin-bottom: 1rem;
}

.dataTables_length select {
    padding: 0.375rem 0.75rem;
    margin: 0 0.5rem;
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
    background-color: #fff;
    font-size: 0.875rem;
}

.dataTables_length label {
    font-weight: 500;
    margin-bottom: 0;
    display: flex;
    align-items: center;
}

.dataTables_info {
    font-size: 0.875rem;
    color: #6c757d;
}

.dataTables_paginate {
    font-size: 0.875rem;
}

.dataTables_wrapper .dataTables_filter {
    margin-bottom: 1rem;
}

.dataTables_wrapper .dataTables_filter input {
    padding: 0.375rem 0.75rem;
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
    margin-left: 0.5rem;
}

/* Mejorar el layout del DataTable */
.dataTables_wrapper .row {
    align-items: center;
}

.dataTables_wrapper .dataTables_length,
.dataTables_wrapper .dataTables_filter {
    display: flex;
    align-items: center;
}
</style>
@endpush

@push('scripts')
    {{ $dataTable->scripts() }}
    
    <script>
    $(document).ready(function() {
        // Toggle para mostrar/ocultar filtros
        $('#toggleFilters').click(function() {
            $('#filterForm').slideToggle();
            const icon = $('#filterIcon');
            icon.toggleClass('ph-caret-down ph-caret-up');
        });

        // Mostrar filtros si hay parámetros de búsqueda
        if ({{ request()->hasAny(['tipo_filter', 'estado_filter', 'ninio_filter', 'comunidad_filter', 'fecha_inicio', 'fecha_fin', 'fecha_respuesta_inicio', 'fecha_respuesta_fin', 'gestor_filter']) ? 'true' : 'false' }}) {
            $('#filterForm').show();
            $('#filterIcon').removeClass('ph-caret-down').addClass('ph-caret-up');
            $('#filtrosActivos').show();
        }

        // Aplicar filtros
        $('#aplicarFiltros').click(function() {
            const form = $('#filtrosForm');
            const formData = form.serialize();
            
            // Mostrar spinner de carga
            $('#loading-spinner').show();
            
            // Recargar DataTable con filtros
            window.LaravelDataTables['carta-table'].ajax.url('{{ route("cartas.index") }}?' + formData).load(function() {
                // Ocultar spinner cuando termine de cargar
                $('#loading-spinner').hide();
            });
            
            // Actualizar URL del navegador
            const newUrl = '{{ route("cartas.index") }}?' + formData;
            history.pushState(null, null, newUrl);
            
            // Mostrar indicador de filtros activos
            const hasFilters = formData.includes('=') && formData.replace(/[^=]/g, '').length > 0;
            if (hasFilters) {
                $('#filtrosActivos').show();
            } else {
                $('#filtrosActivos').hide();
            }
        });

        // Limpiar filtros
        $('#limpiarFiltros').click(function() {
            $('#filtrosForm')[0].reset();
            
            // Recargar DataTable sin filtros
            window.LaravelDataTables['carta-table'].ajax.url('{{ route("cartas.index") }}').load();
            
            // Actualizar URL del navegador
            history.pushState(null, null, '{{ route("cartas.index") }}');
            
            // Ocultar indicador de filtros activos
            $('#filtrosActivos').hide();
        });

        // Aplicar filtros cuando se cambie cualquier select o input
        $('#filtrosForm select, #filtrosForm input').change(function() {
            // Auto-aplicar filtros después de un pequeño delay
            clearTimeout(window.filterTimeout);
            window.filterTimeout = setTimeout(function() {
                $('#aplicarFiltros').click();
            }, 500);
        });

        // Tecla Enter en inputs de texto
        $('#filtrosForm input[type="text"]').keypress(function(e) {
            if (e.which === 13) { // Enter key
                e.preventDefault();
                $('#aplicarFiltros').click();
            }
        });

        // Contador de resultados
        $('#carta-table').on('draw.dt', function() {
            const table = window.LaravelDataTables['carta-table'];
            const info = table.page.info();
            
            if (info.recordsDisplay !== info.recordsTotal) {
                $('#filtrosActivos').text(`Filtros aplicados (${info.recordsDisplay} de ${info.recordsTotal} registros)`).show();
            } else if ($('#filtrosActivos').is(':visible')) {
                $('#filtrosActivos').text(`${info.recordsTotal} registros encontrados`);
            }
        });
    });
    </script>
@endpush

@endsection
