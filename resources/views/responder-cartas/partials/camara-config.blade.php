{{--
    Configuración común de la cámara web (jslib-html5-camera-photo) para las
    vistas de respuesta de cartas. Se incluye justo después de la librería.

    - Resolución: sin pedirla, el navegador usa la suya por defecto (normalmente
      640×480) y la foto se ve borrosa en el PDF. Se pide 1920×1080 como "ideal":
      el navegador da la más cercana que soporte la cámara.
    - Formato: JPG con calidad 0.85 en lugar de PNG. Una foto de cámara en PNG
      pesa varias veces más y viaja en base64 dentro del formulario.
--}}
<script>
    var CAMARA_RESOLUCION_IDEAL = { width: 1920, height: 1080 };
    var CAMARA_CONFIG_CAPTURA = { imageType: 'jpg', imageCompression: 0.85 };
</script>
