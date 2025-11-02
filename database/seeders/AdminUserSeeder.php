<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Admin;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        // Vérifie si l'utilisateur admin existe déjà
        $existing = User::where('login', 'admin')->first();

        if ($existing) {
            // Assure qu'il est bien marqué comme admin
            if (!$existing->is_admin) {
                $existing->update(['is_admin' => true]);
            }

            // Crée le record Admin si absent
            if (!Admin::where('user_id', $existing->id)->exists()) {
                Admin::create([
                    'id' => Str::uuid(),
                    'user_id' => $existing->id,
                ]);
            }

            $this->command->info('Admin user already exists — admin record ensured.');
            return;
        }

        // Crée le user admin
        $user = User::create([
            'id' => Str::uuid(),
            'login' => 'admin',
            'password' => Hash::make('password'), 
            'is_admin' => true
        ]);

        // Crée la table admin correspondante
        Admin::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
        ]);

        $this->command->info('Admin user created successfully');
    }
}
