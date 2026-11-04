
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@airflyintl.com'],
            [
                'name' => 'Airfly Administrator',
                'password' => Hash::make(
                    env('ADMIN_PASSWORD', 'Elias@123!')
                ),
            ]
        );
    }
}
