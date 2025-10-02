<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ $title }}</title>
    <style>
        /* Reset y configuración base */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Comic Sans MS', 'Comic Sans', cursive;
            background: #fff8fb;
            color: #2c3e50;
            line-height: 1.6;
            position: relative;
        }

        /* Fondo decorativo con tema de nuevo comienzo */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 15% 25%, rgba(243, 92, 134, 0.1) 0%, transparent 35%),
                radial-gradient(circle at 85% 15%, rgba(255, 112, 154, 0.1) 0%, transparent 35%),
                radial-gradient(circle at 75% 85%, rgba(255, 140, 174, 0.08) 0%, transparent 35%),
                radial-gradient(circle at 25% 75%, rgba(231, 72, 114, 0.08) 0%, transparent 35%);
            pointer-events: none;
            z-index: -1;
        }

        /* Configuración de página para PDF */
        @page {
            margin: 10mm;
            size: A4;
        }

        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }

        /* Header fijo */
        .header-fixed {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 120px;
            z-index: 1000;
        }

        .header-fixed img {
            width: 100%;
            height: 120px;
        }

        /* Footer fijo */
        .footer-fixed {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 100px;
            z-index: 1000;
        }

        .footer-fixed img {
            width: 100%;
            height: 100px;
        }

        /* Contenedor principal */
        .contenedor {
            max-width: 100%;
            margin: 0 auto;
            background: #ffffff;
            padding-top: 140px;
            padding-bottom: 120px;
            min-height: 100vh;
            position: relative;
        }

        /* Decoraciones del contenedor - tema amanecer */
        .contenedor::before {
            content: '';
            position: absolute;
            top: 160px;
            right: 30px;
            width: 60px;
            height: 60px;
            background: #f35c86;
            border-radius: 50%;
            border: 5px solid #e74872;
            opacity: 0.8;
        }

        .contenedor::after {
            content: '';
            position: absolute;
            top: 180px;
            left: 40px;
            width: 45px;
            height: 45px;
            background: #ff709a;
            border-radius: 50%;
            opacity: 0.8;
        }

        /* Sección de fecha - estilo amanecer */
        .fecha-section {
            background: #ffebf1;
            padding: 25px 30px;
            border: 5px solid #f35c86;
            margin: 20px 0;
            position: relative;
            border-radius: 25px;
            text-align: center;
        }

        .fecha-section::before {
            content: '';
            position: absolute;
            top: -18px;
            left: 40px;
            width: 40px;
            height: 40px;
            background: #ff709a;
            border-radius: 50%;
            border: 5px solid #e74872;
        }

        .fecha-section::after {
            content: '';
            position: absolute;
            bottom: -15px;
            right: 40px;
            width: 35px;
            height: 35px;
            background: #ff8cae;
            border-radius: 50%;
            border: 4px solid #f35c86;
        }

        .fecha-title {
            font-size: 20px;
            font-weight: 700;
            color: #c1325e;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Información del niño - estilo acogedor */
        .info-section {
            margin: 20px 0;
            padding: 0;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            background: #ffffff;
            border: 3px solid #f44336;
            border-radius: 20px;
            overflow: hidden;
            position: relative;
        }

        .info-table::before {
            content: '';
            position: absolute;
            top: -20px;
            left: 75px;
            width: 35px;
            height: 35px;
            background: #e91e63;
            border-radius: 50%;
            border: 5px solid #c2185b;
        }

        .info-table::after {
            content: '';
            position: absolute;
            bottom: -18px;
            right: 65px;
            width: 0;
            height: 0;
            border-left: 18px solid transparent;
            border-right: 18px solid transparent;
            border-top: 25px solid #ff5722;
        }

        .info-table th {
            background: #f44336;
            color: white;
            padding: 25px 30px;
            font-size: 18px;
            font-weight: 600;
            text-align: left;
            border: none;
        }

        /* Contenido principal - tema bienvenida */
        .contenido-principal {
            padding: 35px 30px;
            background: #ffffff;
            position: relative;
            margin: 20px 0;
            border-radius: 25px;
            border: 2px dashed #e91e63;
        }

        .contenido-principal::before {
            content: '';
            position: absolute;
            top: -20px;
            left: 50px;
            width: 45px;
            height: 35px;
            background: #ff5722;
            border-radius: 50%;
            border: 4px solid #d84315;
        }

        .contenido-principal::after {
            content: '';
            position: absolute;
            bottom: -18px;
            right: 45px;
            width: 40px;
            height: 40px;
            background: #f44336;
            border-radius: 50%;
        }

        .contenido-texto {
            font-size: 16px;
            line-height: 1.8;
            color: #2c3e50;
            text-align: justify;
            margin-bottom: 25px;
        }

        .contenido-texto p {
            margin-bottom: 18px;
        }

        /* Sección de foto del niño - tema amistad */
        .foto-ninio-section {
            background: #fce4ec;
            border-radius: 30px;
            padding: 30px;
            margin: 30px 0;
            text-align: center;
            border: 3px solid #e91e63;
            position: relative;
        }

        .foto-ninio-section::before {
            content: '';
            position: absolute;
            top: -22px;
            left: 35px;
            width: 50px;
            height: 50px;
            background: #f44336;
            border-radius: 50%;
            border: 6px solid #d32f2f;
        }

        .foto-ninio-section::after {
            content: '';
            position: absolute;
            bottom: -20px;
            right: 45px;
            width: 0;
            height: 0;
            border-left: 22px solid transparent;
            border-right: 22px solid transparent;
            border-top: 30px solid #e91e63;
        }

        .foto-ninio-title {
            font-size: 22px;
            font-weight: 700;
            color: #ad1457;
            margin-bottom: 25px;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
        }

        .foto-ninio-title::before {
            content: '';
            position: absolute;
            left: -50px;
            top: 50%;
            margin-top: -18px;
            width: 35px;
            height: 35px;
            background: #e91e63;
            border-radius: 50%;
        }

        .foto-ninio-title::after {
            content: '';
            position: absolute;
            right: -50px;
            top: 50%;
            margin-top: -15px;
            width: 30px;
            height: 30px;
            background: #ff5722;
            border-radius: 50%;
        }

        .foto-ninio-container {
            background: white;
            border-radius: 25px;
            padding: 20px;
            display: inline-block;
            border: 4px solid #f44336;
            position: relative;
        }

        .foto-ninio-container::before {
            content: '';
            position: absolute;
            top: -15px;
            right: -15px;
            width: 30px;
            height: 30px;
            background: #ff5722;
            border-radius: 50%;
            border: 5px solid #d84315;
        }

        .foto-ninio-container::after {
            content: '';
            position: absolute;
            bottom: -18px;
            left: -18px;
            width: 40px;
            height: 30px;
            background: #e91e63;
            border-radius: 50%;
        }

        .foto-ninio {
            width: 300px;
            height: 400px;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            border-radius: 20px;
            border: 2px dashed #e91e63;
        }

        /* Sección de boletas - tema creatividad */
        .boletas-section {
            margin: 40px 0;
            page-break-inside: avoid;
            position: relative;
        }

        .boletas-section::before {
            content: '';
            position: absolute;
            top: -30px;
            left: 30px;
            width: 55px;
            height: 55px;
            background: #e91e63;
            border-radius: 50%;
            border: 6px solid #c2185b;
            opacity: 0.8;
        }

        .boletas-section::after {
            content: '';
            position: absolute;
            top: -25px;
            right: 40px;
            width: 45px;
            height: 45px;
            background: #f44336;
            border-radius: 50%;
            opacity: 0.8;
        }

        .boletas-title {
            font-size: 26px;
            font-weight: 700;
            color: #b71c1c;
            margin-bottom: 25px;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
        }

        .boletas-title::before {
            content: '';
            position: absolute;
            left: -60px;
            top: 50%;
            margin-top: -20px;
            width: 40px;
            height: 40px;
            background: #ff5722;
            border-radius: 50%;
        }

        .boletas-title::after {
            content: '';
            position: absolute;
            right: -60px;
            top: 50%;
            margin-top: -18px;
            width: 35px;
            height: 35px;
            background: #f44336;
            border-radius: 50%;
        }

        .boletas-grid {
            display: block;
            padding: 20px;
            background: #ffebee;
            border-radius: 25px;
            border: 3px dashed #f44336;
            position: relative;
        }

        .boleta-item {
            background: white;
            border-radius: 20px;
            padding: 15px;
            border: 3px solid #e91e63;
            position: relative;
            margin-bottom: 20px;
            display: inline-block;
            width: 300px;
            margin-right: 20px;
            vertical-align: top;
        }

        .boleta-item:nth-child(even) {
            border-color: #f44336;
        }

        .boleta-item:nth-child(3n) {
            border-color: #ff5722;
        }

        .boleta-item::before {
            content: '';
            position: absolute;
            top: -15px;
            right: -15px;
            width: 25px;
            height: 25px;
            background: #f44336;
            border-radius: 50%;
            border: 5px solid #d32f2f;
        }

        .boleta-item::after {
            content: '';
            position: absolute;
            bottom: -12px;
            left: -12px;
            width: 0;
            height: 0;
            border-left: 15px solid transparent;
            border-right: 15px solid transparent;
            border-top: 20px solid #e91e63;
        }

        .boleta-item img {
            width: 100%;
            height: 250px;
            border-radius: 15px;
            background: #ffebee;
            padding: 10px;
            border: 2px dashed #f44336;
        }

        /* Sección de información adicional - tema conocimiento */
        .info-adicional-section {
            background: #fce4ec;
            border-radius: 30px;
            padding: 30px;
            margin: 30px 0;
            border: 3px solid #e91e63;
            position: relative;
        }

        .info-adicional-section::before {
            content: '';
            position: absolute;
            top: -20px;
            left: 75px;
            width: 45px;
            height: 45px;
            background: #ff5722;
            border-radius: 50%;
            border: 5px solid #d84315;
        }

        .info-adicional-section::after {
            content: '';
            position: absolute;
            bottom: -18px;
            right: 65px;
            width: 40px;
            height: 40px;
            background: #e91e63;
            border-radius: 50%;
        }

        .info-adicional-section img {
            width: 350px;
            max-width: 100%;
            height: auto;
            border-radius: 20px;
            border: 3px solid #f44336;
            margin-bottom: 25px;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }

        .info-adicional-section p {
            font-size: 16px;
            line-height: 1.8;
            color: #2c3e50;
            text-align: justify;
            background: white;
            padding: 25px;
            border-radius: 20px;
            border: 3px dashed #e91e63;
            position: relative;
            margin: 0;
        }

        /* Utilidades */
        .no-break {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        /* Separador decorativo */
        .separador {
            margin: 40px 0;
            text-align: center;
            position: relative;
            height: 30px;
        }

        .separador::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 3px;
            background: #f44336;
            margin-top: -1px;
            border-radius: 10px;
        }

        .separador::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            margin-left: -12px;
            margin-top: -12px;
            width: 25px;
            height: 25px;
            background: #ff8cae;
            border-radius: 50%;
            border: 3px solid #ffffff;
        }

    </style>
