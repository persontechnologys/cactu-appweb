<?php

namespace App\Actions;

use App\Models\Boleta;
use App\Models\Carta;
use App\Models\Ninio;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RegistrarCartaAction
{
    /**
     * Registra una carta nueva junto con sus boletas y su PDF adjunto,
     * dentro de una única transacción. Extraído de CartaController::store
     * (ARQ-002) sin cambiar comportamiento.
     *
     * @param  UploadedFile[]|null  $boletas
     */
    public function registrar(
        int $ninioId,
        string $tipo,
        string $asunto,
        int $userId,
        ?array $boletas,
        ?UploadedFile $archivoCartaPdf
    ): Carta {
        return DB::transaction(function () use ($ninioId, $tipo, $asunto, $userId, $boletas, $archivoCartaPdf) {
            $ninio = Ninio::findOrFail($ninioId);

            $carta = new Carta();
            $carta->tipo = $tipo;
            $carta->asunto = $asunto;
            $carta->ninio_id = $ninioId;
            $carta->user_id = $userId;
            $carta->comunidad_id = $ninio->comunidad_id;
            $carta->save();

            if ($boletas) {
                foreach ($boletas as $file) {
                    $boleta = new Boleta();
                    $boleta->carta_id = $carta->id;
                    $boleta->save();

                    $pathBoleta = Storage::putFileAs(
                        'public/cartas/boletas', $file, $boleta->id.'.'.$file->getClientOriginalExtension()
                    );
                    $boleta->archivo_imagen = $pathBoleta;
                    $boleta->save();
                }
            }

            if ($archivoCartaPdf) {
                $pathCarta = Storage::putFileAs(
                    'public/cartas/archivo_pdf', $archivoCartaPdf, $carta->id.'.'.$archivoCartaPdf->getClientOriginalExtension()
                );
                $carta->archivo_pdf = $pathCarta;
                $carta->save();
            }

            return $carta;
        });
    }
}
