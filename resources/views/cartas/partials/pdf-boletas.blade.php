{{--
    Bloque "Boletas" del PDF de una carta. Lo usan las 4 plantillas pdf-*.

    - Junta la imagen principal de la carta (archivo_imagen) y las boletas en
      una sola lista y la reparte en filas de 2. Antes el salto de fila solo
      contaba las boletas, y la primera fila podía quedar con 3 imágenes.
    - Cada fila es un bloque propio con "no cortar", así una imagen nunca queda
      partida entre dos hojas y el bloque no salta entero a la hoja siguiente.
    - Las imágenes conservan su proporción: max-width/max-height con tamaño
      automático. wkhtmltopdf 0.12 no soporta object-fit.

    El color de fondo de la tabla lo pone cada plantilla con `.cuerpo table`.
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

        .boletas-pdf-fila table {
            width: 100%;
            table-layout: fixed;
        }

        .boletas-pdf-fila td {
            width: 50%;
            height: 210px;
            text-align: center;
            vertical-align: middle;
        }

        .boletas-pdf-fila td img {
            width: auto;
            height: auto;
            max-width: 100%;
            max-height: 200px;
            display: block;
            margin: 0 auto;
        }
    </style>

    @foreach ($imagenesBoletas->chunk(2) as $fila)
        <div class="boletas-pdf-fila">
            @if ($loop->first)
                <h4>Boletas</h4>
            @endif
            <table>
                <tr>
                    @foreach ($fila as $ruta)
                        <td><img src="{{ public_path($ruta ?? 'img/no_carta_imagen.png') }}"></td>
                    @endforeach
                    @if ($fila->count() === 1)
                        <td></td>
                    @endif
                </tr>
            </table>
        </div>
    @endforeach
@endif
