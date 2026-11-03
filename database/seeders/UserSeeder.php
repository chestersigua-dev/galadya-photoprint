<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superAdminRole = Role::where('slug', 'super_admin')->first();
        $storeManagerRole = Role::where('slug', 'store_manager')->first();
        $photographerRole = Role::where('slug', 'photographer')->first();

        // Super Admin
        User::firstOrCreate(
            ['email' => 'admin@galadya.com'],
            [
                'name' => 'Chester Sigua (Lead Admin)',
                'password' => Hash::make('password123'),
                'role_id' => $superAdminRole?->id,
                'is_active' => true,
            ]
        );

        // Store Manager
        User::firstOrCreate(
            ['email' => 'manager@galadya.com'],
            [
                'name' => 'Maria Santos (Studio Manager)',
                'password' => Hash::make('password123'),
                'role_id' => $storeManagerRole?->id,
                'is_active' => true,
            ]
        );

        // Studio Photographer / Operator
        User::firstOrCreate(
            ['email' => 'photographer@galadya.com'],
            [
                'name' => 'John Reyes (Lead Operator)',
                'password' => Hash::make('password123'),
                'role_id' => $photographerRole?->id,
                'is_active' => true,
            ]
        );
    }
}

