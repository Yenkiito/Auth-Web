<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $password = env('INITIAL_OWNER_PASSWORD');
        if (! $password) {
            $this->command?->warn('INITIAL_OWNER_PASSWORD no está definido; OWNER no fue creado.');

            return;
        }
        User::updateOrCreate(['username' => env('INITIAL_OWNER_USERNAME', 'owner')], [
            'name' => 'Kenyra Owner', 'email' => env('INITIAL_OWNER_EMAIL'),
            'password' => $password, 'role' => Role::OWNER, 'status' => 'active',
        ]);
    }
}
