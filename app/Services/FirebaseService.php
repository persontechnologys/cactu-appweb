<?php

namespace App\Services;

use App\Models\Dispositivo;
use App\Models\Ninio;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\Messaging\NotFound;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as MensajeFirebase;

class FirebaseService
{
    public function __construct(private Messaging $messaging)
    {
    }

    /**
     * Envía a todos los dispositivos del Ninio (Fase 7, punto 3: un Ninio
     * puede tener varios). Un error transitorio (red, cuota, servidor) en
     * un dispositivo corta el envío a los restantes de esta ejecución, pero
     * se propaga para que $tries/$backoff de la Notification reintenten el
     * job completo — ningún dispositivo queda sin notificar por un fallo
     * transitorio de otro.
     */
    public function enviarNotificacion(Ninio $ninio, string $titulo, string $cuerpo): void
    {
        foreach ($ninio->dispositivos as $dispositivo) {
            $this->enviarADispositivo($dispositivo, $titulo, $cuerpo);
        }
    }

    /**
     * Si Firebase responde que el token ya no está registrado (dispositivo
     * desinstaló la app, o el token fue reemplazado por uno nuevo), se
     * elimina el dispositivo puntual para no seguir intentando contra un
     * token muerto (FIREBASE-001, Fase 7).
     */
    private function enviarADispositivo(Dispositivo $dispositivo, string $titulo, string $cuerpo): void
    {
        $mensaje = CloudMessage::withTarget('token', $dispositivo->fcm_token)
            ->withNotification(MensajeFirebase::create($titulo, $cuerpo));

        try {
            $this->messaging->send($mensaje);
        } catch (NotFound $e) {
            Log::warning("Token FCM inválido para Ninio #{$dispositivo->ninio_id}, se elimina el dispositivo #{$dispositivo->id}: {$e->getMessage()}");
            $dispositivo->delete();
        }
    }
}
