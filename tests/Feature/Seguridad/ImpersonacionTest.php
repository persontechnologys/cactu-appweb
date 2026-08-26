<?php

namespace Tests\Feature\Seguridad;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\Concerns\CreaUsuariosDePrueba;
use Tests\TestCase;

/**
 * SEG-016: acceder como otro usuario ("impersonar").
 *
 * Solo un ADMINISTRADOR puede iniciar sesión como otro usuario sin conocer
 * su contraseña. Cubre: quién puede iniciarlo, que no se pueda anidar, y
 * que siempre exista una forma de volver a la cuenta real.
 */
class ImpersonacionTest extends TestCase
{
    use RefreshDatabase, CreaUsuariosDePrueba;

    public function test_un_gestor_no_puede_acceder_como_otro_usuario(): void
    {
        $gestor = $this->crearGestor();
        $otro = $this->crearGestor();

        $this->actingAs($gestor)
            ->post(route('usuarios.acceder', $otro->id))
            ->assertForbidden();

        $this->assertEquals($gestor->id, Auth::id());
    }

    public function test_un_administrador_puede_acceder_como_otro_usuario(): void
    {
        $admin = $this->crearAdministrador();
        $gestor = $this->crearGestor();

        $this->actingAs($admin)
            ->post(route('usuarios.acceder', $gestor->id))
            ->assertRedirect(route('home'));

        $this->assertEquals($gestor->id, Auth::id());
        $this->assertEquals($admin->id, session('impersonador_id'));

        // el banner de "volver a mi cuenta" queda visible mientras dure la
        // impersonación, en cualquier pantalla del panel.
        $this->get(route('home'))->assertSee('Volver a mi cuenta');
    }

    public function test_un_administrador_no_puede_acceder_como_si_mismo(): void
    {
        $admin = $this->crearAdministrador();

        $this->actingAs($admin)
            ->post(route('usuarios.acceder', $admin->id))
            ->assertForbidden();
    }

    public function test_no_se_puede_anidar_una_impersonacion(): void
    {
        $admin = $this->crearAdministrador();
        $gestor = $this->crearGestor();
        $otroGestor = $this->crearGestor();

        $this->actingAs($admin)->post(route('usuarios.acceder', $gestor->id));
        $this->assertEquals($gestor->id, Auth::id());

        // el propio $gestor, ya sin rol ADMINISTRADOR, ni siquiera puede
        // llegar a la ruta (bloqueada por el middleware role:ADMIN) —
        // confirma que la impersonación anidada queda cerrada en dos capas.
        $this->post(route('usuarios.acceder', $otroGestor->id))
            ->assertForbidden();

        $this->assertEquals($gestor->id, Auth::id());
        $this->assertEquals($admin->id, session('impersonador_id'));
    }

    public function test_puede_volver_a_su_cuenta_despues_de_acceder_como_otro_usuario(): void
    {
        $admin = $this->crearAdministrador();
        $gestor = $this->crearGestor();

        $this->actingAs($admin)->post(route('usuarios.acceder', $gestor->id));
        $this->assertEquals($gestor->id, Auth::id());

        $this->post(route('usuarios.volver'))
            ->assertRedirect(route('usuarios.index'));

        $this->assertEquals($admin->id, Auth::id());
        $this->assertNull(session('impersonador_id'));

        // ya de vuelta como administrador, el panel de usuarios vuelve a
        // estar disponible.
        $this->get(route('usuarios.index'))->assertOk();
    }

    public function test_volver_sin_estar_impersonando_no_falla(): void
    {
        $admin = $this->crearAdministrador();

        $this->actingAs($admin)
            ->post(route('usuarios.volver'))
            ->assertRedirect(route('home'));

        $this->assertEquals($admin->id, Auth::id());
    }
}
