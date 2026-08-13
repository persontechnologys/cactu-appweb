<?php

namespace Tests\Feature\Api;

use App\Models\Carta;
use App\Models\Ninio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreaUsuariosDePrueba;
use Tests\TestCase;

/**
 * ARQ-001: test de regresión end-to-end (ruta -> controller -> Action) del
 * canal API/móvil de "responder carta", escrito tras extraer las Actions
 * para comprobar que el comportamiento observable no cambió.
 */
class ResponderCartaTest extends TestCase
{
    use RefreshDatabase, CreaUsuariosDePrueba;

    private function crearNinioConCartaPendiente(): array
    {
        $gestor = $this->crearAdministrador();
        $comunidad = $this->crearComunidad($gestor);
        $ninio = Ninio::create([
            'numero_child' => 'EC-'.uniqid(),
            'nombres_completos' => 'Niño de prueba',
            'genero' => 'Male',
            'fecha_nacimiento' => '2015-01-01',
            'edad' => 10,
            'comunidad_id' => $comunidad->id,
        ]);

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

    public function test_el_ninio_autenticado_puede_responder_su_propia_carta_de_contestacion(): void
    {
        Storage::fake('local');
        Notification::fake();
        [$ninio, $carta] = $this->crearNinioConCartaPendiente();
        $token = $ninio->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/responder-carta-contestacion', [
                'id' => $carta->id,
                'imagen' => UploadedFile::fake()->image('foto.jpg'),
                'nombre_patrocinador' => 'Juan',
                'agradezco_por' => 'la comida',
                'te_cuento_que' => 'estoy bien',
                'pregunta_al_patrocinador' => 'cómo estás',
                'detalle' => 'un abrazo',
            ]);

        $response->assertOk();
        $response->assertJson(['success' => 'Carta resgitrado exitosamente']);

        $carta->refresh();
        $this->assertSame('Respondida', $carta->estado);
        $this->assertStringContainsString('Juan', $carta->detalle);
        Storage::disk('local')->assertExists($carta->archivo_imagen_ninio);
    }

    public function test_responder_una_carta_ya_respondida_no_persiste_y_devuelve_info(): void
    {
        Storage::fake('local');
        [$ninio, $carta] = $this->crearNinioConCartaPendiente();
        $carta->estado = 'Respondida';
        $carta->detalle = '<p>respuesta original</p>';
        $carta->save();
        $token = $ninio->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/responder-carta-contestacion', [
                'id' => $carta->id,
                'imagen' => UploadedFile::fake()->image('foto.jpg'),
                'detalle' => 'un abrazo',
            ]);

        $response->assertOk();
        $response->assertJson(['info' => 'Carta ya fue respondida']);

        // No se sobrescribe la respuesta original (comportamiento idempotente ya existente).
        $this->assertSame('<p>respuesta original</p>', $carta->fresh()->detalle);
    }

    public function test_responder_carta_con_id_inexistente_no_revienta_con_500(): void
    {
        [$ninio] = $this->crearNinioConCartaPendiente();
        $token = $ninio->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/responder-carta-contestacion', [
                'id' => 999999,
                'imagen' => UploadedFile::fake()->image('foto.jpg'),
                'detalle' => 'un abrazo',
            ]);

        // Comportamiento heredado de guardarCarta(): catch(\Throwable) genérico, responde
        // 200 con {error: ...} en vez de un 404/500 — se preserva tal cual, no se corrige aquí.
        $response->assertOk();
        $response->assertJson(['error' => 'Carta no registrado vuelva intentar']);
    }

    public function test_falta_un_campo_obligatorio_devuelve_422(): void
    {
        [$ninio, $carta] = $this->crearNinioConCartaPendiente();
        $token = $ninio->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/responder-carta-contestacion', [
                'id' => $carta->id,
                'imagen' => UploadedFile::fake()->image('foto.jpg'),
                // falta 'detalle', requerido por ResponderCartaContestacionRequest
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('detalle');
    }
}
