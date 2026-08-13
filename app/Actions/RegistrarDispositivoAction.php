<?php

namespace App\Actions;

use App\Models\Dispositivo;
use App\Models\Ninio;

/**
 * Registra o reasigna un dispositivo por su fcm_token. El token es la
 * clave única del dispositivo (CactuApp no provee device_id). Si el mismo
 * token ya estaba asociado a otro Ninio (mismo teléfono físico, otro
 * numero_child logueado antes — caso real: hermanos compartiendo un
 * celular), este upsert lo reasigna al Ninio actual.
 */
class RegistrarDispositivoAction
{
    public function registrar(Ninio $ninio, string $fcmToken): Dispositivo
    {
        return Dispositivo::updateOrCreate(
            ['fcm_token' => $fcmToken],
            ['ninio_id' => $ninio->id]
        );
    }
}
