<?php

namespace Tests\Feature\Api;

use App\Models\Dispositivo;
use App\Models\Ninio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaUsuariosDePrueba;
use Tests\TestCase;

/**
 * Fase 7 (paso 1, roadmap): endpoints para actualizar/eliminar el token FCM
 * fuera del login (antes solo se podía sobrescribir volviendo a autenticar).
 * Fase 7 (punto 3, roadmap): adaptado a la tabla `dispositivos`.
 */
class TokenFcmTest extends TestCase
{
    use RefreshDatabase, CreaUsuariosDePrueba;

    private function crearNinioAutenticado(string $numeroChild, ?string $fcmToken = null): array
    {
        $gestor = $this->crearAdministrador();
        $comunidad = $this->crearComunidad($gestor);
        $ninio = Ninio::create([
            'numero_child' => $numeroChild,
            'nombres_completos' => 'Niño '.$numeroChild,
            'genero' => 'Male',
            'fecha_nacimiento' => '2015-01-01',
            'edad' => 10,
            'comunidad_id' => $comunidad->id,
        ]);

        if ($fcmToken) {
            Dispositivo::create(['ninio_id' => $ninio->id, 'fcm_token' => $fcmToken]);
        }

        $token = $ninio->createToken('test')->plainTextToken;

        return [$ninio, $token];
    }

    public function test_actualizar_token_fcm_requiere_autenticacion(): void
    {
        $this->postJson('/api/actualizar-token-fcm', ['fcm_token' => 'nuevo'])
            ->assertStatus(401);
    }

    public function test_actualizar_token_fcm_exige_el_campo(): void
    {
        [, $token] = $this->crearNinioAutenticado('EC-600');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/actualizar-token-fcm', [])
            ->assertJsonValidationErrors('fcm_token');
    }

    public function test_actualizar_token_fcm_registra_el_dispositivo(): void
    {
        [$ninio, $token] = $this->crearNinioAutenticado('EC-601');

        $respuesta = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/actualizar-token-fcm', ['fcm_token' => 'token-nuevo']);

        $respuesta->assertOk();
        $this->assertTrue(
            Dispositivo::where('ninio_id', $ninio->id)->where('fcm_token', 'token-nuevo')->exists()
        );
    }

    public function test_actualizar_token_fcm_con_el_mismo_token_dos_veces_no_duplica_el_dispositivo(): void
    {
        [, $token] = $this->crearNinioAutenticado('EC-605');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/actualizar-token-fcm', ['fcm_token' => 'token-repetido'])
            ->assertOk();
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/actualizar-token-fcm', ['fcm_token' => 'token-repetido'])
            ->assertOk();

        $this->assertSame(1, Dispositivo::where('fcm_token', 'token-repetido')->count());
    }

    public function test_actualizar_token_fcm_reasigna_un_dispositivo_de_otro_ninio(): void
    {
        [$ninioA] = $this->crearNinioAutenticado('EC-606', 'token-compartido');
        [, $tokenB] = $this->crearNinioAutenticado('EC-607');

        $this->withHeader('Authorization', 'Bearer '.$tokenB)
            ->postJson('/api/actualizar-token-fcm', ['fcm_token' => 'token-compartido'])
            ->assertOk();

        $dispositivo = Dispositivo::where('fcm_token', 'token-compartido')->sole();
        $this->assertNotSame($ninioA->id, $dispositivo->ninio_id);
        $this->assertSame(1, Dispositivo::where('fcm_token', 'token-compartido')->count());
    }

    public function test_eliminar_token_fcm_requiere_autenticacion(): void
    {
        $this->postJson('/api/eliminar-token-fcm')
            ->assertStatus(401);
    }

    public function test_eliminar_token_fcm_exige_el_campo(): void
    {
        [, $token] = $this->crearNinioAutenticado('EC-608');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/eliminar-token-fcm', [])
            ->assertJsonValidationErrors('fcm_token');
    }

    public function test_eliminar_token_fcm_borra_el_dispositivo_indicado(): void
    {
        [$ninio, $token] = $this->crearNinioAutenticado('EC-602', 'token-existente');

        $respuesta = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/eliminar-token-fcm', ['fcm_token' => 'token-existente']);

        $respuesta->assertOk();
        $this->assertFalse(
            Dispositivo::where('ninio_id', $ninio->id)->where('fcm_token', 'token-existente')->exists()
        );
    }

    public function test_eliminar_token_fcm_no_afecta_otros_dispositivos_del_mismo_ninio(): void
    {
        [$ninio, $token] = $this->crearNinioAutenticado('EC-609', 'token-uno');
        Dispositivo::create(['ninio_id' => $ninio->id, 'fcm_token' => 'token-dos']);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/eliminar-token-fcm', ['fcm_token' => 'token-uno'])
            ->assertOk();

        $this->assertTrue(
            Dispositivo::where('ninio_id', $ninio->id)->where('fcm_token', 'token-dos')->exists()
        );
    }

    public function test_un_ninio_solo_actualiza_su_propio_token(): void
    {
        [$ninioA, $tokenA] = $this->crearNinioAutenticado('EC-603', 'token-a');
        [$ninioB] = $this->crearNinioAutenticado('EC-604', 'token-b');

        $this->withHeader('Authorization', 'Bearer '.$tokenA)
            ->postJson('/api/actualizar-token-fcm', ['fcm_token' => 'token-a-nuevo'])
            ->assertOk();

        $this->assertTrue(
            Dispositivo::where('ninio_id', $ninioA->id)->where('fcm_token', 'token-a-nuevo')->exists()
        );
        $this->assertTrue(
            Dispositivo::where('ninio_id', $ninioB->id)->where('fcm_token', 'token-b')->exists()
        );

        // Intentar eliminar el token del niño B autenticado como A no debe afectarlo:
        // el scope $ninio->dispositivos() de eliminar() solo alcanza los propios.
        $this->withHeader('Authorization', 'Bearer '.$tokenA)
            ->postJson('/api/eliminar-token-fcm', ['fcm_token' => 'token-b'])
            ->assertOk();

        $this->assertTrue(
            Dispositivo::where('ninio_id', $ninioB->id)->where('fcm_token', 'token-b')->exists()
        );
    }
}
