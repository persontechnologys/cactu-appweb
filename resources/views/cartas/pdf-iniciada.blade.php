<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: 'Comic Sans MS', 'Comic Sans', cursive;
        }

        .contenedor {
            background-image: url("{!! public_path('img/cartas/fondo_iniciada.png') !!}");
            background-repeat: no-repeat;
            background-size: 100% 100%;
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            box-sizing: border-box;
            font-family: 'Handlee', cursive;
        }

        .contenido {
            margin: 0 auto;
            padding: 5px;
            border-radius: 15px;
            width: 90%;
            box-sizing: border-box;
        }

        .fecha {
            text-align: center;
            margin-top: 2em;
            font-size: 24px;
            color: #18b2ce;
        }

        .cuerpo {
            margin-top: 2em;
          
        }

        .imagen_del_ninio img {
            width: 100%;
        }

       

        .imagen_del_ninio p {
            font-size: 16px;
            line-height: 1.6;
        }

        /* Evitar cortes de página dentro de este contenedor */
        .imagen_del_ninio {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        /* Estilo de borde verde para el PDF */
        @page {
            margin: 5px;
            border: 5px solid #3fa855; /* Borde verde de 5px */
        }
        
        .cuerpo table {
            width: 100%;
            border-collapse: collapse;
            background-color: #18b3ce;
            
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
        <br>
        
        <div class="contenido">
            
            <div class="fecha">
                <h3>Fecha {{ \Carbon\Carbon::parse($carta->fecha_respondida)->format('d/M/Y') }}</h3>
            </div>

            <div class="cuerpo">
                <table style="background-color: #18b2ce; width: 100%;">
                    <tr>
                        <th>N° child de niño: {{ $carta->ninio->numero_child }}</th>
                        <th>Carta de {{ $carta->tipo }}</th>
                    </tr>
                </table>
                <br>
                {!! $carta->detalle !!}
            </div>

            <center>
                <div class="cuerpo">
                    <div class="imagen_del_ninio">
                        <table style="width: auto;">
                            <tr>
                                <th>Aquí está mi foto</th>
                            </tr>
                            <tr>
                                <td style="background: url({!! public_path($carta->archivo_imagen_ninio_link??'img/no_carta_imagen.png') !!});
                                    background-repeat: no-repeat;
                                    background-size: contain;
                                    background-size: 300px 400px;
                                    background-position: center; 
                                    width: 300px; 
                                    height: 400px;
                                ">
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </center>

            <div class="cuerpo imagen_del_ninio">
                <h4>Boletas</h4>
                <table>
                    <tr>
                        @if ($carta->archivo_imagen)
                            <td>
                                <img src="{{ public_path($carta->archivo_imagen_link??'img/no_carta_imagen.png') }}" />
                            </td>
                        @endif
            
                        @if ($carta->boletas->count() > 0)
                            @foreach ($carta->boletas as $index => $boleta)
                                <td>
                                    <img src="{{ public_path($boleta->archivo_imagen_link??'img/no_carta_imagen.png') }}" />
                                </td>
                                @if (($index + 1) % 2 == 0)
                                    </tr><tr>
                                @endif
                            @endforeach
                        @endif
                    </tr>
                </table>
            </div>

            <div class="imagen_del_ninio">
                
                <img style="width: 350px;" src="{{ public_path('img/cartas/ccagradecimiento.jpg') }}">
                <p>With this letter, you will notice something is different that we are very excited
                    about. We have introduced the use of tablets and cell phones that allow children
                    to express themselves in their letters to you by not just writing, but also by taking
                    photos of them and their drawings. We hope you enjoy this new kind of letter as
                    much as children are enjoying creating them, and learning about technology in the
                    process. Make sure you write back to your sponsor child and keep the
                    conversation going!</p>
            </div>
        </div>
    </div>
</body>

</html>
