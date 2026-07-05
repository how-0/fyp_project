<?php

namespace Database\Seeders;

use App\Models\Destination;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@mail.com'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('admin@mail.com'),
                'is_admin' => true,
            ]
        );

        $destinations = [
            ['name' => 'George Town', 'state' => 'Penang', 'category' => 'heritage', 'lat' => 5.4141, 'lng' => 100.3288, 'is_featured' => true],
            ['name' => 'Langkawi Sky Bridge', 'state' => 'Kedah', 'category' => 'nature', 'lat' => 6.375, 'lng' => 99.675, 'is_featured' => true],
            ['name' => 'Petronas Twin Towers', 'state' => 'Kuala Lumpur', 'category' => 'sightseeing', 'lat' => 3.1578, 'lng' => 101.7118, 'is_featured' => true],
            ['name' => 'Jonker Street', 'state' => 'Melaka', 'category' => 'food', 'lat' => 2.1945, 'lng' => 102.2486, 'is_featured' => true],
            ['name' => 'Cameron Highlands', 'state' => 'Pahang', 'category' => 'nature', 'lat' => 4.4721, 'lng' => 101.3791, 'is_featured' => true],
        ];

        foreach ($destinations as $destination) {
            Destination::updateOrCreate(
                ['name' => $destination['name'], 'state' => $destination['state']],
                array_merge($destination, [
                    'description' => "Featured Malaysia destination: {$destination['name']}",
                ])
            );
        }
    }
}
