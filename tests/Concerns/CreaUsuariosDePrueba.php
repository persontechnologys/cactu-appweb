<?php

namespace Tests\Concerns;

use App\Models\Comunidad;
use App\Models\User;
use Spatie\Permission\Models\Role;

trait CreaUsuariosDePrueba
{
    protected function crearAdministrador(array $atributos = []): User
    {
        Role::findOrCreate(config('app.ROLE_ADMIN'), 'web');

        $user = User::factory()->create(array_merge(['estado' => 'ACTIVO'], $atributos));
        $user->assignRole(config('app.ROLE_ADMIN'));

        return $user;
    }

    protected function crearGestor(?Comunidad $comunidad = null, array $atributos = []): User
    {
        Role::findOrCreate(config('app.ROLE_GESTOR'), 'web');

        $user = User::factory()->create(array_merge(['estado' => 'ACTIVO'], $atributos));
        $user->assignRole(config('app.ROLE_GESTOR'));

        if ($comunidad) {
            $comunidad->user_id = $user->id;
            $comunidad->save();
        }

        return $user;
    }

    protected function crearComunidad(?User $gestor = null): Comunidad
    {
        return Comunidad::create([
            'nombre' => fake()->unique()->city(),
            'user_id' => $gestor?->id,
        ]);
    }
}
