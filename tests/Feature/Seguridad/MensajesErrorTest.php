<?php

namespace Tests\Feature\Seguridad;

use App\Models\Ninio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Concerns\CreaUsuariosDePrueba;
use Tests\TestCase;

/**
 * SEG-008: mensajes de excepción ($th->getMessage()) expuestos al usuario final.
 *
 * Escenario cerrado: un error interno (por ejemplo, un ID de carta que no
 * existe) ya no debe filtrar el mensaje real de la excepción en la
 * respuesta; solo debe quedar registrado en el log del servidor.
 */
class MensajesErrorTest extends TestCase
{
    use RefreshDatabase, CreaUsuariosDePrueba;

    public function test_guardar_carta_no_filtra_el_mensaje_interno_de_la_excepcion(): void
    {
        $gestor = $this->crearAdministrador();
        $comunidad = $this->crearComunidad($gestor);
        $ninio = Ninio::create([
            'numero_child' => 'EC-400',
            'nombres_completos' => 'Niño de prueba',
            'genero' => 'Male',
            'fecha_nacimiento' => '2015-01-01',
            'edad' => 10,
            'comunidad_id' => $comunidad->id,
        ]);
        $token = $ninio->createToken('test')->plainTextToken;

        $respuesta = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/responder-carta-contestacion', [
                'id' => 999999, // no existe -> Carta::findOrFail lanza ModelNotFoundException
                'imagen' => UploadedFile::fake()->image('foto.jpg'),
                'nombre_patrocinador' => 'Patrocinador',
                'agradezco_por' => 'algo',
                'te_cuento_que' => 'algo',
                'pregunta_al_patrocinador' => '¿algo?',
                'detalle' => 'despedida',
            ]);

        $respuesta->assertJson(['error' => 'Carta no registrado vuelva intentar']);
        $respuesta->assertDontSee('No query results', false);
        $respuesta->assertDontSee('ModelNotFoundException', false);
    }
}
