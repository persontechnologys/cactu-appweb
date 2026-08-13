<?php

namespace Tests\Feature\Console;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Cubre el comando que resuelve el riesgo de acumulación en storage/app/temp
 * documentado en docs/auditoria/infraestructura-actual.md: borra solo los
 * huérfanos (más viejos que el umbral), nunca un archivo recién creado.
 */
class LimpiarArchivosTemporalesCommandTest extends TestCase
{
    public function test_borra_solo_los_archivos_mas_viejos_que_el_umbral(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('temp/huerfano.png', 'contenido');
        Storage::disk('local')->put('temp/reciente.png', 'contenido');

        $rutaHuerfano = Storage::disk('local')->path('temp/huerfano.png');
        touch($rutaHuerfano, now()->subHours(25)->getTimestamp());

        $this->artisan('temp:limpiar', ['--horas' => 24])
            ->assertExitCode(0);

        Storage::disk('local')->assertMissing('temp/huerfano.png');
        Storage::disk('local')->assertExists('temp/reciente.png');
    }

    public function test_no_falla_si_la_carpeta_temp_no_existe(): void
    {
        Storage::fake('local');

        $this->artisan('temp:limpiar')
            ->assertExitCode(0);
    }
}
