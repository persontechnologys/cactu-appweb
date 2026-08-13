<?php

namespace App\Actions;

use App\Models\Carta;
use App\Notifications\EnviarCartaRespondidaGestor;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class GuardarRespuestaCartaAction
{
    /**
     * Guarda la respuesta de un niño a una carta: archivo(s) de imagen,
     * detalle y transición de estado a "Respondida", más la notificación
     * al gestor. Extraído de ResponderCartaController::guardarArchivo (web)
     * y Api\ResponderCarta::guardarCarta (móvil) — ARQ-001. La resolución
     * de la carta y su autorización quedan en cada controller, porque el
     * modelo de acceso difiere entre canales (link cifrado sin sesión en
     * web vs. niño autenticado por Sanctum en la API).
     */
    public function guardar(
        Carta $carta,
        string $detalle,
        ?UploadedFile $imagen,
        ?UploadedFile $fotoFamilia
    ): Carta {
        return DB::transaction(function () use ($carta, $detalle, $imagen, $fotoFamilia) {
            if ($imagen) {
                $extension = $imagen->getClientOriginalExtension();
                $carta->archivo_imagen_ninio = Storage::putFileAs(
                    'public/cartas/archivo_imagen_ninio', $imagen, $carta->id.'.'.$extension
                );
            }

            if ($fotoFamilia) {
                $extensionFamilia = $fotoFamilia->getClientOriginalExtension();
                $carta->archivo_familia_ninio = Storage::putFileAs(
                    'public/cartas/archivo_familia_ninio', $fotoFamilia, $carta->id.'.'.$extensionFamilia
                );
            }

            $carta->detalle = $detalle;
            $carta->estado = 'Respondida';
            $carta->fecha_respondida = Carbon::now();
            $carta->save();

            $carta->gestor->notify(new EnviarCartaRespondidaGestor($carta));

            return $carta;
        });
    }
}
