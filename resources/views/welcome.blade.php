@extends('layouts.app')

@section('content')

<div class="d-flex flex-column justify-content-center align-items-center text-center" style="min-height: 60vh;">

    <img src="{{ asset('assets/images/logo_icon_azul.svg') }}" width="80" height="80" alt="CACTU" class="mb-3">
    <h2 class="fw-bold text-primary mb-1">CACTU</h2>
    <p class="text-muted mb-4">Corporación de Asociaciones de Cotopaxi y Tungurahua</p>

    <div class="row g-3 justify-content-center mb-4" style="max-width: 460px;">
        <div class="col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center p-3">
                    <i class="ph ph-users fs-2 text-primary d-block mb-2"></i>
                    <small class="fw-semibold">Gestión de Comunidades</small>
                </div>
            </div>
        </div>
        <div class="col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center p-3">
                    <i class="ph ph-envelope fs-2 text-success d-block mb-2"></i>
                    <small class="fw-semibold">Correspondencia</small>
                </div>
            </div>
        </div>
    </div>

    <a href="{{ route('login') }}" class="btn btn-primary fw-semibold px-4">
        <i class="ph ph-sign-in me-2"></i>
        Iniciar Sesión
    </a>

</div>

@endsection
