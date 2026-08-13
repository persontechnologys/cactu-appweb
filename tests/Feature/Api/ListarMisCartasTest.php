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

    /**
     * Fija la forma exacta de cada item de "data" (ARQ-006, Paso A) antes de
     * introducir CartaResource, para garantizar que el refactor no cambia
     * el contrato: mismos 9 campos, mismos valores, para los dos casos que
     * ramifican en el código real (carta con/sin archivo adjunto, y
     * respondida/sin responder).
     */
    public function test_la_forma_de_cada_item_no_cambia_con_o_sin_pdf_adjunto(): void
    {
        $gestor = $this->crearAdministrador();
        $comunidad = $this->crearComunidad($gestor);
        $ninio = Ninio::create([
            'numero_child' => 'EC-220',
            'nombres_completos' => 'Niño con y sin PDF',
            'genero' => 'Male',
            'fecha_nacimiento' => '2015-01-01',
            'edad' => 10,
            'comunidad_id' => $comunidad->id,
        ]);

        Auth::login($gestor);
        $sinPdf = Carta::create([
            'tipo' => 'Contestación',
            'asunto' => 'Sin PDF',
            'estado' => 'Enviado',
            'ninio_id' => $ninio->id,
            'comunidad_id' => $comunidad->id,
        ]);
        $conPdf = Carta::create([
            'tipo' => 'Agradecimiento',
            'asunto' => 'Con PDF',
            'estado' => 'Respondida',
            'archivo_pdf' => 'cartas/adjunto.pdf',
            'ninio_id' => $ninio->id,
            'comunidad_id' => $comunidad->id,
        ]);
        Auth::logout();

        $token = $ninio->createToken('test')->plainTextToken;

        $data = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/listar-mis-cartas')
            ->assertOk()
            ->json('data');

        $porId = collect($data)->keyBy('id');
        $edadEsperada = \Carbon\Carbon::parse('2015-01-01')->age;

        $this->assertSame([
            'created_at' => $sinPdf->created_at->format('d/m/Y H:i:s'),
            'id' => $sinPdf->id,
            'asunto' => 'Sin PDF',
            'tipo_carta_nombre' => 'Contestación',
            'estado' => 'Enviado',
            'edad' => $edadEsperada,
            'archivo' => 'no',
            'carta' => 'no',
            'carta_nombre' => '',
            // 'eliminado' no tiene cast a boolean en el modelo Carta: el
            // valor real serializado por json() es el entero de SQLite (0/1),
            // no true/false — verificado, no asumido.
            'eliminado' => 0,
        ], $porId[$sinPdf->id]);

        $itemConPdf = $porId[$conPdf->id];
        $this->assertSame($conPdf->created_at->format('d/m/Y H:i:s'), $itemConPdf['created_at']);
        $this->assertSame('Con PDF', $itemConPdf['asunto']);
        $this->assertSame('Agradecimiento', $itemConPdf['tipo_carta_nombre']);
        $this->assertSame('Respondida', $itemConPdf['estado']);
        $this->assertSame('Carta-de-Agradecimiento-'.$conPdf->id.'.pdf', $itemConPdf['carta_nombre']);
        $this->assertSame(0, $itemConPdf['eliminado']);

        // 'archivo'/'carta' cifran el id con una función no determinística
        // (IV aleatorio), así que se compara descifrando en vez de comparar
        // el string completo.
        $idCifradoArchivo = basename(parse_url($itemConPdf['archivo'], PHP_URL_PATH));
        $idCifradoCarta = basename(parse_url($itemConPdf['carta'], PHP_URL_PATH));
        $this->assertSame((string) $conPdf->id, \Illuminate\Support\Facades\Crypt::decryptString($idCifradoArchivo));
        $this->assertSame((string) $conPdf->id, \Illuminate\Support\Facades\Crypt::decryptString($idCifradoCarta));
        $this->assertStringContainsString('/api/ver-archivo-pdf-por-ninio/', $itemConPdf['archivo']);
        $this->assertStringContainsString('/api/dcpdf/', $itemConPdf['carta']);
    }
}
