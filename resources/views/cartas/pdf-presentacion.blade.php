<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title> {{ $title }}</title>
    <style>
        
        body {
            font-family: 'Comic Sans MS', 'Comic Sans', cursive;
        }

        .contenedor {
            background-image: url("{!! public_path('img/cartas/fondo.jpg') !!}");
            background-repeat: no-repeat;
            background-size: 100% 1350px;
            width: 100%;
            height: 1290px;
            border-radius: 100%;
            padding: 20px;
            box-sizing: border-box;
            font-family: 'Handlee', cursive;
        }

        .fecha {
            padding-left: 8em;
            padding-top: 5em;
            font-size: 19px;
        }

        .cuerpo {
            padding-top: 0%;
            font-size: 17px;
            padding-left: 8em;
            padding-right: 8em;
            letter-spacing: 1px;
            border: 3px solid #cbd818; /* Borde alrededor del cuerpo principal */
            margin-bottom: 20px;
        }

        .imagen-del-ninio {
            page-break-inside: avoid; /* Evitar salto de página dentro de la imagen */
            break-inside: avoid; /* Para navegadores modernos */
        }

        .seccion-imagen {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .imagen-ninio,
        .imagen-familia {
            width: 48%;
            height: 500px; /* Ajustar altura según sea necesario */
            background-size: cover;
            background-position: center;
            border: 3px solid #cbd818; /* Borde alrededor de las imágenes de ninio y familia */
            position: relative;
            overflow: hidden; /* Evitar desbordamiento de la imagen */
        }

        .imagen-titulo {
            position: absolute;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #cbd818;
            color: #fff;
            padding: 5px 10px;
            border-radius: 5px;
        }

        .boletas {
            padding: 20px;
            background-color: #f0f0f0;
            border: 3px solid #cbd818; /* Borde alrededor de la sección de boletas */
            margin-bottom: 20px;
        }

        .boletas img {
            width: 100%;
          
        }

        .provincia-img {
            width: 100%;
            border: 3px solid #cbd818; /* Borde alrededor de la imagen de provincia */
        }

        .bottom-arrow {
            border-bottom: 15px solid #cbd818;
            position: relative;
            margin-bottom: 20px;
        }

        .bottom-arrow::after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: -15px;
            transform: translateX(-50%);
            border-width: 15px 50px 0;
            border-style: solid;
            border-color: #cbd818 transparent transparent;
        }

        /* Evitar cortes de página dentro de este contenedor */
        .imagen_del_ninio {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        /* Estilo de borde verde para el PDF */
        @page {
            margin: 20px;
            border: 5px solid #3fa855; /* Borde verde de 5px */
        }
        
        .cuerpo table {
            width: 100%;
            border-collapse: collapse;
        }

        .cuerpo td {
            width: 50%; /* Dos columnas */
            padding: 10px; /* Espacio entre las imágenes */
        }

        .cuerpo img {
            width: 100%;
            height: auto;
            max-height: 200px; /* Ajusta este valor según sea necesario */
            object-fit: contain; /* Ajusta la imagen dentro del contenedor sin recortar */
            display: block;
            margin: 0 auto;
        }
    </style>
</head>

<body>
    <div class="contenedor">

        <div class="fecha" style="padding-top: 12em; text-align: left;">
            <p style="text-align: left;">Fecha {{ \Carbon\Carbon::parse($carta->fecha_respondida)->format('d/M/Y') }} </p>
        </div>

        <div class="cuerpo">
            <table style="background-color: #cbd818; width: 100%;">
                <tr>
                    <th>N° child de niño: {{ $carta->ninio->numero_child }}</th>
                    <th>Carta de {{ $carta->tipo }}</th>
                </tr>
            </table>
            <br>
            
            {!! $carta->detalle !!}

        </div>

        @php
            $fotosCarta = [];
            if ($carta->archivo_imagen_ninio) {
                $fotosCarta[] = ['titulo' => 'Aquí está mi foto', 'ruta' => $carta->archivo_imagen_ninio_link];
            }
            if ($carta->archivo_familia_ninio) {
                $fotosCarta[] = ['titulo' => 'Esta es mi familia', 'ruta' => $carta->archivo_familia_ninio_link];
            }
        @endphp
        @if (count($fotosCarta) > 0)
            @include('cartas.partials.pdf-fotos', ['fotos' => $fotosCarta, 'ancho' => 300, 'alto' => 400, 'color' => '#cbd818', 'colorTexto' => '#3b3f00'])
        @endif

        <div class="seccion-boletas">
            @include('cartas.partials.pdf-boletas', ['carta' => $carta, 'color' => '#cbd818', 'colorTexto' => '#3b3f00'])
        </div>

        <div class="provincia-img">
            @if ($carta->gestor->provincia == 'COTOPAXI')
                <img style="width: 100%;" src="{{ public_path('img/cartas/cotopaxi.png') }}">
            @else
                <img style="width: 100%;" src="{{ public_path('img/cartas/tungurahua.png') }}">
            @endif
        </div>

    </div>

</body>

</html>
