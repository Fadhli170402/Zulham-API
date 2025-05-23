<?php

namespace Database\Seeders;

use App\Models\locations;
use App\Models\tours;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class adminseed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            [
                'username' => 'admin',
                'email' => 'admin@gmail.com',
                'password' => bcrypt('admin123'),
                'role' => 'admin',
            ]
        );

        tours::updateOrCreate(
            [
                'address_tour' => 'Pantai Kuta, Badung Regency, Bali',
                'tour_name' => 'Pantai Kuta',
            ]
        );

        locations::updateOrCreate(
            [
                'latitude' => -8.409518,
                'longitude' => 115.188919,
                'complete_address' => 'Pantai Kuta, Badung Regency, Bali',
            ]
        );
    }
}
