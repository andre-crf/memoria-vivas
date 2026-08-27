<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'nome' => 'Administrador Memórias Vivas',
            'email' => 'admin@memorias.test',
            'role' => 'admin',
            'status' => 'ativo',
        ]);
    }
}
