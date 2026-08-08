<?php

namespace Tests\Feature\Seguridad;

use App\Models\Carta;
use App\Models\Ninio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\Concerns\CreaUsuariosDePrueba;
use Tests\TestCase;

/**
 * SEG-007: CartaController no autorizaba show/edit/update (solo destroy).
 *
 * Escenario de ataque cerrado: un gestor autenticado podía navegar a
 * /cartas/{id}, /cartas/{id}/edit o hacer PUT /cartas/{id} sobre una
 * carta de otro gestor/comunidad, simplemente conociendo o adivinando
 * el ID numérico.
 */
class CartaAutorizacionTest extends TestCase
{
    use RefreshDatabase, CreaUsuariosDePrueba;

    private function crearCartaDeGestor(): array
    {
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

        Auth::login($gestor);
        $carta = Carta::create([
            'tipo' => 'Contestación',
            'asunto' => 'Asunto de prueba',
            'estado' => 'Enviado',
            'ninio_id' => $ninio->id,
            'comunidad_id' => $comunidad->id,
        ]);
        Auth::logout();

        return [$gestor, $carta];
    }

    public function test_un_gestor_no_puede_ver_la_carta_de_otro_gestor(): void
    {
        [, $cartaAjena] = $this->crearCartaDeGestor();
        $otroGestor = $this->crearGestor();

        $this->actingAs($otroGestor)
            ->get(route('cartas.show', $cartaAjena))
            ->assertForbidden();
    }

    public function test_un_gestor_no_puede_editar_la_carta_de_otro_gestor(): void
    {
        [, $cartaAjena] = $this->crearCartaDeGestor();
        $otroGestor = $this->crearGestor();

        $this->actingAs($otroGestor)
            ->get(route('cartas.edit', $cartaAjena))
            ->assertForbidden();

        $this->actingAs($otroGestor)
            ->put(route('cartas.update', $cartaAjena), ['detalle' => 'contenido inyectado'])
            ->assertForbidden();

        $this->assertNull($cartaAjena->fresh()->detalle);
    }

    public function test_el_dueno_si_puede_ver_y_editar_su_propia_carta(): void
    {
        [$gestor, $carta] = $this->crearCartaDeGestor();

        $this->actingAs($gestor)
            ->get(route('cartas.show', $carta))
            ->assertOk();

        $this->actingAs($gestor)
            ->put(route('cartas.update', $carta), ['detalle' => 'mi respuesta'])
            ->assertRedirect(route('cartas.show', $carta));

        $this->assertSame('mi respuesta', $carta->fresh()->detalle);
    }

    public function test_un_administrador_puede_ver_la_carta_de_cualquier_gestor(): void
    {
        [, $carta] = $this->crearCartaDeGestor();
        $admin = $this->crearAdministrador();

        $this->actingAs($admin)
            ->get(route('cartas.show', $carta))
            ->assertOk();
    }
}
