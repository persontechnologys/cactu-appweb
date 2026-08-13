<?php

namespace Tests\Feature\Seguridad;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Concerns\CreaUsuariosDePrueba;
use Tests\TestCase;

/**
 * SEG-001: escalación de privilegios en /usuarios y /comunidad.
 *
 * Escenario de ataque cerrado: un GESTOR autenticado no debía poder acceder
 * a /usuarios ni asignarse a sí mismo (o a otros) el rol ADMINISTRADOR
 * mediante PUT /usuarios/{id}.
 */
class UsuariosAutorizacionTest extends TestCase
{
    use RefreshDatabase, CreaUsuariosDePrueba;

    public function test_un_gestor_no_puede_listar_usuarios(): void
    {
        $gestor = $this->crearGestor();

        $this->actingAs($gestor)
            ->get(route('usuarios.index'))
            ->assertForbidden();
    }

    public function test_un_gestor_no_puede_listar_comunidades(): void
    {
        $gestor = $this->crearGestor();

        $this->actingAs($gestor)
            ->get(route('comunidad.index'))
            ->assertForbidden();
    }

    public function test_un_administrador_si_puede_listar_usuarios(): void
    {
        $admin = $this->crearAdministrador();

        $this->actingAs($admin)
            ->get(route('usuarios.index'))
            ->assertOk();
    }

    public function test_un_gestor_no_puede_autoasignarse_el_rol_administrador(): void
    {
        $gestor = $this->crearGestor();
        $rolAdmin = Role::findOrCreate(config('app.ROLE_ADMIN'), 'web');

        $this->actingAs($gestor)
            ->put(route('usuarios.update', $gestor->id), [
                'name' => $gestor->name,
                'email' => $gestor->email,
                'estado' => 'ACTIVO',
                'provincia' => 'Pichincha',
                'roles' => [$rolAdmin->id],
            ])
            ->assertForbidden();

        $this->assertFalse($gestor->fresh()->hasRole(config('app.ROLE_ADMIN')));
    }

    public function test_un_administrador_no_puede_ascender_a_otro_usuario_a_administrador_desde_el_formulario(): void
    {
        $admin = $this->crearAdministrador();
        $gestor = $this->crearGestor();
        $rolAdmin = Role::findOrCreate(config('app.ROLE_ADMIN'), 'web');

        $this->actingAs($admin)
            ->put(route('usuarios.update', $gestor->id), [
                'name' => $gestor->name,
                'email' => $gestor->email,
                'estado' => 'ACTIVO',
                'provincia' => 'Pichincha',
                'roles' => [$rolAdmin->id],
            ])
            ->assertSessionHasErrors('roles.0');

        $this->assertFalse($gestor->fresh()->hasRole(config('app.ROLE_ADMIN')));
    }

    /**
     * BUG-001: UpdateRq usaba $this->id (siempre null, la ruta usa el parámetro
     * "usuario") para excluir al propio usuario de la regla unique:email. Se
     * corrigió a $this->route('usuario').
     */
    public function test_editar_usuario_sin_cambiar_su_email_no_falla_la_validacion(): void
    {
        $admin = $this->crearAdministrador();
        $gestor = $this->crearGestor();
        $rolGestor = Role::findOrCreate(config('app.ROLE_GESTOR'), 'web');

        $this->actingAs($admin)
            ->put(route('usuarios.update', $gestor->id), [
                'name' => $gestor->name,
                'email' => $gestor->email,
                'estado' => 'ACTIVO',
                'provincia' => 'Pichincha',
                'roles' => [$rolGestor->id],
            ])
            ->assertSessionDoesntHaveErrors()
            ->assertRedirect(route('usuarios.index'));
    }

    public function test_un_administrador_no_puede_crear_un_usuario_con_rol_administrador(): void
    {
        $admin = $this->crearAdministrador();
        $rolAdmin = Role::findOrCreate(config('app.ROLE_ADMIN'), 'web');

        $this->actingAs($admin)
            ->post(route('usuarios.store'), [
                'name' => 'Nuevo usuario',
                'email' => 'nuevo@example.com',
                'password' => 'password',
                'estado' => 'ACTIVO',
                'provincia' => 'Pichincha',
                'roles' => [$rolAdmin->id],
            ])
            ->assertSessionHasErrors('roles.0');

        $this->assertDatabaseMissing('users', ['email' => 'nuevo@example.com']);
    }
}
