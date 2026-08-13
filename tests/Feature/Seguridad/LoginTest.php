<?php

namespace Tests\Feature\Seguridad;

use App\Models\Dispositivo;
use App\Models\Ninio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\CreaUsuariosDePrueba;
use Tests\TestCase;

/**
 * SEG-013: el catch(\Throwable) de POST /login envolvía también
 * $request->validate(...), así que una validación fallida devolvía un
 * string suelto con HTTP 200 en vez del 422 estándar de Laravel, y una
 * excepción real (p. ej. fallo de BD) filtraba $th->getMessage() al cliente.
 */
class LoginTest extends TestCase
{
    use RefreshDatabase, CreaUsuariosDePrueba;

    public function test_login_sin_numero_child_devuelve_422_estandar_de_laravel(): void
    {
        $respuesta = $this->postJson('/api/login', []);

        $respuesta->assertStatus(422);
        $respuesta->assertJsonValidationErrors('numero_child');
    }

    public function test_login_no_filtra_el_mensaje_interno_de_una_excepcion_real(): void
    {
        $gestor = $this->crearAdministrador();
        $comunidad = $this->crearComunidad($gestor);
        $ninio = Ninio::create([
            'numero_child' => 'EC-500',
            'nombres_completos' => 'Niño de prueba',
            'genero' => 'Male',
            'fecha_nacimiento' => '2015-01-01',
            'edad' => 10,
            'comunidad_id' => $comunidad->id,
        ]);

        // Fuerza una excepción real (no simulada) en $ninio->createToken(),
        // el mismo patrón usado en MensajesErrorTest para SEG-008.
        Schema::drop('personal_access_tokens');

        $respuesta = $this->postJson('/api/login', ['numero_child' => $ninio->numero_child]);

        $respuesta->assertJson(['error' => 'No se pudo iniciar sesión, intente nuevamente']);
        $respuesta->assertDontSee('personal_access_tokens', false);
        $respuesta->assertDontSee('SQLSTATE', false);
    }

    /**
     * SEG-014: antes, un login que no enviaba fcm_token en el body sobrescribía
     * el valor existente con null ($ninio->fcm_token = $request->fcm_token,
     * sin condición), perdiendo el token de push guardado. Adaptado a la
     * tabla `dispositivos` (Fase 7, punto 3): el dispositivo existente debe
     * permanecer intacto si el login no manda fcm_token.
     */
    public function test_login_sin_fcm_token_en_el_body_no_borra_el_dispositivo_existente(): void
    {
        $gestor = $this->crearAdministrador();
        $comunidad = $this->crearComunidad($gestor);
        $ninio = Ninio::create([
            'numero_child' => 'EC-501',
            'nombres_completos' => 'Niño de prueba',
            'genero' => 'Male',
            'fecha_nacimiento' => '2015-01-01',
            'edad' => 10,
            'comunidad_id' => $comunidad->id,
        ]);
        $dispositivo = Dispositivo::create(['ninio_id' => $ninio->id, 'fcm_token' => 'token-existente']);

        $respuesta = $this->postJson('/api/login', ['numero_child' => $ninio->numero_child]);

        $respuesta->assertOk();
        $this->assertSame(1, Dispositivo::count());
        $this->assertSame('token-existente', $dispositivo->fresh()->fcm_token);
        $this->assertSame($ninio->id, $dispositivo->fresh()->ninio_id);
    }

    public function test_login_con_fcm_token_en_el_body_registra_el_dispositivo(): void
    {
        $gestor = $this->crearAdministrador();
        $comunidad = $this->crearComunidad($gestor);
        $ninio = Ninio::create([
            'numero_child' => 'EC-502',
            'nombres_completos' => 'Niño de prueba',
            'genero' => 'Male',
            'fecha_nacimiento' => '2015-01-01',
            'edad' => 10,
            'comunidad_id' => $comunidad->id,
        ]);

        $respuesta = $this->postJson('/api/login', [
            'numero_child' => $ninio->numero_child,
            'fcm_token' => 'token-nuevo',
        ]);

        $respuesta->assertOk();
        $this->assertTrue(
            Dispositivo::where('ninio_id', $ninio->id)->where('fcm_token', 'token-nuevo')->exists()
        );
    }

    /**
     * Caso real confirmado en datos de producción: hermanos patrocinados que
     * comparten un único teléfono familiar, cada uno con su propio
     * numero_child. El fcm_token es la clave única del dispositivo, así que
     * cuando el segundo hermano se loguea desde el mismo teléfono, el
     * dispositivo debe reasignarse a él (y dejar de notificar al primero).
     */
    public function test_login_con_fcm_token_ya_asociado_a_otro_ninio_reasigna_el_dispositivo(): void
    {
        $gestor = $this->crearAdministrador();
        $comunidad = $this->crearComunidad($gestor);
        $hermanoUno = Ninio::create([
            'numero_child' => 'EC-503',
            'nombres_completos' => 'Hermano Uno',
            'genero' => 'Male',
            'fecha_nacimiento' => '2015-01-01',
            'edad' => 10,
            'comunidad_id' => $comunidad->id,
        ]);
        $hermanoDos = Ninio::create([
            'numero_child' => 'EC-504',
            'nombres_completos' => 'Hermano Dos',
            'genero' => 'Female',
            'fecha_nacimiento' => '2016-01-01',
            'edad' => 9,
            'comunidad_id' => $comunidad->id,
        ]);
        $dispositivo = Dispositivo::create(['ninio_id' => $hermanoUno->id, 'fcm_token' => 'token-telefono-familiar']);

        $respuesta = $this->postJson('/api/login', [
            'numero_child' => $hermanoDos->numero_child,
            'fcm_token' => 'token-telefono-familiar',
        ]);

        $respuesta->assertOk();
        $this->assertSame(1, Dispositivo::count());
        $this->assertSame($hermanoDos->id, $dispositivo->fresh()->ninio_id);
    }
}
