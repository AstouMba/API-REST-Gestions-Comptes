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
        $user = User::create([
            'id' => Str::uuid(),
            'login' => 'admin',
            'password' => bcrypt('password'),
            'is_admin' => true
        ]);

        Admin::create([
            'id' => Str::uuid(),
            'user_id' => $user->id
        ]);

        $this->command->info('Admin user created successfully');
    }
}