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
            background: #f8f5ff;
            color: #2e2838;
            line-height: 1.6;
            position: relative;
        }

        /* Fondo decorativo con tema de agradecimiento */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 15% 25%, rgba(142, 112, 193, 0.06) 0%, transparent 25%),
                radial-gradient(circle at 85% 15%, rgba(122, 85, 171, 0.06) 0%, transparent 25%),
                radial-gradient(circle at 70% 85%, rgba(162, 140, 201, 0.06) 0%, transparent 25%),
                radial-gradient(circle at 25% 75%, rgba(102, 65, 151, 0.06) 0%, transparent 25%);
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

        /* Decoraciones del contenedor - tema agradecimiento */
        .contenedor::before {
            content: '';
            position: absolute;
            top: 160px;
            right: 25px;
            width: 50px;
            height: 50px;
            background: #8e70c1;
            border-radius: 50%;
            border: 4px solid #7a55ab;
            opacity: 0.8;
        }

        .contenedor::after {
            content: '';
            position: absolute;
            top: 180px;
            left: 35px;
            width: 35px;
            height: 35px;
            background: #a28cc9;
            opacity: 0.8;
        }

        /* Sección de fecha - estilo agradecimiento */
        .fecha-section {
            background: #f2f0ff;
            padding: 20px 25px;
            border: 3px solid #8e70c1;
            margin: 15px 0;
            position: relative;
            border-radius: 25px;
            text-align: center;
        }

        .fecha-section::before {
            content: '';
            position: absolute;
            top: -12px;
            left: 30px;
            width: 30px;
            height: 30px;
            background: #8e70c1;
            border-radius: 50%;
            border: 3px solid #664197;
        }

        .fecha-section::after {
            content: '';
            position: absolute;
            bottom: -10px;
            right: 30px;
            width: 25px;
            height: 25px;
            background: #a28cc9;
            border-radius: 50%;
        }

        .fecha-title {
            font-size: 18px;
            font-weight: 700;
            color: #4a2d73;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Información del niño - estilo cálido */
        .info-section {
            margin: 15px 0;
            padding: 0;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            background: #ffffff;
            border: 3px solid #8e70c1;
            border-radius: 20px;
            overflow: hidden;
            position: relative;
        }

        .info-table::before {
            content: '';
            position: absolute;
            top: -15px;
            left: 60px;
            width: 25px;
            height: 25px;
            background: #7a55ab;
            border-radius: 50%;
            border: 3px solid #664197;
        }

        .info-table::after {
            content: '';
            position: absolute;
            bottom: -12px;
            right: 50px;
            width: 0;
            height: 0;
            border-left: 12px solid transparent;
            border-right: 12px solid transparent;
            border-top: 18px solid #a28cc9;
        }

        .info-table th {
            background: #8e70c1;
            color: white;
            padding: 20px 25px;
            font-size: 16px;
            font-weight: 600;
            text-align: left;
            border: none;
        }

        /* Contenido principal - tema gratitud */
        .contenido-principal {
            padding: 35px 30px;
            background: #ffffff;
            position: relative;
            margin: 20px 0;
            border-radius: 25px;
            border: 2px dashed #8e70c1;
        }

        .contenido-principal::before {
            content: '';
            position: absolute;
            top: -12px;
            left: 30px;
            width: 30px;
            height: 20px;
            background: #b8a7d9;
            border-radius: 50%;
        }

        .contenido-principal::after {
            content: '';
            position: absolute;
            bottom: -10px;
            right: 25px;
            width: 25px;
            height: 25px;
            background: #a28cc9;
        }

        .contenido-texto {
            font-size: 16px;
            line-height: 1.8;
            color: #4a2d73;
            text-align: justify;
            margin-bottom: 30px;
            position: relative;
        }

        .contenido-texto p {
            margin-bottom: 15px;
        }

        /* Sección de foto - tema corazón y gratitud */
        .foto-ninio-section {
            background: #f2f0ff;
            border-radius: 30px;
            padding: 30px;
            margin: 30px 0;
            text-align: center;
            border: 3px solid #8e70c1;
            position: relative;
        }

        .foto-ninio-section::before {
            content: '';
            position: absolute;
            top: -15px;
            left: 20px;
            width: 35px;
            height: 35px;
            background: #7a55ab;
            border-radius: 50%;
            border: 4px solid #664197;
        }

        .foto-ninio-section::after {
            content: '';
            position: absolute;
            bottom: -12px;
            right: 30px;
            width: 0;
            height: 0;
            border-left: 15px solid transparent;
            border-right: 15px solid transparent;
            border-top: 20px solid #a28cc9;
        }

        .foto-title {
            font-size: 22px;
            font-weight: 700;
            color: #4a2d73;
            margin-bottom: 25px;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
        }

        .foto-title::before {
            content: '';
            position: absolute;
            left: -30px;
            top: 50%;
            margin-top: -10px;
            width: 20px;
            height: 20px;
            background: #8e70c1;
        }

        .foto-title::after {
            content: '';
            position: absolute;
            right: -35px;
            top: 50%;
            margin-top: -7px;
            width: 25px;
            height: 15px;
            background: #b8a7d9;
            border-radius: 50%;
        }

        .foto-container {
            background: white;
            border-radius: 25px;
            padding: 20px;
            display: inline-block;
            border: 4px solid #8e70c1;
            position: relative;
        }

        .foto-container::before {
            content: '';
            position: absolute;
            top: -8px;
            right: -8px;
            width: 20px;
            height: 20px;
            background: #7a55ab;
            border-radius: 50%;
        }

        .foto-container::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: -10px;
            width: 25px;
            height: 15px;
            background: #a28cc9;
            border-radius: 50%;
        }

        .foto-ninio {
            width: 300px;
            height: 400px;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            border-radius: 20px;
            border: 2px dashed #8e70c1;
        }

        /* Sección de boletas - tema regalo */
        .boletas-section {
            margin: 40px 0;
            page-break-inside: avoid;
            position: relative;
        }

        .boletas-section::before {
            content: '';
            position: absolute;
            top: -20px;
            left: 15px;
            width: 40px;
            height: 40px;
            background: #b8a7d9;
            border-radius: 50% 30% 60% 40%;
            transform: rotate(-25deg);
            opacity: 0.7;
        }

        .boletas-section::after {
            content: '';
            position: absolute;
            top: -15px;
            right: 25px;
            width: 30px;
            height: 30px;
            background: #a28cc9;
            clip-path: polygon(25% 0%, 75% 0%, 100% 50%, 75% 100%, 25% 100%, 0% 50%);
            transform: rotate(15deg);
            opacity: 0.7;
        }

        .boletas-title {
            font-size: 24px;
            font-weight: 700;
            color: #8e70c1;
            margin-bottom: 25px;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 1px;
            transform: rotate(-0.3deg);
            position: relative;
        }

        .boletas-title::before {
            content: '';
            position: absolute;
            left: -40px;
            top: 50%;
            transform: translateY(-50%) rotate(20deg);
            width: 25px;
            height: 25px;
            background: #8e70c1;
            border-radius: 50% 30% 70% 20%;
        }

        .boletas-title::after {
            content: '';
            position: absolute;
            right: -45px;
            top: 50%;
            transform: translateY(-50%) rotate(-40deg);
            width: 20px;
            height: 20px;
            background: #b8a7d9;
            clip-path: polygon(50% 0%, 0% 100%, 100% 100%);
        }

        .boletas-grid {
            display: block;
            padding: 20px;
            background: #f2f0ff;
            border-radius: 25px;
            border: 3px dashed #8e70c1;
            position: relative;
        }

        .boleta-item {
            background: white;
            border-radius: 20px;
            padding: 15px;
            border: 3px solid #8e70c1;
            position: relative;
            margin-bottom: 20px;
            display: inline-block;
            width: 300px;
            margin-right: 20px;
            vertical-align: top;
        }

        .boleta-item:nth-child(even) {
            border-color: #a28cc9;
        }

        .boleta-item:nth-child(3n) {
            border-color: #7a55ab;
        }

        .boleta-item::before {
            content: '';
            position: absolute;
            top: -8px;
            right: -8px;
            width: 16px;
            height: 16px;
            background: #664197;
            border-radius: 50%;
            border: 2px solid #4a2d73;
        }

        .boleta-item::after {
            content: '';
            position: absolute;
            bottom: -6px;
            left: -6px;
            width: 0;
            height: 0;
            border-left: 8px solid transparent;
            border-right: 8px solid transparent;
            border-top: 12px solid #8e70c1;
        }

        .boleta-item img {
            width: 100%;
            height: 250px;
            border-radius: 15px;
            background: #f8f5ff;
            padding: 10px;
            border: 2px dashed #a28cc9;
        }

        /* Información adicional - tema corazón */
        .info-adicional {
            background: #f2f0ff;
            border-radius: 30px;
            padding: 30px;
            margin: 30px 0;
            border: 3px solid #8e70c1;
            position: relative;
        }

        .info-adicional::before {
            content: '';
            position: absolute;
            top: -12px;
            left: 50px;
            width: 30px;
            height: 30px;
            background: #8e70c1;
            border-radius: 50%;
        }

        .info-adicional::after {
            content: '';
            position: absolute;
            bottom: -10px;
            right: 40px;
            width: 25px;
            height: 25px;
            background: #b8a7d9;
        }

        .info-adicional img {
            width: 350px;
            max-width: 100%;
            height: auto;
            display: block;
            margin: 0 auto 25px;
            border-radius: 20px;
            border: 3px solid #8e70c1;
        }

        .info-adicional p {
            font-size: 16px;
            line-height: 1.8;
            color: #4a2d73;
            text-align: justify;
            background: white;
            padding: 25px;
            border-radius: 20px;
            border: 3px dashed #8e70c1;
            position: relative;
        }

        .info-adicional p::before {
            content: '';
            position: absolute;
            top: -8px;
            right: 20px;
            width: 20px;
            height: 20px;
            background: #b8a7d9;
            border-radius: 50%;
        }

        /* Separador decorativo - tema gratitud */
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
            background: #8e70c1;
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
            background: #b8a7d9;
            border-radius: 50%;
            border: 3px solid #ffffff;
        }

        /* Utilidades */
        .no-break {
            page-break-inside: avoid;
            break-inside: avoid;
        }

    </style>
