<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaUsuariosDePrueba;
use Tests\TestCase;

/**
 * UX-007: welcome.blade.php pasa de ser un alias literal de home.blade.php
 * a una landing pública propia, sin el layout de panel ni contenido interno.
 */
class WelcomeTest extends TestCase
{
    use RefreshDatabase, CreaUsuariosDePrueba;

    public function test_un_visitante_sin_sesion_ve_la_landing_publica(): void
    {
        $response = $this->get(route('welcome'));

        $response->assertOk();
        $response->assertSee('Iniciar Sesión');
        $response->assertDontSee('Acceso Rápido');
        $response->assertDontSee('Actualización Importante');
    }

    public function test_un_usuario_autenticado_es_redirigido_al_dashboard(): void
    {
        $gestor = $this->crearGestor();

        $this->actingAs($gestor)
            ->get(route('welcome'))
            ->assertRedirect(route('home'));
    }
}
