<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $adminUser = \App\Models\User::factory()->create();
        \App\Models\Admin::create([
            'id' => Str::uuid(),
            'user_id' => $adminUser->id,
        ]);

        \App\Models\User::factory(9)->create();

        $this->call([
            ClientSeeder::class,
            CompteSeeder::class,
            TransactionSeeder::class,
        ]);
    }
}