</head>

<body>
    <!-- Header fijo en la parte superior -->
    <div class="header-fixed">
        <img src="{{ public_path('caratulas/cabecera-agradecimiento.png') }}" 
             alt="Cabecera de agradecimiento">
    </div>

    <!-- Footer fijo en la parte inferior -->
    <div class="footer-fixed">
        <img src="{{ public_path('caratulas/pie-agradecimiento.png') }}" 
             alt="Pie de página de agradecimiento">
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
        <div class="foto-ninio-section no-break">
            <h4 class="foto-title">Aquí está mi foto</h4>
            <div class="foto-container">
                <div class="foto-ninio" style="
                    background-image: url({{ public_path($carta->archivo_imagen_ninio_link??'images/no-carta-imagen.png') }});
                "></div>
            </div>
        </div>

        <!-- Sección de boletas -->
        @if($carta->archivo_imagen || $carta->boletas->count() > 0)
        <div class="boletas-section no-break">
            <h4 class="boletas-title">Mis Dibujos y Boletas</h4>
            <div class="boletas-grid">
                @if ($carta->archivo_imagen)
                    <div class="boleta-item">
                        <img src="{{ public_path($carta->archivo_imagen_link??'images/no-carta-imagen.png') }}" 
                             alt="Imagen de la carta" />
                    </div>
                @endif
    
                @if ($carta->boletas->count() > 0)
                    @foreach ($carta->boletas as $boleta)
                        <div class="boleta-item">
                            <img src="{{ public_path($boleta->archivo_imagen_link??'images/no-carta-imagen.png') }}" 
                                 alt="Boleta {{ $loop->iteration }}" />
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
        @endif

        <!-- Información adicional -->
        <div class="info-adicional no-break">
            <img src="{{ public_path('img/cartas/ccagradecimiento.jpg') }}" 
                 alt="Imagen de agradecimiento">
            <p>
                <strong>With this letter, you will notice something is different that we are very excited about.</strong> We have introduced the use of tablets and cell phones that allow children to express themselves in their letters to you by not just writing, but also by taking photos of them and their drawings. We hope you enjoy this new kind of letter as much as children are enjoying creating them, and learning about technology in the process. <strong>Make sure you write back to your sponsor child and keep the conversation going!</strong>
            </p>
        </div>

        <!-- Separador decorativo -->
        <div class="separador"></div>
    </div>
</body>

</html>
