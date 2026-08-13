<?php

namespace Tests\Feature\Seguridad;

use App\Http\Controllers\ResponderCartaController;
use App\Models\Comunidad;
use App\Models\Ninio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaUsuariosDePrueba;
use Tests\TestCase;

/**
 * SEG-015: antes, `cartas-ninio.index` recibía el numero_child en texto
 * plano como parámetro de URL (`/cartas-ninio/{numero_child}`), sin límite
 * de intentos y sin expiración — quedaba en el historial del navegador
 * indefinidamente. Ahora se ingresa una vez por formulario (con rate
 * limiting) y queda en una ventana corta de sesión.
 */
class ResponderCartaSesionTest extends TestCase
{
    use RefreshDatabase, CreaUsuariosDePrueba;

    private function crearNinio(): Ninio
    {
        $gestor = $this->crearAdministrador();
        $comunidad = $this->crearComunidad($gestor);

        return Ninio::create([
            'numero_child' => 'EC-'.uniqid(),
            'nombres_completos' => 'Niño de prueba',
            'genero' => 'Male',
            'fecha_nacimiento' => '2015-01-01',
            'edad' => 10,
            'comunidad_id' => $comunidad->id,
        ]);
    }

    public function test_el_formulario_de_ingreso_es_accesible_sin_sesion(): void
    {
        $this->get(route('cartas-ninio.ingresar'))->assertOk();
    }

    public function test_ingresar_con_numero_child_valido_abre_sesion_y_redirige_a_mis_cartas(): void
    {
        $ninio = $this->crearNinio();

        $response = $this->post(route('cartas-ninio.ingresar.store'), [
            'numero_child' => $ninio->numero_child,
        ]);

        $response->assertRedirect(route('cartas-ninio.index'));
        $this->assertSame($ninio->numero_child, session('responder_carta_ninio_numero_child'));
    }

    public function test_ingresar_con_numero_child_inexistente_no_abre_sesion(): void
    {
        $response = $this->post(route('cartas-ninio.ingresar.store'), [
            'numero_child' => 'no-existe-'.uniqid(),
        ]);

        $response->assertSessionHasErrors('numero_child');
        $this->assertNull(session('responder_carta_ninio_numero_child'));
    }

    public function test_index_sin_haber_ingresado_redirige_al_formulario(): void
    {
        $this->get(route('cartas-ninio.index'))
            ->assertRedirect(route('cartas-ninio.ingresar'));
    }

    public function test_index_con_sesion_expirada_redirige_al_formulario(): void
    {
        $ninio = $this->crearNinio();

        $this->withSession([
            'responder_carta_ninio_numero_child' => $ninio->numero_child,
            'responder_carta_ninio_expira' => now()->subMinute(), // ya vencida
        ])->get(route('cartas-ninio.index'))
            ->assertRedirect(route('cartas-ninio.ingresar'));
    }

    public function test_index_con_sesion_valida_muestra_las_cartas_del_nino(): void
    {
        $ninio = $this->crearNinio();

        $response = $this->withSession([
            'responder_carta_ninio_numero_child' => $ninio->numero_child,
            'responder_carta_ninio_expira' => now()->addMinutes(45),
        ])->get(route('cartas-ninio.index'));

        $response->assertOk();
        $response->assertSee($ninio->nombres_completos);
    }

    public function test_ingresar_esta_limitado_por_rate_limiting(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('cartas-ninio.ingresar.store'), ['numero_child' => 'x'])
                ->assertStatus(302);
        }

        $this->post(route('cartas-ninio.ingresar.store'), ['numero_child' => 'x'])
            ->assertStatus(429);
    }

    /**
     * Pensado para dispositivos compartidos (computadora de la comunidad,
     * teléfono familiar): sin esto, la próxima persona que entrara a
     * cartas-ninio dentro de los 45 min vería las cartas del niño anterior.
     */
    public function test_salir_cierra_la_sesion(): void
    {
        $ninio = $this->crearNinio();

        $this->withSession([
            'responder_carta_ninio_numero_child' => $ninio->numero_child,
            'responder_carta_ninio_expira' => now()->addMinutes(45),
        ]);

        $response = $this->post(route('cartas-ninio.salir'));

        $response->assertRedirect(route('cartas-ninio.ingresar'));
        $this->assertNull(session('responder_carta_ninio_numero_child'));

        // Sin sesión, index() vuelve a rebotar al formulario.
        $this->get(route('cartas-ninio.index'))
            ->assertRedirect(route('cartas-ninio.ingresar'));
    }

    public function test_el_boton_de_cerrar_sesion_aparece_cuando_hay_sesion_y_no_en_el_formulario_de_ingreso(): void
    {
        $ninio = $this->crearNinio();

        $this->get(route('cartas-ninio.ingresar'))
            ->assertDontSee(route('cartas-ninio.salir'), false);

        $this->withSession([
            'responder_carta_ninio_numero_child' => $ninio->numero_child,
            'responder_carta_ninio_expira' => now()->addMinutes(45),
        ])->get(route('cartas-ninio.index'))
            ->assertSee(route('cartas-ninio.salir'), false);
    }
}
