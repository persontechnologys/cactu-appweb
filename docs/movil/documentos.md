# Documentos y PDF en CactuApp

> Para el hallazgo de seguridad ya resuelto relacionado (SEG-003), ver `docs/auditoria/seguridad.md` y `docs/cambios/changelog.md` en el repositorio backend.

## Los dos PDFs que maneja la app

1. **Carta original del patrocinador** (`archivo` en la respuesta de `listar-mis-cartas`) — apunta a `GET /ver-archivo-pdf-por-ninio/{id}` con ID cifrado. Se muestra embebido en `Contestacion.js` (única pantalla que lo hace) o como link externo en `Agradecimiento.js`/`Iniciada.js`.
2. **Carta ya respondida por el niño** (`carta`) — apunta a `GET /dcpdf/{id}`, también con ID cifrado (desde el cierre de SEG-003). Se muestra en `DescargarEliminar.js`.

En ambos casos, **el ID cifrado siempre lo genera el backend** — el móvil solo sigue el URL recibido, nunca construye uno propio. Esto es importante: no se necesitó ningún cambio en el móvil cuando se cerró SEG-003 (cifrar `dcpdf/{id}`), porque el cliente ya trataba esos campos como URLs opacas.

## Cómo descarga y muestra un PDF

```text
RNFetchBlob.config({ fileCache: true, path: DownloadDir + '/cartas-cactu/' + id + '.pdf' })
  .fetch('GET', fileUrl)
  .then(res => seturlCarta(res.path()))
  .catch(() => toast('Error al descargar el archivo'))
```

Luego se renderiza con `<Pdf source={{uri: urlCarta}} />` (`react-native-pdf`). El archivo queda además guardado en la carpeta pública de descargas del dispositivo (`DownloadDir/cartas-cactu/`), visible fuera de la app — no se audita aquí si eso es deseado (contiene fotos y datos personales del niño), se deja registrado como consideración de privacidad en `cambios-requeridos.md`.

## Qué pasa ante 403/404

`.catch()` es genérico — solo muestra `"Error al descargar el archivo"` sin distinguir "no autorizado" de "no encontrado" de "sin conexión". Ver `manejo-errores.md`.

## Permisos involucrados

`WRITE_EXTERNAL_STORAGE_PERMISSION.js` pide el permiso Android correspondiente al montar `App.tsx` (junto con el permiso de notificaciones), independientemente de si el usuario está autenticado o va a descargar algo. En versiones recientes de Android (API 33+) este permiso ya no es necesario/no tiene efecto para el directorio de descargas público — no se auditó si esto genera un warning o no-op silencioso; queda para revisión en Fase de UX/performance.
