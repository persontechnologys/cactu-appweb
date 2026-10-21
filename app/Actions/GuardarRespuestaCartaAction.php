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
                $carta->archivo_imagen_ninio = Storage::putFileAs(
                    'public/cartas/archivo_imagen_ninio', $imagen, $carta->id.'.'.$this->extensionReal($imagen)
                );
            }

            if ($fotoFamilia) {
                $carta->archivo_familia_ninio = Storage::putFileAs(
                    'public/cartas/archivo_familia_ninio', $fotoFamilia, $carta->id.'.'.$this->extensionReal($fotoFamilia)
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

    /**
     * Extensión según el contenido real del archivo, no según el nombre que
     * manda el cliente. CactuApp envía JPEG con el nombre "personal.png", y
     * antes se guardaba como .png. Si el servidor no puede detectar el tipo
     * (sin fileinfo), se usa la extensión del nombre, como antes.
     */
    private function extensionReal(UploadedFile $archivo): string
    {
        try {
            $extension = $archivo->guessExtension();
        } catch (\LogicException) {
            $extension = null;
        }

        return $extension ?: $archivo->getClientOriginalExtension();
    }
}
