{{--
    Celda con una foto del niño (o de su familia) en el PDF de una carta.

    Antes la foto se ponía como fondo de la celda con `background-size` fijo,
    y eso la deformaba: una foto horizontal se estiraba a vertical. Ahora es
    un <img> que entra en el recuadro sin perder su proporción.

    Parámetros:
    - $ruta:   ruta pública de la imagen (o null para la imagen de reemplazo)
    - $ancho:  ancho del recuadro en px
    - $alto:   alto del recuadro en px
    - $estilo: (opcional) CSS extra para la celda, p. ej. un borde
--}}
<td style="width: {{ $ancho }}px; height: {{ $alto }}px; text-align: center; vertical-align: middle; {{ $estilo ?? '' }}">
    <img src="{{ public_path($ruta ?? 'img/no_carta_imagen.png') }}"
         style="width: auto; height: auto; max-width: {{ $ancho }}px; max-height: {{ $alto }}px; display: block; margin: 0 auto;">
</td>
