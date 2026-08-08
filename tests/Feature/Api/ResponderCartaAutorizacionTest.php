<?php

namespace Tests\Feature\Api;

use App\Models\Carta;
use App\Models\Ninio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreaUsuariosDePrueba;
use Tests\TestCase;

/**
 * SEG-005: guardarCarta/eliminarCarta (API móvil) no verificaban que la
 * carta perteneciera al niño autenticado.
 *
 * Escenario de ataque cerrado: un niño autenticado podía responder o
 * eliminar la carta de otro niño enviando su ID en el body.
 */
class ResponderCartaAutorizacionTest extends TestCase
{
    use RefreshDatabase, CreaUsuariosDePrueba;

    private function crearNinioConCartaPendiente(string $numeroChild): array
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

    public function test_un_ninio_no_puede_responder_la_carta_de_otro_ninio(): void
    {
        Storage::fake('local');
        [$ninioA] = $this->crearNinioConCartaPendiente('EC-300');
        [, $cartaDeB] = $this->crearNinioConCartaPendiente('EC-301');

        $tokenA = $ninioA->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$tokenA)
            ->postJson('/api/responder-carta-contestacion', [
                'id' => $cartaDeB->id,
                'imagen' => UploadedFile::fake()->image('foto.jpg'),
                'nombre_patrocinador' => 'Patrocinador',
                'agradezco_por' => 'algo',
                'te_cuento_que' => 'algo',
                'pregunta_al_patrocinador' => '¿algo?',
                'detalle' => 'despedida',
            ])
            ->assertStatus(403);

        $this->assertSame('Enviado', $cartaDeB->fresh()->estado);
    }

    public function test_un_ninio_no_puede_eliminar_la_carta_de_otro_ninio(): void
    {
        [$ninioA] = $this->crearNinioConCartaPendiente('EC-310');
        [, $cartaDeB] = $this->crearNinioConCartaPendiente('EC-311');

        $tokenA = $ninioA->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$tokenA)
            ->postJson('/api/eliminar-carta', ['id' => $cartaDeB->id])
            ->assertStatus(403);

        $this->assertEquals(0, $cartaDeB->fresh()->eliminado);
    }

    public function test_un_ninio_si_puede_eliminar_su_propia_carta(): void
    {
        [$ninioA, $cartaDeA] = $this->crearNinioConCartaPendiente('EC-320');

        $tokenA = $ninioA->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$tokenA)
            ->postJson('/api/eliminar-carta', ['id' => $cartaDeA->id])
            ->assertOk();

        $this->assertEquals(1, $cartaDeA->fresh()->eliminado);
    }

    public function test_eliminar_carta_con_id_inexistente_responde_404_en_vez_de_500(): void
    {
        [$ninioA] = $this->crearNinioConCartaPendiente('EC-330');
        $tokenA = $ninioA->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$tokenA)
            ->postJson('/api/eliminar-carta', ['id' => 999999])
            ->assertStatus(404);
    }
}
