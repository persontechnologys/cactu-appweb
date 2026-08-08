<?php

namespace Tests\Feature\Cartas;

use App\Models\Ninio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreaUsuariosDePrueba;
use Tests\TestCase;

/**
 * ARQ-002: test de regresión para el camino feliz de CartaController::store,
 * escrito antes de extraer RegistrarCartaAction para comprobar que el
 * refactor no cambia el comportamiento observable.
 */
class RegistrarCartaTest extends TestCase
{
    use RefreshDatabase, CreaUsuariosDePrueba;

    public function test_un_gestor_puede_registrar_una_carta_con_boleta_de_su_ninio(): void
    {
        Storage::fake('local');

        $gestor = $this->crearGestor();
        $comunidad = $this->crearComunidad($gestor);
        $ninio = Ninio::create([
            'numero_child' => 'EC-'.uniqid(),
            'nombres_completos' => 'Niño de prueba',
            'genero' => 'Male',
            'fecha_nacimiento' => '2015-01-01',
            'edad' => 10,
            'comunidad_id' => $comunidad->id,
        ]);

        $response = $this->actingAs($gestor)->post(route('cartas.store'), [
            'ninio' => $ninio->id,
            'tipo' => 'Agradecimiento',
            'asunto' => 'Asunto de prueba',
            'boleta' => [UploadedFile::fake()->image('boleta.jpg')],
        ]);

        $response->assertRedirect(route('cartas.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('cartas', [
            'tipo' => 'Agradecimiento',
            'asunto' => 'Asunto de prueba',
            'ninio_id' => $ninio->id,
            'user_id' => $gestor->id,
            'comunidad_id' => $comunidad->id,
        ]);

        $carta = $ninio->cartas()->first();
        $this->assertNotNull($carta);
        $this->assertCount(1, $carta->boletas);

        $boleta = $carta->boletas->first();
        Storage::disk('local')->assertExists($boleta->archivo_imagen);
    }
}
