<?php

namespace Tests\Feature\Seguridad;

use App\DataTables\NinioDataTable;
use App\Models\Ninio;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\Concerns\CreaUsuariosDePrueba;
use Tests\TestCase;

/**
 * SEG-002: exposición y modificación de niños de cualquier comunidad.
 *
 * Escenario de ataque cerrado: un GESTOR autenticado no debía poder acceder
 * a /ninios (gestión global) ni ver/editar niños de comunidades ajenas.
 * El acceso legítimo por comunidad sigue disponible vía /mis-ninios.
 */
class NiniosAutorizacionTest extends TestCase
{
    use RefreshDatabase, CreaUsuariosDePrueba;

    private function crearNinio(int $comunidadId, string $numeroChild): Ninio
    {
        return Ninio::create([
            'numero_child' => $numeroChild,
            'nombres_completos' => 'Niño de prueba',
            'genero' => 'Male',
            'fecha_nacimiento' => '2015-01-01',
            'edad' => Carbon::parse('2015-01-01')->age,
            'comunidad_id' => $comunidadId,
        ]);
    }

    public function test_un_gestor_no_puede_listar_ninios_globalmente(): void
    {
        $gestor = $this->crearGestor();

        $this->actingAs($gestor)
            ->get(route('ninios.index'))
            ->assertForbidden();
    }

    public function test_un_gestor_no_puede_editar_un_ninio_de_otra_comunidad(): void
    {
        $gestorA = $this->crearGestor();
        $comunidadB = $this->crearComunidad($this->crearGestor());
        $ninioAjeno = $this->crearNinio($comunidadB->id, 'EC-002');

        $this->actingAs($gestorA)
            ->get(route('ninios.edit', $ninioAjeno))
            ->assertForbidden();
    }

    public function test_un_administrador_si_puede_listar_ninios(): void
    {
        $admin = $this->crearAdministrador();

        $this->actingAs($admin)
            ->get(route('ninios.index'))
            ->assertOk();
    }

    public function test_el_datatable_de_ninios_filtra_por_comunidad_para_un_gestor(): void
    {
        $comunidadPropia = $this->crearComunidad();
        $gestor = $this->crearGestor($comunidadPropia);
        $comunidadAjena = $this->crearComunidad($this->crearGestor());

        $ninioPropio = $this->crearNinio($comunidadPropia->id, 'EC-010');
        $this->crearNinio($comunidadAjena->id, 'EC-011');

        Auth::login($gestor);
        $resultado = app(NinioDataTable::class)->query(new Ninio())->get();

        $this->assertCount(1, $resultado);
        $this->assertSame($ninioPropio->id, $resultado->first()->id);
    }

    public function test_el_datatable_de_ninios_no_filtra_para_un_administrador(): void
    {
        $admin = $this->crearAdministrador();
        $comunidadA = $this->crearComunidad();
        $comunidadB = $this->crearComunidad();
        $this->crearNinio($comunidadA->id, 'EC-020');
        $this->crearNinio($comunidadB->id, 'EC-021');

        Auth::login($admin);
        $resultado = app(NinioDataTable::class)->query(new Ninio())->get();

        $this->assertCount(2, $resultado);
    }
}
