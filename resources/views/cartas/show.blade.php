@extends('layouts.app')

@section('breadcrumbs')
{{ Breadcrumbs::render('cartas.show',$carta) }}
@endsection

@section('breadcrumb_elements')
<div class="d-lg-flex mb-2 mb-lg-0">
    <a href="{{ route('cartas.edit',$carta) }}" class="d-flex align-items-center text-body py-2">
        <i class="ph ph-pen"></i>Editar
    </a>
</div>
@endsection

@section('content')


<div class="row mb-3">
    <div class="col-md-6">
        <div class="card">
          <div class="card-header">
            Carta de {{ $carta->tipo }}, del Niñ@: {{ $carta->ninio->nombres_completos }}
          </div>
          <div class="card-body">
            <div class="ratio ratio-16x9">
            <iframe src="{{ route('cartas.ver-carta-pdf',$carta->id) }}" class="card-img-top" allowfullscreen frameborder="0" mozallowfullscreen></iframe>
          </div>
          </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
          <div class="card-header">
            Detalle de la carta
          </div>
          <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-4">Tipo</dt>
                <dd class="col-sm-8">
                    <span class="badge bg-{{ $carta->color }}"><i class="{{ $carta->icono }} me-1"></i>{{ $carta->tipo }}</span>
                </dd>

                <dt class="col-sm-4">Estado</dt>
                <dd class="col-sm-8">
                    <span class="badge bg-{{ $carta->estado_color }}">{{ $carta->estado ?? 'Enviado' }}</span>
                </dd>

                <dt class="col-sm-4">Niñ@</dt>
                <dd class="col-sm-8">{{ $carta->ninio->nombres_completos }}</dd>

                <dt class="col-sm-4">Comunidad</dt>
                <dd class="col-sm-8">{{ $carta->comunidad->nombre ?? 'Sin comunidad' }}</dd>

                <dt class="col-sm-4">Gestor</dt>
                <dd class="col-sm-8">{{ $carta->gestor->name ?? 'Sin gestor' }}</dd>

                <dt class="col-sm-4">Fecha de creación</dt>
                <dd class="col-sm-8">{{ optional($carta->created_at)->format('d/m/Y H:i') }}</dd>

                <dt class="col-sm-4">Fecha de respuesta</dt>
                <dd class="col-sm-8">{{ optional($carta->fecha_respondida)->format('d/m/Y H:i') ?? 'Sin responder' }}</dd>
            </dl>
          </div>
        </div>
    </div>
</div>

@endsection
