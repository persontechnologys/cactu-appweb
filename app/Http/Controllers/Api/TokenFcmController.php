<?php

namespace App\Http\Controllers\Api;

use App\Actions\RegistrarDispositivoAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ActualizarTokenFcmRequest;
use App\Http\Requests\EliminarTokenFcmRequest;

/**
 * Fase 7 (paso 1, roadmap): antes del login era el único punto de escritura
 * de Ninio::fcm_token. Estos endpoints permiten actualizarlo (p. ej. tras
 * onTokenRefresh en el móvil) o eliminarlo (p. ej. en logout) sin volver a
 * autenticar con numero_child.
 *
 * Fase 7 (punto 3, roadmap): migrado a la tabla `dispositivos` — un Ninio
 * puede tener varios dispositivos, cada uno identificado por su fcm_token.
 * `eliminar` ahora exige `fcm_token` en el body para saber qué dispositivo
 * puntual borrar (antes borraba "el único que había").
 */
class TokenFcmController extends Controller
{
    public function __construct(private RegistrarDispositivoAction $registrarDispositivo)
    {
    }

    public function actualizar(ActualizarTokenFcmRequest $request)
    {
        $ninio = $request->user();
        $this->registrarDispositivo->registrar($ninio, $request->fcm_token);

        return response()->json(['success' => 'Token FCM actualizado correctamente']);
    }

    public function eliminar(EliminarTokenFcmRequest $request)
    {
        $ninio = $request->user();
        $ninio->dispositivos()->where('fcm_token', $request->fcm_token)->delete();

        return response()->json(['success' => 'Token FCM eliminado correctamente']);
    }
}
