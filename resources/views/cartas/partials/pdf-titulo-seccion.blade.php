{{--
    Título decorativo centrado de una sección del PDF ("Aquí está mi foto",
    "Boletas"...): una etiqueta redondeada con el color de la plantilla y una
    línea fina a cada lado.

    Usa elementos inline-block (wkhtmltopdf 0.12 no soporta flexbox). No usa
    tabla, porque una tabla de ancho automático apretaba la etiqueta y cortaba
    el texto.

    Parámetros:
    - $texto
    - $color:      color de la etiqueta y de las líneas
    - $colorTexto: (opcional) color de la letra, blanco por defecto
--}}
<div style="text-align: center; margin: 0 0 14px 0; white-space: nowrap; letter-spacing: normal;">
    <span style="display: inline-block; width: 46px; height: 2px; background: {{ $color }}; vertical-align: middle;"></span>
    <span style="display: inline-block; margin: 0 8px; padding: 6px 24px; border-radius: 20px; background: {{ $color }}; color: {{ $colorTexto ?? '#ffffff' }}; font-weight: bold; font-size: 15px; letter-spacing: 0; vertical-align: middle;">{{ $texto }}</span>
    <span style="display: inline-block; width: 46px; height: 2px; background: {{ $color }}; vertical-align: middle;"></span>
</div>
