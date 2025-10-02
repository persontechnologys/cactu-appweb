@extends('layouts.app')

@section('breadcrumbs')
{{ Breadcrumbs::render('home') }}
@endsection

@section('content')
    <!-- Content area -->
    <div class="content">
        <!-- Alert de notificación -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-primary alert-dismissible fade show border-2 border-primary shadow-sm" role="alert">
                    <div class="d-flex align-items-start">
                        <div class="me-3">
                            <i class="ph ph-info fs-3 text-primary"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="alert-heading mb-2">
                                <i class="ph ph-calendar-check me-2"></i>
                                Actualización Importante
                            </h6>
                            <p class="mb-2">
                                Los nuevos formatos de la carta estarán disponibles <strong class="text-decoration-underline">solo hasta el 25 de octubre de 2025</strong>. 
                                Activa el cambio hoy para estandarizar tus comunicaciones y evitar retrabajos.
                            </p>
                            <div class="d-flex flex-wrap gap-2 mt-3">
                                <a href="https://persontechnology.net/contacto" class="btn btn-sm btn-primary" target="_blank">
                                    <i class="ph ph-phone me-1"></i>
                                    Contactar Person Technology
                                </a>
                                <button type="button" class="btn btn-sm btn-yellow" data-bs-dismiss="alert">
                                    <i class="ph ph-x me-1"></i>
                                    Cerrar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main content grid -->
        <div class="row g-4">
            <!-- Logo y información principal -->
            <div class="col-lg-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="mb-4">
                            <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 rounded-circle mb-3" style="width: 120px; height: 120px;">
                                <img src="{{ asset('assets/images/logo_icon_azul.svg') }}" width="80" height="80" alt="CACTU Logo" class="img-fluid">
                            </div>
                        </div>
                        <h4 class="fw-bold text-primary mb-2">CACTU</h4>
                        <p class="text-muted mb-4">Corporación de Asociaciones de Cotopaxi y Tungurahua</p>
                        
                        <div class="row g-3 text-start">
                            <div class="col-12">
                                <div class="d-flex align-items-center p-3 bg-light rounded">
                                    <i class="ph ph-users fs-4 text-primary me-3"></i>
                                    <div>
                                        <h6 class="mb-1">Gestión de Comunidades</h6>
                                        <small class="text-muted">Administra y coordina las asociaciones</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="d-flex align-items-center p-3 bg-light rounded">
                                    <i class="ph ph-envelope fs-4 text-success me-3"></i>
                                    <div>
                                        <h6 class="mb-1">Correspondencia</h6>
                                        <small class="text-muted">Sistema de cartas y comunicaciones</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Panel de acciones -->
            <div class="col-lg-6">
                <div class="row g-4 h-100">
                    @auth
                    <!-- Manual de usuario -->
                    <div class="col-12">
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

                    <!-- Estadísticas rápidas -->
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-4">
                                <h6 class="mb-3">
                                    <i class="ph ph-chart-bar me-2 text-info"></i>
                                    Acceso Rápido
                                </h6>
                                <div class="row g-3">
                                    <div class="col-6">
                                        <div class="text-center p-3 bg-primary bg-opacity-10 rounded">
                                            <i class="ph ph-envelope-open fs-3 text-primary d-block mb-2"></i>
                                            <div class="fw-semibold">Cartas</div>
                                            <small class="text-muted">Gestionar</small>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-center p-3 bg-success bg-opacity-10 rounded">
                                            <i class="ph ph-users-three fs-3 text-success d-block mb-2"></i>
                                            <div class="fw-semibold">Niños</div>
                                            <small class="text-muted">Administrar</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @else
                    <!-- Panel para usuarios no autenticados -->
                    <div class="col-12">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body p-4 text-center">
                                <div class="mb-4">
                                    <i class="ph ph-lock fs-1 text-muted"></i>
                                </div>
                                <h5 class="mb-3">Acceso Restringido</h5>
                                <p class="text-muted mb-4">Inicia sesión para acceder a todas las funcionalidades del sistema.</p>
                                <a href="{{ route('login') }}" class="btn btn-primary fw-semibold">
                                    <i class="ph ph-sign-in me-2"></i>
                                    Iniciar Sesión
                                </a>
                            </div>
                        </div>
                    </div>
                    @endauth
                </div>
            </div>
        </div>

        <!-- Información adicional -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h6 class="mb-2">
                                    <i class="ph ph-info me-2 text-info"></i>
                                    Sistema de Gestión CACTU
                                </h6>
                                <p class="text-muted mb-0">
                                    Plataforma integral para la administración de asociaciones, gestión de correspondencia 
                                    y seguimiento de actividades comunitarias.
                                </p>
                            </div>
                            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                <span class="badge bg-primary fs-6 px-3 py-2">
                                    <i class="ph ph-shield-check me-1"></i>
                                    Sistema Seguro
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /content area -->
@endsection
