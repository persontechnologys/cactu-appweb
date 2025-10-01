<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $role = Role::updateOrCreate(['name' => config('app.ROLE_ADMIN')]);
        Role::updateOrCreate(['name' => config('app.ROLE_GESTOR')]);
        
        // crear o actualizar usuario por email si no existe
        $user = User::updateOrCreate(
            ['email' => config('app.EMAIL_ADMIN')],
            [
                'name' => config('app.EMAIL_ADMIN'),
                'email' => config('app.EMAIL_ADMIN'),
                'password' => Hash::make(config('app.PASSWORD_ADMIN')),
            ]
        );

        $user->syncRoles($role);


    }
}
