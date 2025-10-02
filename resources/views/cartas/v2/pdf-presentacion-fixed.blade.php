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
            background: #f0f8ff;
            color: #2c3e50;
            line-height: 1.6;
            position: relative;
        }

        /* Fondo decorativo suave */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 80% 20%, rgba(4, 154, 173, 0.08) 0%, transparent 20%),
                radial-gradient(circle at 30% 70%, rgba(4, 154, 173, 0.06) 0%, transparent 20%);
            pointer-events: none;
            z-index: -1;
        }

        /* Elemento flotante suave */
        body::after {
            content: '';
            position: absolute;
            top: 80px;
            right: 50px;
            width: 35px;
            height: 25px;
            background: #b3e5fc;
            border-radius: 15px 15px 15px 3px;
            border: 2px solid #81d4fa;
            z-index: -1;
            opacity: 0.5;
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
            background-image: 
                linear-gradient(to bottom, transparent 0px, transparent 25px, rgba(129, 212, 250, 0.02) 25px, rgba(129, 212, 250, 0.02) 26px, transparent 26px);
            background-size: 30px 26px;
        }

        /* Decoraciones del contenedor - suavizadas */
        .contenedor::before {
            content: '';
            position: absolute;
            top: 150px;
            right: 20px;
            width: 60px;
            height: 60px;
            background: #81d4fa;
            border-radius: 50%;
            opacity: 0.6;
            border: 3px dashed #64b5f6;
        }

        .contenedor::after {
            content: '';
            position: absolute;
            top: 200px;
            left: 30px;
            width: 40px;
            height: 40px;
            background: #64b5f6;
            opacity: 0.6;
            border-radius: 15px 15px 15px 3px;
            border: 2px solid #42a5f5;
        }

        /* Sección de fecha - suavizada */
        .fecha-section {
            background: #e6f3ff;
            padding: 25px 30px;
            border-left: 4px solid #81d4fa;
            margin: 20px 0;
            position: relative;
            border-radius: 15px;
            box-shadow: 2px 2px 8px rgba(0,0,0,0.1);
            border-right: 2px dashed #64b5f6;
            text-align: center;
        }

        /* Flecha de respuesta */
        .fecha-section::before {
            content: '';
            position: absolute;
            top: 50%;
            left: -15px;
            margin-top: -8px;
            width: 0;
            height: 0;
            border-top: 8px solid transparent;
            border-bottom: 8px solid transparent;
            border-right: 12px solid #81d4fa;
        }

        /* Marca de "respondido" */
        .fecha-section::after {
            content: '';
            position: absolute;
            top: -8px;
            right: 15px;
            width: 25px;
            height: 25px;
            background: #81d4fa;
            border-radius: 50%;
            border: 3px solid #ffffff;
        }

        .fecha-title {
            font-size: 18px;
            font-weight: 700;
            color: #5ba8d4;
            margin: 0;
            text-align: center;
        }

        /* Información del niño */
        .info-section {
            margin: 20px 0;
            padding: 0;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            background: #ffffff;
            border: 3px solid #81d4fa;
            border-radius: 20px;
            overflow: hidden;
            position: relative;
        }

        /* Globo de diálogo decorativo */
        .info-table::before {
            content: '';
            position: absolute;
            top: -15px;
            left: 50px;
            width: 30px;
            height: 20px;
            background: #b3e5fc;
            border-radius: 15px 15px 15px 3px;
            border: 2px solid #81d4fa;
        }

        /* Flecha de intercambio */
        .info-table::after {
            content: '';
            position: absolute;
            bottom: -15px;
            right: 40px;
            width: 25px;
            height: 3px;
            background: #81d4fa;
            border-radius: 2px;
        }

        .info-table th {
            background: #81d4fa;
            color: white;
            padding: 18px 25px;
            font-size: 16px;
            font-weight: 600;
            text-align: left;
            border: none;
        }

        /* Contenido principal - simplificado */
        .contenido-principal {
            padding: 30px 25px;
            background: #ffffff;
            position: relative;
            margin: 20px 0;
            background-image: 
                linear-gradient(to right, transparent 0px, transparent 25px, rgba(129, 212, 250, 0.02) 25px, rgba(129, 212, 250, 0.02) 26px, transparent 26px);
            background-size: 26px 30px;
        }

        /* Globo de pensamiento */
        .contenido-principal::before {
            content: '';
            position: absolute;
            top: -25px;
            right: 40px;
            width: 40px;
            height: 25px;
            background: #b3e5fc;
            border-radius: 20px 20px 20px 5px;
            border: 2px solid #81d4fa;
        }

        /* Serie de burbujas de pensamiento */
        .contenido-principal::after {
            content: '';
            position: absolute;
            top: -10px;
            right: 20px;
            width: 8px;
            height: 8px;
            background: #b3e5fc;
            border-radius: 50%;
            border: 1px solid #81d4fa;
            box-shadow: 
                -15px 5px 0 -2px #b3e5fc, 
                -15px 5px 0 -1px #81d4fa,
                -25px 10px 0 -3px #b3e5fc, 
                -25px 10px 0 -2px #81d4fa;
        }

        .contenido-texto {
            font-size: 16px;
            line-height: 1.8;
            color: #2c3e50;
            text-align: justify;
        }

        .contenido-texto p {
            margin-bottom: 15px;
        }

        /* Sección de fotos - simplificada */
        .fotos-section {
            background: #f5f5f5;
            border: 3px solid #81d4fa;
            border-radius: 15px;
            padding: 25px;
            margin: 25px 0;
            text-align: center;
            position: relative;
        }

        /* Marco decorativo superior */
        .fotos-section::before {
            content: '';
            position: absolute;
            top: -12px;
            left: 30px;
            width: 30px;
            height: 30px;
            background: #81d4fa;
            border-radius: 50%;
            border: 3px solid #ffffff;
        }

        /* Elemento de corazón */
        .fotos-section::after {
            content: '';
            position: absolute;
            bottom: -12px;
            right: 30px;
            width: 20px;
            height: 18px;
            background: #ffb3ba;
            border-radius: 10px 10px 0 0;
            transform: rotate(-45deg);
            border: 2px solid #ff9aa2;
        }

        .fotos-title {
            font-size: 19px;
            font-weight: 700;
            color: #42a5f5;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
        }

        .fotos-container {
            display: block;
            text-align: center;
        }

        .foto-item {
            background: white;
            border: 3px solid #81d4fa;
            border-radius: 15px;
            padding: 15px;
            display: inline-block;
            position: relative;
            margin: 0 10px 15px 10px;
            vertical-align: top;
        }

        .foto-ninio, .foto-familia {
            width: 400px;
            height: 300px;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            border: 3px solid #64b5f6;
            border-radius: 10px;
        }

        .foto-familia {
            height: 500px;
        }

        .foto-label {
            font-size: 16px;
            font-weight: 600;
            color: #42a5f5;
            margin-bottom: 15px;
        }

        /* Sección de boletas - simplificada */
        .boletas-section {
            margin: 25px 0;
            page-break-inside: avoid;
            position: relative;
        }

        .boletas-title {
            font-size: 20px;
            font-weight: 700;
            color: #42a5f5;
            margin-bottom: 20px;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .boletas-grid {
            display: block;
            padding: 20px;
            background: #f8f9fa;
            border: 3px solid #81d4fa;
            border-radius: 15px;
        }

        .boleta-item {
            background: white;
            border: 3px solid #81d4fa;
            border-radius: 10px;
            padding: 15px;
            position: relative;
            margin-bottom: 20px;
            display: inline-block;
            width: 300px;
            margin-right: 20px;
            vertical-align: top;
        }

        .boleta-item img {
            width: 100%;
            height: 250px;
            border: 2px solid #64b5f6;
            border-radius: 8px;
            background: #f8f9fa;
            padding: 10px;
        }

        /* Sección de provincia - simplificada */
        .provincia-section {
            background: #f8f9fa;
            border: 3px solid #81d4fa;
            border-radius: 15px;
            padding: 25px;
            margin: 25px 0;
            position: relative;
            text-align: center;
        }

        .provincia-section img {
            width: 100%;
            max-width: 600px;
            height: auto;
            border: 3px solid #64b5f6;
            border-radius: 10px;
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
             alt="Cabecera de presentación">
    </div>

    <!-- Footer fijo en la parte inferior -->
    <div class="footer-fixed">
        <img src="{{ public_path('caratulas/pie-agradecimiento.png') }}" 
             alt="Pie de página de presentación">
    </div>

    <div class="contenedor">
        <!-- Sección de fecha -->
        <div class="fecha-section no-break">
            <h3 class="fecha-title">
                {{ \Carbon\Carbon::parse($carta->fecha_respondida)->format('d \d\e F \d\e Y') }}
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

        <!-- Sección de fotos del niño y familia -->
        <div class="fotos-section no-break">
            <h4 class="fotos-title">Mis Fotos</h4>
            <div class="fotos-container">
                @if ($carta->archivo_imagen_ninio)
                    <div class="foto-item">
                        <div class="foto-label">Aquí está mi foto</div>
                        <div class="foto-ninio" style="
                            background-image: url({{ public_path($carta->archivo_imagen_ninio_link??'images/no-carta-imagen.png') }});
                        "></div>
                    </div>
                @endif

                @if ($carta->archivo_familia_ninio)
                    <div class="foto-item">
                        <div class="foto-label">Esta es mi familia</div>
                        <div class="foto-familia" style="
                            background-image: url({{ public_path($carta->archivo_familia_ninio_link??'images/no-carta-imagen.png') }});
                        "></div>
                    </div>
                @endif
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

        <!-- Sección de provincia -->
        <div class="provincia-section no-break">
            @if ($carta->gestor->provincia == 'COTOPAXI')
                <img src="{{ public_path('img/cartas/cotopaxi.png') }}" 
                     alt="Provincia de Cotopaxi">
            @else
                <img src="{{ public_path('img/cartas/tungurahua.png') }}" 
                     alt="Provincia de Tungurahua">
            @endif
        </div>
    </div>
</body>

</html>