<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Borra huérfanos de storage/app/temp: ResponderCartaController ya limpia
 * cada archivo apenas lo usa (via finally), esto solo cubre el caso en que
 * el proceso PHP murió antes de llegar a ese finally (cliente desconectado,
 * timeout, OOM) — ver docs/auditoria/infraestructura-actual.md.
 */
class LimpiarArchivosTemporalesCommand extends Command
{
    protected $signature = 'temp:limpiar {--horas=24 : Antigüedad mínima en horas para borrar un archivo}';

    protected $description = 'Elimina archivos huérfanos de storage/app/temp más antiguos que el umbral indicado';

    public function handle(): int
    {
        $carpeta = 'temp';
        $horas = (int) $this->option('horas');
        $limite = now()->subHours($horas)->getTimestamp();

        if (!Storage::exists($carpeta)) {
            $this->info("No existe {$carpeta}, nada que limpiar.");
            return self::SUCCESS;
        }

        $eliminados = 0;
        foreach (Storage::files($carpeta) as $archivo) {
            if (Storage::lastModified($archivo) < $limite) {
                Storage::delete($archivo);
                $eliminados++;
            }
        }

        $this->info("Archivos temporales huérfanos eliminados: {$eliminados}");
        return self::SUCCESS;
    }
}
