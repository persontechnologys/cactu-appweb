<?php

namespace Tests\Feature\Api;

use App\Models\Carta;
use App\Models\Ninio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\Concerns\CreaUsuariosDePrueba;
use Tests\TestCase;

/**
 * SEG-004: IDOR en listar-mis-cartas por confiar en `userId` del cliente.
 *
 * Escenario de ataque cerrado: la app móvil del niño A enviaba
 * POST /api/listar-mis-cartas con userId del niño B y el backend
 * devolvía las cartas de B, sin verificar el dueño real del token.
 */
class ListarMisCartasTest extends TestCase
{
    use RefreshDatabase, CreaUsuariosDePrueba;

    private function crearNinioConCarta(string $numeroChild, string $asunto): Ninio
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

        Auth::login($gestor);
        Carta::create([
            'tipo' => 'Contestación',
            'asunto' => $asunto,
            'estado' => 'Enviado',
            'ninio_id' => $ninio->id,
            'comunidad_id' => $comunidad->id,
        ]);
        Auth::logout();

        return $ninio;
    }

    public function test_un_ninio_no_puede_leer_las_cartas_de_otro_ninio_enviando_su_userid(): void
    {
        $ninioA = $this->crearNinioConCarta('EC-200', 'Carta de A');
        $ninioB = $this->crearNinioConCarta('EC-201', 'Carta de B');

        $tokenA = $ninioA->createToken('test')->plainTextToken;

        $respuesta = $this->withHeader('Authorization', 'Bearer '.$tokenA)
            ->postJson('/api/listar-mis-cartas', ['userId' => $ninioB->id])
            ->assertOk()
            ->json('data');

        $this->assertCount(1, $respuesta);
        $this->assertSame('Carta de A', $respuesta[0]['asunto']);
    }

    public function test_un_ninio_ve_sus_propias_cartas_sin_enviar_userid(): void
    {
        $ninioA = $this->crearNinioConCarta('EC-210', 'Carta propia');

        $tokenA = $ninioA->createToken('test')->plainTextToken;

        $respuesta = $this->withHeader('Authorization', 'Bearer '.$tokenA)
            ->postJson('/api/listar-mis-cartas')
            ->assertOk()
            ->json('data');

        $this->assertCount(1, $respuesta);
        $this->assertSame('Carta propia', $respuesta[0]['asunto']);
    }
}
