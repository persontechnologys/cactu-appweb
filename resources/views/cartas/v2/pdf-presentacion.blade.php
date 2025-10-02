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
            background: #f0f9fa;
            color: #2c3e50;
            line-height: 1.6;
            position: relative;
        }

        /* Fondo decorativo con tema de presentación */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 10% 20%, rgba(4, 154, 173, 0.08) 0%, transparent 30%),
                radial-gradient(circle at 90% 10%, rgba(34, 184, 207, 0.08) 0%, transparent 30%),
                radial-gradient(circle at 80% 90%, rgba(54, 162, 235, 0.08) 0%, transparent 30%),
                radial-gradient(circle at 20% 80%, rgba(2, 136, 153, 0.08) 0%, transparent 30%);
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

        /* Decoraciones del contenedor - tema presentación */
        .contenedor::before {
            content: '';
            position: absolute;
            top: 160px;
            right: 30px;
            width: 55px;
            height: 55px;
            background: #049aad;
            border-radius: 50%;
            border: 4px solid #028899;
            opacity: 0.7;
        }

        .contenedor::after {
            content: '';
            position: absolute;
            top: 180px;
            left: 40px;
            width: 40px;
            height: 40px;
            background: #22b8cf;
            border-radius: 50%;
            opacity: 0.7;
        }

        /* Sección de fecha - estilo presentación */
        .fecha-section {
            background: #e6f7f8;
            padding: 22px 28px;
            border: 4px solid #049aad;
            margin: 18px 0;
            position: relative;
            border-radius: 20px;
            text-align: center;
        }

        .fecha-section::before {
            content: '';
            position: absolute;
            top: -15px;
            left: 35px;
            width: 35px;
            height: 35px;
            background: #22b8cf;
            border-radius: 50%;
            border: 4px solid #028899;
        }

        .fecha-section::after {
            content: '';
            position: absolute;
            bottom: -12px;
            right: 35px;
            width: 30px;
            height: 30px;
            background: #36a2eb;
            border-radius: 50%;
            border: 3px solid #1e88c7;
        }

        .fecha-title {
            font-size: 19px;
            font-weight: 700;
            color: #025c6b;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Información del niño - estilo conocimiento */
        .info-section {
            margin: 18px 0;
            padding: 0;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            background: #ffffff;
            border: 3px solid #049aad;
            border-radius: 20px;
            overflow: hidden;
            position: relative;
        }

        .info-table::before {
            content: '';
            position: absolute;
            top: -18px;
            left: 70px;
            width: 30px;
            height: 30px;
            background: #06bbd6;
            border-radius: 50%;
            border: 4px solid #048a9a;
        }

        .info-table::after {
            content: '';
            position: absolute;
            bottom: -15px;
            right: 60px;
            width: 0;
            height: 0;
            border-left: 15px solid transparent;
            border-right: 15px solid transparent;
            border-top: 22px solid #0ac4dd;
        }

        .info-table th {
            background: #049aad;
            color: white;
            padding: 22px 28px;
            font-size: 17px;
            font-weight: 600;
            text-align: left;
            border: none;
        }

        /* Contenido principal - tema encuentro */
        .contenido-principal {
            padding: 35px 30px;
            background: #ffffff;
            position: relative;
            margin: 20px 0;
            border-radius: 25px;
            border: 2px dashed #06bbd6;
        }

        .contenido-principal::before {
            content: '';
            position: absolute;
            top: -18px;
            left: 45px;
            width: 40px;
            height: 30px;
            background: #0ac4dd;
            border-radius: 50%;
            border: 3px solid #048a9a;
        }

        .contenido-principal::after {
            content: '';
            position: absolute;
            bottom: -15px;
            right: 40px;
            width: 35px;
            height: 35px;
            background: #0ed7f2;
            border-radius: 50%;
        }

        .contenido-texto {
            font-size: 16px;
            line-height: 1.8;
            color: #2c3e50;
            text-align: justify;
            margin-bottom: 22px;
        }

        .contenido-texto p {
            margin-bottom: 15px;
        }

        /* Sección de fotos - tema familia y presentación */
        .fotos-section {
            background: #e8f9fb;
            border-radius: 30px;
            padding: 30px;
            margin: 30px 0;
            text-align: center;
            border: 3px solid #0ed7f2;
            position: relative;
        }

        .fotos-section::before {
            content: '';
            position: absolute;
            top: -20px;
            left: 30px;
            width: 45px;
            height: 45px;
            background: #049aad;
            border-radius: 50%;
            border: 5px solid #037a89;
        }

        .fotos-section::after {
            content: '';
            position: absolute;
            bottom: -18px;
            right: 40px;
            width: 0;
            height: 0;
            border-left: 20px solid transparent;
            border-right: 20px solid transparent;
            border-top: 28px solid #06bbd6;
        }

        .fotos-title {
            font-size: 21px;
            font-weight: 700;
            color: #036d7a;
            margin-bottom: 22px;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
        }

        .fotos-title::before {
            content: '';
            position: absolute;
            left: -45px;
            top: 50%;
            margin-top: -15px;
            width: 30px;
            height: 30px;
            background: #06bbd6;
            border-radius: 50%;
        }

        .fotos-title::after {
            content: '';
            position: absolute;
            right: -45px;
            top: 50%;
            margin-top: -12px;
            width: 35px;
            height: 25px;
            background: #0ac4dd;
            border-radius: 50%;
        }

        .fotos-container {
            display: block;
            text-align: center;
        }

        .foto-item {
            background: white;
            border-radius: 25px;
            padding: 20px;
            display: inline-block;
            border: 4px solid #049aad;
            position: relative;
            margin: 0 15px 20px 15px;
            vertical-align: top;
        }

        .foto-item:nth-child(even) {
            border-color: #06bbd6;
        }

        .foto-item::before {
            content: '';
            position: absolute;
            top: -12px;
            right: -12px;
            width: 28px;
            height: 28px;
            background: #0ac4dd;
            border-radius: 50%;
            border: 4px solid #048a9a;
        }

        .foto-item::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: -15px;
            width: 35px;
            height: 25px;
            background: #0ed7f2;
            border-radius: 50%;
        }

        .foto-ninio, .foto-familia {
            width: 400px;
            height: 300px;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            border-radius: 20px;
            border: 2px dashed #06bbd6;
        }

        .foto-familia {
            height: 500px;
        }

        .foto-label {
            font-size: 18px;
            font-weight: 700;
            color: #036d7a;
            margin-bottom: 15px;
            text-transform: uppercase;
        }

        /* Sección de boletas - tema descubrimiento */
        .boletas-section {
            margin: 35px 0;
            page-break-inside: avoid;
            position: relative;
        }

        .boletas-section::before {
            content: '';
            position: absolute;
            top: -28px;
            left: 25px;
            width: 50px;
            height: 50px;
            background: #06bbd6;
            border-radius: 50%;
            border: 5px solid #048a9a;
            opacity: 0.8;
        }

        .boletas-section::after {
            content: '';
            position: absolute;
            top: -22px;
            right: 35px;
            width: 40px;
            height: 40px;
            background: #0ed7f2;
            border-radius: 50%;
            opacity: 0.8;
        }

        .boletas-title {
            font-size: 24px;
            font-weight: 700;
            color: #025c6b;
            margin-bottom: 22px;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
        }

        .boletas-title::before {
            content: '';
            position: absolute;
            left: -55px;
            top: 50%;
            margin-top: -18px;
            width: 35px;
            height: 35px;
            background: #0ac4dd;
            border-radius: 50%;
        }

        .boletas-title::after {
            content: '';
            position: absolute;
            right: -55px;
            top: 50%;
            margin-top: -15px;
            width: 30px;
            height: 30px;
            background: #049aad;
            border-radius: 50%;
        }

        .boletas-grid {
            display: block;
            padding: 20px;
            background: #e8f9fb;
            border-radius: 25px;
            border: 3px dashed #049aad;
            position: relative;
        }

        .boleta-item {
            background: white;
            border-radius: 20px;
            padding: 15px;
            border: 3px solid #06bbd6;
            position: relative;
            margin-bottom: 20px;
            display: inline-block;
            width: 300px;
            margin-right: 20px;
            vertical-align: top;
        }

        .boleta-item:nth-child(even) {
            border-color: #0ed7f2;
        }

        .boleta-item:nth-child(3n) {
            border-color: #0ac4dd;
        }

        .boleta-item::before {
            content: '';
            position: absolute;
            top: -12px;
            right: -12px;
            width: 22px;
            height: 22px;
            background: #049aad;
            border-radius: 50%;
            border: 4px solid #037a89;
        }

        .boleta-item::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: -10px;
            width: 0;
            height: 0;
            border-left: 12px solid transparent;
            border-right: 12px solid transparent;
            border-top: 18px solid #06bbd6;
        }

        .boleta-item img {
            width: 100%;
            height: 250px;
            border-radius: 15px;
            background: #e8f9fb;
            padding: 10px;
            border: 2px dashed #049aad;
        }

        /* Sección de provincia - tema lugar de origen */
        .provincia-section {
            background: #e8f9fb;
            border-radius: 30px;
            padding: 30px;
            margin: 30px 0;
            border: 3px solid #049aad;
            position: relative;
            text-align: center;
        }

        .provincia-section::before {
            content: '';
            position: absolute;
            top: -18px;
            left: 70px;
            width: 40px;
            height: 40px;
            background: #0ac4dd;
            border-radius: 50%;
            border: 4px solid #048a9a;
        }

        .provincia-section::after {
            content: '';
            position: absolute;
            bottom: -15px;
            right: 60px;
            width: 35px;
            height: 35px;
            background: #06bbd6;
            border-radius: 50%;
        }

        .provincia-section img {
            width: 100%;
            max-width: 100%;
            height: auto;
            border-radius: 20px;
            border: 3px solid #0ed7f2;
            display: block;
            margin: 0 auto;
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
            background: #049aad;
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
            background: #06bbd6;
            border-radius: 50%;
            border: 3px solid #ffffff;
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

        <!-- Separador decorativo -->
        <div class="separador"></div>
    </div>
</body>

</html>
