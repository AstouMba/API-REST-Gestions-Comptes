<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Crée un admin fixe via le seeder dédié (login: admin / password: password)
        $this->call([AdminUserSeeder::class]);

        User::factory(9)->create();

        $this->call([
            ClientSeeder::class,
            CompteSeeder::class,
            TransactionSeeder::class,
        ]);
    }
}
