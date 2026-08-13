# Cron en cPanel — configuración real confirmada en producción

> Confirmado directamente por el usuario en cPanel de producción (`cactuec`) el 2026-08-12. Esta es la única fuente de verdad sobre el cron real — no vive en este repositorio (es configuración de panel, no de código), así que este documento debe actualizarse a mano si cambia en cPanel.

## Contexto

`app/Console/Kernel.php` (ver `docs/auditoria/infraestructura-actual.md`, sección 2) fue diseñado asumiendo el patrón "una sola entrada de cron que dispara `schedule:run`, y el Scheduler de Laravel se encarga de invocar `queue:work --stop-when-empty` cada minuto y `temp:limpiar` a diario". Ese sigue siendo el diseño recomendado (una sola entrada de cron cubre cualquier tarea que se agregue después a `Kernel::schedule()`, sin volver a tocar cPanel).

En la práctica, producción **no usa `schedule:run`**. Se configuraron dos entradas de cron independientes que invocan directamente los comandos artisan relevantes. Funciona (confirmado: las notificaciones de Firebase y de correo, antes encoladas sin procesarse, empezaron a llegar), pero tiene una diferencia importante frente al diseño de `Kernel.php`: **cualquier tarea que solo esté registrada en `Kernel::schedule()` y no tenga su propia entrada de cron no se ejecuta.** Hoy eso afecta a `temp:limpiar` (ver sección "Gap conocido" más abajo).

## Cron Jobs activos en cPanel (producción)

| Minuto | Hora | Día | Mes | Día semana | Comando |
|---|---|---|---|---|---|
| `*` | `*` | `*` | `*` | `*` | `/usr/local/bin/php /home/cactuec/public_html/cactu-appweb/artisan queue:work --stop-when-empty --tries=3 --timeout=90 >> /dev/null 2>&1` |
| `0` | `0` | `*` | `*` | `*` | `/usr/local/bin/php /home/cactuec/public_html/cactu-appweb/artisan queue:prune-failed --hours=168 >> /dev/null 2>&1` |

- **Cada minuto:** `queue:work --stop-when-empty` procesa la tabla `jobs` (driver `database`) y se detiene solo al vaciarla — evita dejar un proceso PHP persistente corriendo en un hosting compartido sin Supervisor. `--tries=3 --timeout=90` son flags explícitos en el comando (no dependen de `$tries`/`$backoff` de cada Notification para el timeout, aunque los reintentos de la Notification en sí — ver `FIREBASE-001` en `problemas-detectados.md` — siguen aplicando por debajo de este límite).
- **Diario a medianoche:** `queue:prune-failed --hours=168` (7 días) limpia `failed_jobs` para que no crezca indefinidamente en un disco de cuota limitada.
- **Ruta del binario PHP:** `/usr/local/bin/php` — confirmada como la que usa este cPanel específico (puede no coincidir con la de PHP-FPM/Apache; ver `docs/auditoria/infraestructura-actual.md` sección 4, sigue sin verificar si ambas versiones coinciden).
- **Ruta del proyecto:** `/home/cactuec/public_html/cactu-appweb` (usuario cPanel `cactuec`).

## Gap conocido: `temp:limpiar` no se ejecuta

`app/Console/Kernel.php:26-28` registra:

```php
$schedule->command('temp:limpiar')
    ->daily()
    ->withoutOverlapping();
```

Esta tarea (borra huérfanos de `storage/app/temp` más viejos que 24h — ver `INFRA-001` en `docs/auditoria/problemas-detectados.md`) **solo se ejecuta si algo invoca `artisan schedule:run`**. Como los dos cron jobs activos llaman directamente a `queue:work` y `queue:prune-failed`, `schedule:run` nunca corre en producción, y `temp:limpiar` tampoco.

**Impacto real:** bajo por ahora — `storage/app/temp` ya se limpia por request en el flujo normal (`ResponderCartaController`, incluso en `finally`); el archivo huérfano solo se acumula si un proceso PHP muere a mitad de camino (timeout, OOM, cliente que corta la conexión). No es una pérdida de datos ni un problema funcional visible, pero si se acumula sin límite en un disco de cuota compartida, puede volverse un problema de espacio con el tiempo.

**Solución recomendada (no aplicada, pendiente de autorización):** agregar una tercera entrada de cron:

```
* * * * * /usr/local/bin/php /home/cactuec/public_html/cactu-appweb/artisan schedule:run >> /dev/null 2>&1
```

No genera conflicto con las dos entradas existentes — `schedule:run` no relanza otro `queue:work` en paralelo del que ya corre por su cron directo, simplemente evalúa qué tareas de `Kernel::schedule()` le tocan en ese minuto (hoy solo `temp:limpiar`, porque `queue:work` en `Kernel.php` seguiría registrado pero sería redundante con el cron directo — se puede dejar así o retirarlo de `Kernel.php` para no tener el mismo comando definido en dos lugares). Agregar esta entrada también deja resuelto de una vez cualquier tarea nueva que se agregue a futuro a `Kernel::schedule()`, sin depender de acordarse de crear un cron nuevo cada vez en cPanel.

## Referencias

- `app/Console/Kernel.php` — definición real de las tareas programadas (`queue:work`, `temp:limpiar`).
- `docs/auditoria/infraestructura-actual.md` — diagnóstico original de infraestructura, incluye la brecha que este documento cierra.
- `docs/auditoria/problemas-detectados.md` — fichas `PERF-001`, `INFRA-001`, `FIREBASE-001`, `FIREBASE-002`.
- `docs/infraestructura/despliegue.md` — checklist de valores de despliegue, incluye el ítem de cron ahora confirmado.
- `docs/cambios/changelog.md` — entrada 2026-08-12.
