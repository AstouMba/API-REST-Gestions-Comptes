<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        Client::truncate();
        Schema::enableForeignKeyConstraints();
        
        // Créer 15 clients avec des données réalistes sénégalaises
        Client::factory(15)->create();
    }
}
