<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 7 (punto 3 del roadmap; CLAUDE.md sección 25): reemplaza la columna
 * única Ninio::fcm_token por una tabla de dispositivos — un Ninio puede
 * tener N dispositivos, cada uno con su propio token FCM.
 *
 * El identificador único de un dispositivo ES el fcm_token (CactuApp no
 * provee ningún device_id hoy). Un updateOrCreate por fcm_token en
 * login/actualizar-token-fcm reasigna el dispositivo al Ninio actual si el
 * mismo teléfono físico ya estaba asociado a otro numero_child — caso real
 * confirmado en datos: 144 grupos de fcm_token duplicados entre 843 filas
 * de `ninios` (hermanos patrocinados compartiendo un celular).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispositivos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ninio_id')->constrained('ninios')->cascadeOnDelete();
            $table->string('fcm_token')->unique();
            $table->timestamps();
        });

        $this->migrarTokensExistentes();
    }

    /**
     * Por cada fcm_token distinto en `ninios`, crea un único registro en
     * `dispositivos`, asignado al Ninio cuya fila fue actualizada más
     * recientemente (updated_at más alto = login más reciente = dueño real
     * y actual del dispositivo físico). Si dos filas de `ninios` comparten
     * el mismo fcm_token, la más antigua queda sin dispositivo tras migrar
     * — igual que pasaría de ahora en más con el nuevo esquema si ese niño
     * no vuelve a loguearse desde ese teléfono.
     */
    private function migrarTokensExistentes(): void
    {
        DB::table('ninios')
            ->whereNotNull('fcm_token')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->select('id', 'fcm_token', 'updated_at')
            ->get()
            ->unique('fcm_token')
            ->each(fn ($ninio) => DB::table('dispositivos')->insert([
                'ninio_id' => $ninio->id,
                'fcm_token' => $ninio->fcm_token,
                'created_at' => $ninio->updated_at,
                'updated_at' => $ninio->updated_at,
            ]));
    }

    public function down(): void
    {
        Schema::dropIfExists('dispositivos');
    }
};
