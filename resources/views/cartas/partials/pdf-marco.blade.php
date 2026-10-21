{{--
    Imagen en un marco blanco con esquinas redondeadas y una sombra suave.
    Lo usan pdf-fotos (foto del niño / familia) y pdf-boletas.

    La sombra NO usa box-shadow: wkhtmltopdf la guarda como una imagen
    semitransparente y algunos visores de PDF (Chrome, Acrobat) dibujan una
    línea gris fina en su borde. Aquí la sombra son 3 anillos de borde en gris
    cada vez más claro, más gruesos abajo para dar sensación de profundidad.
    Son trazos vectoriales, sin imagen, y no dejan marcas.

    La imagen conserva su proporción dentro del tamaño máximo.

    Parámetros:
    - $ruta:  ruta pública de la imagen (null = imagen de reemplazo)
    - $ancho, $alto: tamaño máximo de la imagen en px
--}}
<div style="display: inline-block; padding: 1px 1px 3px 1px; background: #f2f2f2; border-radius: 17px;">
    <div style="padding: 1px 1px 2px 1px; background: #e4e4e4; border-radius: 16px;">
        <div style="padding: 1px; background: #d6d6d6; border-radius: 15px;">
            <div style="padding: 6px; background: #ffffff; border-radius: 14px;">
                <img src="{{ public_path($ruta ?? 'img/no_carta_imagen.png') }}"
                     style="display: block; width: auto; height: auto; max-width: {{ $ancho }}px; max-height: {{ $alto }}px; margin: 0; border-radius: 9px;">
            </div>
        </div>
    </div>
</div>
