# Notificaciones — qué recibe el niño y qué hace la app con ellas

> Complementa `firebase.md` (transporte) con el enfoque en la experiencia de notificación en sí.

## Qué dispara una notificación

Solo un evento de negocio: **el gestor crea una carta nueva** para un niño (`App\Notifications\EnviarNotificacionCartaNueva`, disparada desde el backend). No hay notificaciones para: carta próxima a vencer, recordatorios, mensajes del sistema, ni nada iniciado desde el móvil.

## Qué hace la app al recibir una

- **App en background/cerrada:** el sistema operativo (vía Firebase) muestra la notificación nativa por defecto. Al tocarla, `onNotificationOpenedApp`/`getInitialNotification` se disparan pero **solo hacen `console.log`** — no hay navegación automática a la pantalla de la carta ni a `Index`. El usuario debe navegar manualmente tras abrir la app.
- **App en foreground:** `onMessage` también solo hace `console.log` — **no se muestra ningún banner o alerta local**. Un niño con la app abierta no se entera de una carta nueva hasta que haga pull-to-refresh o vuelva a `Index`.

## Qué NO existe

- Contador de "no leídas".
- Historial de notificaciones dentro de la app.
- Preferencias de notificación (silenciar, elegir tipo).
- Notificaciones locales generadas por la app misma.

## Antes de construir esto

Si se pide implementar navegación automática al tocar una notificación o un banner en foreground, confirmar primero el **payload real** que envía `EnviarNotificacionCartaNueva` del lado backend (qué `data`/`notification` incluye — por ejemplo, si trae el `id` de la carta) antes de diseñar la navegación, ya que actualmente el cliente no lee ningún campo del `remoteMessage` más allá de loguearlo completo.
