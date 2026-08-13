@extends('layouts.app')

@section('breadcrumbs')
{{ Breadcrumbs::render('home') }}
@endsection

@section('content')
    <!-- Content area -->
    <div class="content">
        <!-- KPIs -->
        <div class="row g-4">
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4 d-flex align-items-center">
                        <div class="bg-warning bg-opacity-15 p-3 rounded-circle me-3">
                            <i class="ph ph-envelope-open fs-3 text-warning"></i>
                        </div>
                        <div>
                            <div class="fs-2 fw-bold">{{ $cartasPorResponder }}</div>
                            <small class="text-muted">Cartas por responder</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4 d-flex align-items-center">
                        <div class="bg-success bg-opacity-15 p-3 rounded-circle me-3">
                            <i class="ph ph-check-circle fs-3 text-success"></i>
                        </div>
                        <div>
                            <div class="fs-2 fw-bold">{{ $cartasRespondidas }}</div>
                            <small class="text-muted">Cartas respondidas</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4 d-flex align-items-center">
                        <div class="bg-primary bg-opacity-15 p-3 rounded-circle me-3">
                            <i class="ph ph-users-three fs-3 text-primary"></i>
                        </div>
                        <div>
                            <div class="fs-2 fw-bold">{{ $totalNinios }}</div>
                            <small class="text-muted">Niños en tu alcance</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4 d-flex align-items-center">
                        <div class="bg-danger bg-opacity-15 p-3 rounded-circle me-3">
                            <i class="ph ph-warning-circle fs-3 text-danger"></i>
                        </div>
                        <div>
                            <div class="fs-2 fw-bold">{{ $niniosSinCarta }}</div>
                            <small class="text-muted">Niños sin carta aún</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Manual de usuario + Niños por comunidad -->
        <div class="row g-4 mt-1">
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-warning bg-opacity-15 p-3 rounded-circle me-3">
                                <i class="fa-solid fa-book-open-reader fs-4 text-dark"></i>
                            </div>
                            <div>
                                <h5 class="mb-1">Manual de Usuario</h5>
                                <small class="text-muted">Guía completa del sistema</small>
                            </div>
                        </div>
                        <p class="text-muted mb-4">Aprende a utilizar todas las funcionalidades del sistema con nuestro manual interactivo.</p>
                        <a href="{{ route('manual') }}" class="btn btn-warning fw-semibold w-100">
                            <i class="ph ph-play-circle me-2"></i>
                            Abrir Manual
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h6 class="mb-3">
                            <i class="ph ph-map-pin me-2 text-info"></i>
                            Niños por comunidad
                        </h6>
                        @if($niniosPorComunidad->isEmpty())
                            <p class="text-muted mb-0">No hay comunidades registradas en tu alcance.</p>
                        @else
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Comunidad</th>
                                            <th class="text-end">Niños</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($niniosPorComunidad as $comunidad)
                                            <tr>
                                                <td>{{ $comunidad->nombre }}</td>
                                                <td class="text-end">
                                                    <span class="badge bg-primary">{{ $comunidad->ninios_count }}</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /content area -->
@endsection
