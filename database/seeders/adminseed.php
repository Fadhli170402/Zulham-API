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

        $tour = [
            [
                'address_tour' => 'Pantai Kuta, Badung Regency, Bali',
                'tour_name' => 'Pantai Kuta',
            ],
            [
                'address_tour' => 'Jl. Kuta Lombok, Kuta, Kec. Pujut, Kabupaten Lombok Tengah, Nusa Tenggara Bar. 83573',
                'tour_name' => 'Bukit Merese',
            ],
            [
                'address_tour' => 'Kuta, Kec. Pujut, Kabupaten Lombok Tengah, Nusa Tenggara Bar. 83573',
                'tour_name' => 'Sirkuit Mandalika',
            ],
            [
                'address_tour' => 'Kuta, Kec. Pujut, Kabupaten Lombok Tengah, Nusa Tenggara Bar. 83573',
                'tour_name' => 'Pantai Kuta Lombok',
            ]
        ];
        foreach ($tour as $t) {
            tours::updateOrCreate(
                [
                    'address_tour' => $t['address_tour'],
                    'tour_name' => $t['tour_name'],
                ]
            );
        }

        locations::updateOrCreate(
            [
                'latitude' => -8.409518,
                'longitude' => 115.188919,
                'complete_address' => 'Pantai Kuta, Badung Regency, Bali',
            ]
        );
    }
}
