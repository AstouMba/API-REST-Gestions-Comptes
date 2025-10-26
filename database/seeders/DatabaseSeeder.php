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
        $adminUser = User::factory()->create([
            'is_admin' => true,
        ]);
        Admin::create([
            'id' => Str::uuid(),
            'user_id' => $adminUser->id,
        ]);

        User::factory(9)->create();

        $this->call([
            ClientSeeder::class,
            CompteSeeder::class,
            TransactionSeeder::class,
        ]);
    }
}
