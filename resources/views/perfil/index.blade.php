@extends('layouts.app')

@section('breadcrumbs')
{{ Breadcrumbs::render('perfil') }}
@endsection

@section('content')


{{-- Formulario para actualizar información del perfil --}}
<form action="{{ route('perfil.guardar') }}" method="POST" autocomplete="off">
    @csrf
    <div class="card">
        <div class="card-body row">
            <div class="fw-bold border-bottom pb-2 mb-3">INFORMACIÓN PERSONAL</div>
            
            <div class="col-md-6 mb-1">
                <div class="form-floating form-control-feedback form-control-feedback-start">
                    <div class="form-control-feedback-icon">
                        <i class="ph-user"></i>
                    </div>
                    <input name="name" value="{{ old('name', auth()->user()->name) }}" type="text" class="form-control @error('name') is-invalid @enderror" required>
                    <label>Nombre de usuario<i class="text-danger">*</i></label>
                    @error('name')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </div>

            <div class="col-md-6 mb-1">
                <div class="form-floating form-control-feedback form-control-feedback-start">
                    <div class="form-control-feedback-icon">
                        <i class="ph-envelope-simple"></i>
                    </div>
                    <input name="email" value="{{ old('email', auth()->user()->email) }}" type="email" class="form-control @error('email') is-invalid @enderror" required>
                    <label>Email<i class="text-danger">*</i></label>
                    @error('email')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </div>
        </div>
        <div class="card-footer text-muted">
            <button type="submit" class="btn btn-primary">Actualizar Perfil</button>
        </div>
    </div>
</form>

<div class="mt-4"></div>

{{-- Formulario para cambiar contraseña --}}
<form action="{{ route('perfil.cambiar-password') }}" method="POST" autocomplete="off">
    @csrf
    <div class="card">
        <div class="card-body row">
            <div class="fw-bold border-bottom pb-2 mb-3">CAMBIAR CONTRASEÑA</div>
            
            <div class="col-md-4 mb-1">
                <div class="form-floating form-control-feedback form-control-feedback-start">
                    <div class="form-control-feedback-icon">
                        <i class="ph-lock-simple"></i>
                    </div>
                    <input name="current_password" type="password" class="form-control @error('current_password') is-invalid @enderror" required>
                    <label>Contraseña Actual<i class="text-danger">*</i></label>
                    @error('current_password')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </div>

            <div class="col-md-4 mb-1">
                <div class="form-floating form-control-feedback form-control-feedback-start">
                    <div class="form-control-feedback-icon">
                        <i class="ph-lock-simple"></i>
                    </div>
                    <input name="new_password" type="password" class="form-control @error('new_password') is-invalid @enderror" required minlength="8">
                    <label>Nueva Contraseña<i class="text-danger">*</i></label>
                    @error('new_password')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </div>

            <div class="col-md-4 mb-1">
                <div class="form-floating form-control-feedback form-control-feedback-start">
                    <div class="form-control-feedback-icon">
                        <i class="ph-lock-simple"></i>
                    </div>
                    <input name="new_password_confirmation" type="password" class="form-control" required minlength="8">
                    <label>Confirmar Nueva Contraseña<i class="text-danger">*</i></label>
                </div>
                <div class="form-text mt-1">La contraseña debe tener al menos 8 caracteres.</div>
            </div>
        </div>
        <div class="card-footer text-muted">
            <button type="submit" class="btn btn-warning">Cambiar Contraseña</button>
        </div>
    </div>
</form>

{{-- Script para auto-ocultar alertas --}}
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-ocultar alertas después de 5 segundos
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert.alert-success');
        alerts.forEach(function(alert) {
            if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        });
    }, 5000);
});
</script>
@endpush
@endsection
