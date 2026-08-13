<?php

namespace Tests\Feature\Api;

use App\Models\Carta;
use App\Models\Ninio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\Concerns\CreaUsuariosDePrueba;
use Tests\TestCase;

/**
 * Fase 5 (ARQ-005): alias versionado /api/v1/ sobre las mismas rutas de
 * /api/. Estos tests comprueban que el alias responde igual que la ruta
 * legada, y fijan explícitamente el efecto secundario aceptado en la
 * decisión de arquitectura: las URLs de descarga de PDF embebidas en la
 * respuesta siguen apuntando a /api/... aunque el cliente use /api/v1/.
 */
class VersionadoApiV1Test extends TestCase
{
    use RefreshDatabase, CreaUsuariosDePrueba;

    public function test_login_responde_igual_bajo_api_v1_que_bajo_api(): void
    {
        $gestor = $this->crearAdministrador();
        $comunidad = $this->crearComunidad($gestor);
        $ninio = Ninio::create([
            'numero_child' => 'EC-V1-'.uniqid(),
            'nombres_completos' => 'Niño de prueba v1',
            'genero' => 'Male',
            'fecha_nacimiento' => '2015-01-01',
            'edad' => 10,
            'comunidad_id' => $comunidad->id,
        ]);

        $respuesta = $this->postJson('/api/v1/login', [
            'numero_child' => $ninio->numero_child,
        ]);

        $respuesta->assertOk()
            ->assertJson([
                'message' => 'ok',
                'id' => $ninio->id,
                'numero_child' => $ninio->numero_child,
                'nombres' => $ninio->nombres_completos,
            ])
            ->assertJsonStructure(['message', 'id', 'numero_child', 'nombres', 'token', 'roles_permisos']);
    }

    public function test_listar_mis_cartas_bajo_api_v1_devuelve_la_misma_forma_que_bajo_api(): void
    {
        $gestor = $this->crearAdministrador();
        $comunidad = $this->crearComunidad($gestor);
        $ninio = Ninio::create([
            'numero_child' => 'EC-V1-'.uniqid(),
            'nombres_completos' => 'Niño con carta',
            'genero' => 'Male',
            'fecha_nacimiento' => '2015-01-01',
            'edad' => 10,
            'comunidad_id' => $comunidad->id,
        ]);

        Auth::login($gestor);
        Carta::create([
            'tipo' => 'Contestación',
            'asunto' => 'Carta vía v1',
            'estado' => 'Enviado',
            'ninio_id' => $ninio->id,
            'comunidad_id' => $comunidad->id,
        ]);
        Auth::logout();

        $token = $ninio->createToken('test')->plainTextToken;

        $porV1 = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/listar-mis-cartas')
            ->assertOk()
            ->json('data');

        $porLegado = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/listar-mis-cartas')
            ->assertOk()
            ->json('data');

        $this->assertSame($porLegado, $porV1);
    }

    public function test_las_rutas_con_nombre_no_colisionan_entre_api_y_api_v1(): void
    {
        $this->assertSame('/api/ver-archivo-pdf-por-ninio/1', route('verarchivopdfporninio', ['id' => '1'], false));
        $this->assertSame('/api/dcpdf/1', route('descargarCartaPdf', ['id' => '1'], false));
        $this->assertSame('/api/v1/ver-archivo-pdf-por-ninio/1', route('v1.verarchivopdfporninio', ['id' => '1'], false));
        $this->assertSame('/api/v1/dcpdf/1', route('v1.descargarCartaPdf', ['id' => '1'], false));
    }
}
