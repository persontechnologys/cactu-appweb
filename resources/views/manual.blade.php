@extends('layouts.app')

@section('breadcrumbs')
    {{ Breadcrumbs::render('home') }}
@endsection

@section('content')
    <h1>MANUAL DE USUARIO</h1>
    <div class="ratio ratio-16x9">
        <video src="{{ asset('img/manual.mp4') }}" controls></video>
    </div>
@endsection
