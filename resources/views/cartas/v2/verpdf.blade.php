@switch($carta->tipo)
    @case('Contestación')
        @include('cartas.v2.pdf-contestacion',['carta'=>$carta])
        @break
    @case('Agradecimiento')
    @include('cartas.v2.pdf-agradecimiento',['carta'=>$carta])
        @break
    @case('Presentación')
        @include('cartas.v2.pdf-presentacion',['carta'=>$carta])
        @break
    @case('Iniciada')
        @include('cartas.v2.pdf-iniciada',['carta'=>$carta])
        @break
    @default
        <h1>NO EXISTE CARTA TIPO {{ $carta->tipo }}</h1>
@endswitch