</head>

<body>
    <!-- Header fijo en la parte superior -->
    <div class="header-fixed">
        <img src="{{ public_path('caratulas/cabecera-agradecimiento.png') }}" 
             alt="Cabecera de carta iniciada">
    </div>

    <!-- Footer fijo en la parte inferior -->
    <div class="footer-fixed">
        <img src="{{ public_path('caratulas/pie-agradecimiento.png') }}" 
             alt="Pie de página de carta iniciada">
    </div>

    <div class="contenedor">
        <!-- Sección de fecha -->
        <div class="fecha-section no-break">
            <h3 class="fecha-title">
                {{ $carta->fecha_respondida_formatted }}
            </h3>
        </div>

        <!-- Información del niño -->
        <div class="info-section no-break">
            <table class="info-table">
                <tr>
                    <th>N° Child: {{ $carta->ninio->numero_child }}</th>
                    <th>Carta de {{ ucfirst($carta->tipo) }}</th>
                </tr>
            </table>
        </div>

        <!-- Contenido principal de la carta -->
        <div class="contenido-principal">
            <div class="contenido-texto">
                {!! $carta->detalle !!}
            </div>
        </div>

        <!-- Sección de foto del niño -->
        @if ($carta->archivo_imagen_ninio)
        <div class="foto-ninio-section no-break">
            <h4 class="foto-ninio-title">Aquí está mi foto</h4>
            <div class="foto-ninio-container">
                <div class="foto-ninio" style="
                    background-image: url({{ public_path($carta->archivo_imagen_ninio_link??'img/no_carta_imagen.png') }});
                "></div>
            </div>
        </div>
        @endif

        <!-- Sección de boletas -->
        @if($carta->archivo_imagen || $carta->boletas->count() > 0)
        <div class="boletas-section no-break">
            <h4 class="boletas-title">Mis Creaciones</h4>
            <div class="boletas-grid">
                @if ($carta->archivo_imagen)
                    <div class="boleta-item">
                        <img src="{{ public_path($carta->archivo_imagen_link??'img/no_carta_imagen.png') }}" 
                             alt="Imagen de la carta" />
                    </div>
                @endif
    
                @if ($carta->boletas->count() > 0)
                    @foreach ($carta->boletas as $boleta)
                        <div class="boleta-item">
                            <img src="{{ public_path($boleta->archivo_imagen_link??'img/no_carta_imagen.png') }}" 
                                 alt="Creación {{ $loop->iteration }}" />
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
        @endif

        <!-- Sección de información adicional -->
        <div class="info-adicional-section no-break">
            <img src="{{ public_path('img/cartas/ccagradecimiento.jpg') }}" 
                 alt="Información sobre cartas digitales">
            <p>With this letter, you will notice something is different that we are very excited
                about. We have introduced the use of tablets and cell phones that allow children
                to express themselves in their letters to you by not just writing, but also by taking
                photos of them and their drawings. We hope you enjoy this new kind of letter as
                much as children are enjoying creating them, and learning about technology in the
                process. Make sure you write back to your sponsor child and keep the
                conversation going!</p>
        </div>

        <!-- Separador decorativo -->
        <div class="separador"></div>
    </div>
</body>

</html>
