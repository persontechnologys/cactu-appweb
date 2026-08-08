<?php

namespace Tests\Feature\Seguridad;

use App\Models\Carta;
use App\Models\Ninio;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Tests\Concerns\CreaUsuariosDePrueba;
use Tests\TestCase;

/**
 * SEG-003: descarga pública y anónima de PDF de carta vía ID secuencial.
 *
 * Escenario de ataque cerrado: GET /api/dcpdf/{id} aceptaba el ID
 * autoincremental de `cartas` sin cifrar, permitiendo iterar
 * /api/dcpdf/1, /api/dcpdf/2... sin autenticación.
 */
class DescargaPdfCartaTest extends TestCase
{
    use RefreshDatabase, CreaUsuariosDePrueba;

    private function crearCartaRespondida(): Carta
    {
        $gestor = $this->crearAdministrador();
        $comunidad = $this->crearComunidad($gestor);
        $ninio = Ninio::create([
            'numero_child' => 'EC-100',
            'nombres_completos' => 'Niño de prueba',
            'genero' => 'Male',
            'fecha_nacimiento' => '2015-01-01',
            'edad' => 10,
            'comunidad_id' => $comunidad->id,
        ]);

        // Carta::boot() asigna user_id = Auth::id() en 'creating', por lo que se
        // necesita un usuario web autenticado para poder crear el registro de prueba.
        Auth::login($gestor);
        $carta = Carta::create([
            'tipo' => 'Contestación',
            'asunto' => 'Asunto de prueba',
            'estado' => 'Respondida',
            'ninio_id' => $ninio->id,
            'comunidad_id' => $comunidad->id,
        ]);
        Auth::logout();

        return $carta;
    }

    private function simularGeneracionPdf(): void
    {
        // config/snappy.php apunta a un binario de Windows (PDF-001, fuera de
        // alcance de esta corrección); se sustituye el wrapper para no depender
        // del binario real de wkhtmltopdf al probar la autorización del enlace.
        $pdfFalso = \Mockery::mock();
        $pdfFalso->shouldReceive('loadView')->andReturnSelf();
        $pdfFalso->shouldReceive('setOption')->andReturnSelf();
        $pdfFalso->shouldReceive('download')->andReturn(response('contenido-pdf-falso'));
        SnappyPdf::swap($pdfFalso);
    }

    public function test_el_id_plano_de_la_carta_ya_no_es_aceptado(): void
    {
        $carta = $this->crearCartaRespondida();

        $this->get('/api/dcpdf/'.$carta->id)
            ->assertStatus(404);
    }

    public function test_un_id_cifrado_valido_permite_la_descarga(): void
    {
        $carta = $this->crearCartaRespondida();
        $this->simularGeneracionPdf();

        $this->get(route('descargarCartaPdf', Crypt::encryptString($carta->id)))
            ->assertOk();
    }

    public function test_listarmiscartas_genera_el_enlace_de_descarga_ya_cifrado(): void
    {
        $carta = $this->crearCartaRespondida();
        $ninio = $carta->ninio;

        $token = $ninio->createToken('test')->plainTextToken;

        // nota: se envía 'userId' porque, al momento de esta corrección (SEG-003),
        // el endpoint aún confía en ese campo (ver SEG-004, corregido por separado).
        $respuesta = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/listar-mis-cartas', ['userId' => $ninio->id])
            ->assertOk()
            ->json('data.0.carta');

        $idCifrado = rawurldecode(basename(parse_url($respuesta, PHP_URL_PATH)));
        $this->assertSame($carta->id, (int) Crypt::decryptString($idCifrado));
    }
}
