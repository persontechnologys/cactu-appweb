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
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #ffffff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border: 5px solid #26b686; /* Borde verde de 5px */
        }

        .imagen_del_ninio img {
            width: 100%;
            max-width: 100%;
            height: auto;
        }

        .fecha {
            padding-top: 1em;
        }

        .cuerpo {
            margin-top: 20px;
        }

        .cuerpo table {
            background-color: #9eb8a1;
            width: 100%;
        }

        .cuerpo table th {
            padding: 10px;
            text-align: left;
        }

        .cuerpo table td {
            padding: 10px;
        }

        .cuerpo .imagen_del_ninio {
            margin-top: 20px;
            display: flex;
            justify-content: space-around;
        }

        .cuerpo .imagen_del_ninio td {
            width: 50%;
            height: 250px;
            background-size: cover;
            background-position: center;
        }

        .cuerpo h4 {
            margin-top: 20px;
        }

        .cuerpo .boletas img {
            margin-top: 10px;
            display: block;
            width: 100%;
            max-width: 100%;
            height: auto;
        }

        .cuerpo p {
            margin-top: 20px;
        }

        .imagen_del_ninio hr {
            margin-top: 40px;
            border: none;
            border-top: 1px solid #ccc;
        }

        .imagen_del_ninio img.footer {
            width: 100%;
            max-width: 100%;
            height: auto;
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
        <div class="imagen_del_ninio">
            <img src="{{ public_path('caratulas/cabecera-agradecimiento.png') }}">
        </div>

        <div class="fecha">
            <h3>Fecha {{ \Carbon\Carbon::parse($carta->fecha_respuesta)->format('d/M/Y') }}</h3>
        </div>

        <div class="cuerpo">
            <table>
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
            <div class="imagen_del_ninio table-container" >
                
                <table style="width: auto;">
                    <tr>
                        <th>Aquí está mi foto</th>
                       
                    </tr>
                    <tr>
                        <td style="
                            background-image: url({{ public_path($carta->archivo_imagen_ninio_link??'images/no-carta-imagen.png') }});
                            background-repeat: no-repeat;
                            background-size: contain;
                            background-size: 300px 400px;
                            background-position: center; 
                            width: 300px; 
                            height: 400px;
                        "></td>
                      
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
                            <img src="{{ public_path($carta->archivo_imagen_link??'images/no-carta-imagen.png') }}" />
                        </td>
                    @endif
        
                    @if ($carta->boletas->count() > 0)
                        @foreach ($carta->boletas as $index => $boleta)
                            <td>
                                <img src="{{ public_path($boleta->archivo_imagen_link??'images/no-carta-imagen.png') }}" />
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
            <img src="{{ public_path('img/cartas/ccagradecimiento.jpg') }}" style="width: 350px;">
            <p>With this letter, you will notice something is different that we are very excited about. We have
                introduced the use of tablets and cell phones that allow children to express themselves in their letters
                to you by not just writing, but also by taking photos of them and their drawings. We hope you enjoy this
                new kind of letter as much as children are enjoying creating them, and learning about technology in the
                process. Make sure you write back to your sponsor child and keep the conversation going!</p>
        </div>

        <hr>

        <div class="imagen_del_ninio">
            <img src="{{ public_path('caratulas/pie-agradecimiento.png') }}" class="footer">
        </div>
    </div>
</body>

</html>
