@extends('responder-cartas.principal')

@section('content')

<p class="text-muted">Ingresá tu número de niño (número child) para ver y responder tus cartas.</p>

<form action="{{ route('cartas-ninio.ingresar.store') }}" method="POST">
    @csrf
    <div class="mb-3">
        <label for="numero_child" class="form-label">Número child</label>
        <input type="text" name="numero_child" id="numero_child" value="{{ old('numero_child') }}" class="form-control @error('numero_child') is-invalid @enderror" autofocus required>
        @error('numero_child')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
    </div>
    <button type="submit" class="btn btn-primary w-100">Ingresar</button>
</form>

@endsection
