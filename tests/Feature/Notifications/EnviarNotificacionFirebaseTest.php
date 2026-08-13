<?php

namespace Tests\Feature\Notifications;

use App\Models\Carta;
use App\Models\Dispositivo;
use App\Models\Ninio;
use App\Notifications\EnviarNotificacionCartaNueva;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\Messaging\NotFound;
use Kreait\Firebase\Exception\Messaging\ServerError;
use Kreait\Firebase\Messaging\CloudMessage;
use Mockery;
use Tests\Concerns\CreaUsuariosDePrueba;
use Tests\TestCase;

/**
 * FIREBASE-001 (Fase 7, 2026-08-08): el envío pasó de la API legacy de FCM
 * (decomisionada por Google en 2024, ver `docs/auditoria/problemas-detectados.md`)
 * a FCM HTTP v1 vía kreait/laravel-firebase. Estas pruebas mockean el
 * contrato `Messaging` para no depender de credenciales reales de Firebase.
 *
 * Fase 7 (punto 3, roadmap): adaptado a la tabla `dispositivos` — un Ninio
 * puede tener varios, cada uno con su propio token.
 */
class EnviarNotificacionFirebaseTest extends TestCase
{
    use RefreshDatabase, CreaUsuariosDePrueba;

    /** @param string[] $fcmTokens */
    private function crearNinioConCarta(array $fcmTokens = []): array
    {
        $gestor = $this->crearAdministrador();
        $comunidad = $this->crearComunidad($gestor);
        $ninio = Ninio::create([
            'numero_child' => 'EC-700',
            'nombres_completos' => 'Niño de prueba',
            'genero' => 'Male',
            'fecha_nacimiento' => '2015-01-01',
            'edad' => 10,
            'comunidad_id' => $comunidad->id,
        ]);

        foreach ($fcmTokens as $fcmToken) {
            Dispositivo::create(['ninio_id' => $ninio->id, 'fcm_token' => $fcmToken]);
        }

        Auth::login($gestor);
        $carta = Carta::create([
            'tipo' => 'Contestación',
            'asunto' => 'Asunto de prueba',
            'estado' => 'Enviado',
            'ninio_id' => $ninio->id,
            'comunidad_id' => $comunidad->id,
        ]);
        Auth::logout();

        return [$ninio, $carta];
    }

    public function test_envia_la_notificacion_por_el_canal_firebase_cuando_hay_token(): void
    {
        [$ninio, $carta] = $this->crearNinioConCarta(['token-valido']);

        $mock = Mockery::mock(Messaging::class);
        $mock->shouldReceive('send')->once()->with(Mockery::type(CloudMessage::class));
        $this->app->instance(Messaging::class, $mock);

        $ninio->notify(new EnviarNotificacionCartaNueva($carta));

        $this->assertTrue(Dispositivo::where('fcm_token', 'token-valido')->exists());
    }

    public function test_no_intenta_enviar_si_el_ninio_no_tiene_token(): void
    {
        [$ninio, $carta] = $this->crearNinioConCarta();

        $mock = Mockery::mock(Messaging::class);
        $mock->shouldNotReceive('send');
        $this->app->instance(Messaging::class, $mock);

        $ninio->notify(new EnviarNotificacionCartaNueva($carta));

        // la aserción real es la expectativa Mockery: shouldNotReceive('send')
        // se verifica automáticamente al cerrar el test (Mockery::close()).
        $this->assertTrue(true);
    }

    public function test_token_no_registrado_se_limpia_sin_relanzar_la_excepcion(): void
    {
        [$ninio, $carta] = $this->crearNinioConCarta(['token-viejo']);

        $mock = Mockery::mock(Messaging::class);
        $mock->shouldReceive('send')->once()->andThrow(NotFound::becauseTokenNotFound('token-viejo'));
        $this->app->instance(Messaging::class, $mock);

        // no debe relanzar: un token inválido no amerita reintento
        $ninio->notify(new EnviarNotificacionCartaNueva($carta));

        $this->assertFalse(Dispositivo::where('fcm_token', 'token-viejo')->exists());
    }

    public function test_error_transitorio_se_propaga_para_que_el_job_reintente(): void
    {
        [$ninio, $carta] = $this->crearNinioConCarta(['token-valido']);

        $mock = Mockery::mock(Messaging::class);
        $mock->shouldReceive('send')->once()->andThrow(new ServerError('Firebase no disponible'));
        $this->app->instance(Messaging::class, $mock);

        try {
            $ninio->notify(new EnviarNotificacionCartaNueva($carta));
            $this->fail('Se esperaba que la excepción de Firebase se propagara.');
        } catch (ServerError) {
            // esperado: con la cola sync se relanza igual que en producción
            // relanzaría dentro del job, dejando que $tries/$backoff reintenten.
        }

        // un error transitorio no debe eliminar el dispositivo
        $this->assertTrue(Dispositivo::where('fcm_token', 'token-valido')->exists());
    }

    public function test_envia_a_cada_dispositivo_del_ninio_cuando_tiene_mas_de_uno(): void
    {
        [$ninio, $carta] = $this->crearNinioConCarta(['token-uno', 'token-dos']);

        $mock = Mockery::mock(Messaging::class);
        $mock->shouldReceive('send')->twice()->with(Mockery::type(CloudMessage::class));
        $this->app->instance(Messaging::class, $mock);

        $ninio->notify(new EnviarNotificacionCartaNueva($carta));

        $this->assertSame(2, Dispositivo::where('ninio_id', $ninio->id)->count());
    }

    public function test_token_invalido_en_un_dispositivo_no_afecta_el_envio_a_los_demas_del_mismo_ninio(): void
    {
        [$ninio, $carta] = $this->crearNinioConCarta(['token-malo', 'token-bueno']);

        $mock = Mockery::mock(Messaging::class);
        $mock->shouldReceive('send')->once()->ordered()->andThrow(NotFound::becauseTokenNotFound('token-malo'));
        $mock->shouldReceive('send')->once()->ordered()->andReturn([]);
        $this->app->instance(Messaging::class, $mock);

        $ninio->notify(new EnviarNotificacionCartaNueva($carta));

        $this->assertFalse(Dispositivo::where('fcm_token', 'token-malo')->exists());
        $this->assertTrue(Dispositivo::where('fcm_token', 'token-bueno')->exists());
    }

    public function test_tries_y_backoff_configurados_para_reintentos(): void
    {
        $carta = new Carta(['tipo' => 'Contestación']);
        $notificacion = new EnviarNotificacionCartaNueva($carta);

        $this->assertSame(3, $notificacion->tries);
        $this->assertSame(30, $notificacion->backoff);
    }
}
