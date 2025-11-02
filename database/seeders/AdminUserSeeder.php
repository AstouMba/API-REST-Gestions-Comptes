<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        // Ne pas écraser les utilisateurs existants : créer seulement si absent
        $existing = User::where('login', 'admin')->first();

        if ($existing) {
            // S'assurer que l'utilisateur est marqué admin et qu'il y a une ligne dans admins
            if (!$existing->is_admin) {
                $existing->update(['is_admin' => true]);
            }

            if (!Admin::where('user_id', $existing->id)->exists()) {
                Admin::create([
                    'id' => Str::uuid(),
                    'user_id' => $existing->id,
                ]);
            }

            $this->command->info('Admin user already exists — ensured admin record.');
            return;
        }

            $user = User::create([
            'id' => Str::uuid(),
            'login' => 'admin',
            'password' => 'password',
            'is_admin' => true
        ]);


        Admin::create([
            'id' => Str::uuid(),
            'user_id' => $user->id
        ]);

        $this->command->info('Admin user created successfully');
    }
}