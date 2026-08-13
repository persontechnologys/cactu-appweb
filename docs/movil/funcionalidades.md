# Funcionalidades de CactuApp

> Resumen orientado a desarrollo. Para la matriz de duplicación de reglas de negocio entre Web/API/Móvil, ver `docs/auditoria/funcionalidades-compartidas.md` en el repositorio backend.

## 1. Listado de cartas (`inicio/Index.js`)

Pantalla principal. `POST /listar-mis-cartas` al montar, en pull-to-refresh, y al volver de responder/eliminar una carta (vía `route.params.estado`, un valor aleatorio usado solo como *trigger*). Cada carta se muestra como una tarjeta con tipo, fecha y asunto; al presionarla navega según:

```text
estado === 'Respondida'  → DescargarEliminar
tipo_carta_nombre:
  'Presentación'          → Presentacion (bifurca a Mayores/Menores según edad > 5)
  'Agradecimiento'        → Agradecimiento
  'Iniciada'               → Iniciada
  (default)                → Contestacion
```

## 2. Responder carta — 5 variantes

Todas comparten el mismo esqueleto: si `estado === 'Respondida'` muestran un mensaje y botón "Regresar"; si no, un formulario con `native-base` + captura de foto (`react-native-image-crop-picker`, cámara únicamente, no galería) + envío `multipart/form-data`. Guardan borrador en `AsyncStorage` en cada tecla y lo limpian al recibir `success`.

| Pantalla | Campos propios | Foto(s) requerida(s) |
|---|---|---|
| `Contestacion` | `nombre_patrocinador`, `agradezco_por`, `te_cuento_que`, `pregunta_al_patrocinador`, `texto` | 1 (personal) |
| `Agradecimiento` | ídem + `regalo_usar_para` | 1 (personal) |
| `Iniciada` | `nombre_patrocinador`, `te_cuento_que`, `pregunta_al_patrocinador`, `en_cactu_aprendi`, `texto` | 1 (personal) |
| `PresentacionMayores` | 21 campos de texto (perfil extenso del niño) | 2 (personal + familiar) |
| `PresentacionMenores` | 24 campos de texto (variante para menores de 5 años, redactada en tercera persona/representante) | 2 (personal + familiar) |

`Contestacion.js` es la única que además descarga y muestra el PDF original de la carta del patrocinador embebido (`react-native-pdf`) antes del formulario; `Agradecimiento`/`Iniciada` solo ofrecen un link externo (`archivo`) si existe.

## 3. Descargar/eliminar carta respondida (`DescargarEliminar.js`)

Descarga el PDF de la carta ya respondida (`RNFetchBlob` → `DownloadDir/cartas-cactu/{id}.pdf`) y lo muestra con `react-native-pdf`. Botón adicional para eliminar (con confirmación `Alert.alert`) vía `POST /eliminar-carta`.

## 4. Perfil (`Perfil.js`)

Pantalla mínima: solo botón "Salir de la aplicación" (`signOut`). No hay edición de datos del niño, no hay cambio de `numero_child`, no hay gestión de notificaciones.

## 5. Acerca de (`acerca/Inicio.js`)

Estática: descripción de CACTU, logo, crédito de desarrollo. Sin llamadas a la API.

## 6. Onboarding (`IntroSlider.js`)

3 slides estáticos (misión/visión/presentación), visible solo la primera vez que se abre la app sin sesión (antes de `Login`). No hay persistencia de "ya visto" — se muestra siempre que no haya token, incluso tras logout.

## Funcionalidad backend NO consumida por el móvil

`POST /enviar-mensaje` — existe en el backend como placeholder, ninguna pantalla del móvil lo invoca. Ver `cambios-requeridos.md` antes de asumir que "mensajería" es una función activa.
