@extends('layouts.app')

@section('breadcrumbs')
{{ Breadcrumbs::render('ninios.index') }}
@endsection

@section('breadcrumb_elements')
<div class="d-lg-flex mb-2 mb-lg-0">
    <a href="{{ route('ninios.create') }}" class="d-flex align-items-center text-body py-2">
        <i class="ph ph-file-plus"></i>Nuevo
    </a>
    <a href="{{ route('ninios.importar') }}" class="d-flex align-items-center text-body py-2 ms-lg-3">
        <i class="ph ph-microsoft-excel-logo"></i>Importar
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
        <form id="filtrosForm" method="GET">
            <div class="row g-3">
                <div class="col-md-4">
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

                <div class="col-md-4">
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

                <div class="col-md-4">
                    <label class="form-label">Género</label>
                    <select name="genero_filter" class="form-select">
                        <option value="all">Todos</option>
                        @foreach($generos as $genero)
                            <option value="{{ $genero }}" {{ request('genero_filter') == $genero ? 'selected' : '' }}>
                                {{ $genero }}
                            </option>
                        @endforeach
                    </select>
                </div>

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
    <div class="card-body">
        <div class="table-responsive">
            {{ $dataTable->table() }}
        </div>
    </div>
</div>

@push('scripts')
    {{ $dataTable->scripts() }}

    <script>
    $(document).ready(function() {
        $('#toggleFilters').click(function() {
            $('#filterForm').slideToggle();
            const icon = $('#filterIcon');
            icon.toggleClass('ph-caret-down ph-caret-up');
        });

        if ({{ request()->hasAny(['comunidad_filter', 'estado_filter', 'genero_filter']) ? 'true' : 'false' }}) {
            $('#filterForm').show();
            $('#filterIcon').removeClass('ph-caret-down').addClass('ph-caret-up');
            $('#filtrosActivos').show();
        }

        $('#aplicarFiltros').click(function() {
            const form = $('#filtrosForm');
            const formData = form.serialize();

            window.LaravelDataTables['ninio-table'].ajax.url('{{ route("ninios.index") }}?' + formData).load();

            const newUrl = '{{ route("ninios.index") }}?' + formData;
            history.pushState(null, null, newUrl);

            const hasFilters = formData.includes('=') && formData.replace(/[^=]/g, '').length > 0;
            $('#filtrosActivos').toggle(hasFilters);
        });

        $('#limpiarFiltros').click(function() {
            $('#filtrosForm')[0].reset();
            window.LaravelDataTables['ninio-table'].ajax.url('{{ route("ninios.index") }}').load();
            history.pushState(null, null, '{{ route("ninios.index") }}');
            $('#filtrosActivos').hide();
        });

        $('#filtrosForm select').change(function() {
            clearTimeout(window.filterTimeout);
            window.filterTimeout = setTimeout(function() {
                $('#aplicarFiltros').click();
            }, 500);
        });

        $('#ninio-table').on('draw.dt', function() {
            const table = window.LaravelDataTables['ninio-table'];
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
