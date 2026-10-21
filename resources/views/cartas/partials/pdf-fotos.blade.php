{{--
    Fotos del niño (y de su familia, en Presentación) en el PDF de una carta.

    Cada foto lleva encima su título decorativo y va en un marco blanco con
    esquinas redondeadas y sombra suave, sin fondo de color. La foto conserva
    su proporción y entra en el recuadro máximo ($ancho × $alto). La sombra se
    dibuja aparte y no baja la resolución de la foto dentro del PDF.

    El bloque completo no se corta entre hojas.

    Parámetros:
    - $fotos:      lista de ['titulo' => ..., 'ruta' => ...] (ruta null = imagen de reemplazo)
    - $ancho, $alto: tamaño máximo de cada foto en px
    - $color, $colorTexto: colores del título (ver pdf-titulo-seccion)
--}}
<div style="page-break-inside: avoid; break-inside: avoid; margin: 24px 0;">
    <table style="margin: 0 auto; width: auto; background: none; border-collapse: separate;">
        <tr>
            @foreach ($fotos as $foto)
                <td style="width: auto; height: auto; padding: 0 12px; vertical-align: top; text-align: center; background: none;">
                    @include('cartas.partials.pdf-titulo-seccion', ['texto' => $foto['titulo'], 'color' => $color, 'colorTexto' => $colorTexto ?? null])
                    <div style="display: inline-block; padding: 7px; background: #ffffff; border-radius: 14px; -webkit-box-shadow: 0 6px 18px rgba(0, 0, 0, 0.28); box-shadow: 0 6px 18px rgba(0, 0, 0, 0.28);">
                        <img src="{{ public_path($foto['ruta'] ?? 'img/no_carta_imagen.png') }}"
                             style="display: block; width: auto; height: auto; max-width: {{ $ancho }}px; max-height: {{ $alto }}px; margin: 0; border-radius: 9px;">
                    </div>
                </td>
            @endforeach
        </tr>
    </table>
</div>
