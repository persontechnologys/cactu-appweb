{{--
    Bloque "Boletas" del PDF de una carta. Lo usan las 4 plantillas pdf-*.

    - Junta la imagen principal de la carta (archivo_imagen) y las boletas en
      una sola lista y la reparte en filas de 2. Antes el salto de fila solo
      contaba las boletas, y la primera fila podía quedar con 3 imágenes.
    - Cada fila es un bloque propio con "no cortar", así una imagen nunca queda
      partida entre dos hojas y el bloque no salta entero a la hoja siguiente.
    - Sin fondo de color: cada imagen va en un marco blanco con esquinas
      redondeadas y sombra suave (pdf-marco), igual que la foto del niño.
    - Las imágenes conservan su proporción: max-width/max-height con tamaño
      automático. wkhtmltopdf 0.12 no soporta object-fit.

    Parámetros:
    - $carta
    - $color, $colorTexto: colores del título (ver pdf-titulo-seccion)
--}}
@php
    $imagenesBoletas = collect();
    if ($carta->archivo_imagen) {
        $imagenesBoletas->push($carta->archivo_imagen_link);
    }
    foreach ($carta->boletas as $boleta) {
        $imagenesBoletas->push($boleta->archivo_imagen_link);
    }
@endphp

@if ($imagenesBoletas->isNotEmpty())
    <style>
        .boletas-pdf-fila {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .boletas-pdf-fila table.boletas-pdf-tabla {
            width: 100%;
            table-layout: fixed;
            background: none;
            border-collapse: separate;
        }

        .boletas-pdf-fila td.boletas-pdf-celda {
            width: 50%;
            height: auto;
            padding: 12px;
            text-align: center;
            vertical-align: middle;
            background: none;
        }

    </style>

    @foreach ($imagenesBoletas->chunk(2) as $fila)
        <div class="boletas-pdf-fila">
            @if ($loop->first)
                <div style="margin-top: 20px;">
                    @include('cartas.partials.pdf-titulo-seccion', ['texto' => 'Boletas', 'color' => $color, 'colorTexto' => $colorTexto ?? null])
                </div>
            @endif
            <table class="boletas-pdf-tabla">
                <tr>
                    @foreach ($fila as $ruta)
                        <td class="boletas-pdf-celda">
                            @include('cartas.partials.pdf-marco', ['ruta' => $ruta, 'ancho' => 290, 'alto' => 200])
                        </td>
                    @endforeach
                    @if ($fila->count() === 1)
                        <td class="boletas-pdf-celda"></td>
                    @endif
                </tr>
            </table>
        </div>
    @endforeach
@endif
