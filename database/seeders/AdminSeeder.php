
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('ADMIN_PASSWORD');

        if (!$password || strlen($password) < 12) {
            throw new \RuntimeException(
                'ADMIN_PASSWORD must be set and at least 12 characters long.'
            );
        }

        User::updateOrCreate(
            ['email' => 'admin@airflyintl.com'],
            [
                'name' => 'Airfly Administrator',
                'password' => Hash::make($password),
            ]
        );
    }
}
