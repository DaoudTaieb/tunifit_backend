<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Super Admin utilisateur
        User::firstOrCreate(
            ['email' => 'admin@tunifit.tn'],
            [
            'first_name' => 'Admin',
            'last_name' => 'TuniFit',
            'email' => 'admin@tunifit.tn',
            'password' => Hash::make('admin123'),
            'role' => User::ROLE_SUPER_ADMIN,
            'status' => User::STATUS_ACTIVE,
            'phone' => '+216 12 345 678',
            'email_verified_at' => now(),
            'city' => 'Tunis',
            'country' => 'Tunisie',
            'avatar' => 'https://ui-avatars.com/api/?name=Admin+TuniFit&background=6366f1&color=fff',
            ]
        );

        // Créateurs
        $creators = [
            [
                'first_name' => 'Admin',
                'last_name' => 'TuniFit',
                'email' => 'creator1@tunifit.tn',
                'password' => Hash::make('creator123'),
                'role' => User::ROLE_ADMIN,
                'status' => User::STATUS_ACTIVE,
                'phone' => '+216 11 111 111',
                'email_verified_at' => now(),
                'city' => 'Tunis',
                'country' => 'Tunisie',
                'avatar' => 'https://ui-avatars.com/api/?name=Admin+TuniFit&background=10b981&color=fff',
            ],
            [
                'first_name' => 'Kouki',
                'last_name' => 'Fashion',
                'email' => 'kouki@fashion.tn',
                'password' => Hash::make('kouki123'),
                'role' => User::ROLE_ADMIN,
                'status' => User::STATUS_ACTIVE,
                'phone' => '+216 22 222 222',
                'email_verified_at' => now(),
                'city' => 'Sfax',
                'country' => 'Tunisie',
                'avatar' => 'https://ui-avatars.com/api/?name=Kouki+Fashion&background=f59e0b&color=fff',
            ],
            [
                'first_name' => 'Sara',
                'last_name' => 'Style',
                'email' => 'sara@style.tn',
                'password' => Hash::make('sara123'),
                'role' => User::ROLE_ADMIN,
                'status' => User::STATUS_ACTIVE,
                'phone' => '+216 33 333 333',
                'email_verified_at' => now(),
                'city' => 'Sousse',
                'country' => 'Tunisie',
                'avatar' => 'https://ui-avatars.com/api/?name=Sara+Style&background=ec4899&color=fff',
            ],
            [
                'first_name' => 'Ahmed',
                'last_name' => 'Trend',
                'email' => 'ahmed@trend.tn',
                'password' => Hash::make('ahmed123'),
                'role' => User::ROLE_ADMIN,
                'status' => User::STATUS_ACTIVE,
                'phone' => '+216 44 444 444',
                'email_verified_at' => now(),
                'city' => 'Monastir',
                'country' => 'Tunisie',
                'avatar' => 'https://ui-avatars.com/api/?name=Ahmed+Trend&background=3b82f6&color=fff',
            ],
        ];

        foreach ($creators as $creatorData) {
            User::firstOrCreate(
                ['email' => $creatorData['email']],
                $creatorData
            );
        }

        // Utilisateurs de test
        $users = [
            [
                'first_name' => 'Ahmed',
                'last_name' => 'Ben Ali',
                'email' => 'ahmed@example.com',
                'password' => Hash::make('password123'),
                'role' => User::ROLE_USER,
                'status' => User::STATUS_ACTIVE,
                'phone' => '+216 23 456 789',
                'email_verified_at' => now(),
                'gender' => 'male',
                'city' => 'Tunis',
                'address' => 'Avenue Habib Bourguiba',
                'postal_code' => '1000',
                'country' => 'Tunisie',
            ],
            [
                'first_name' => 'Fatma',
                'last_name' => 'Trabelsi',
                'email' => 'fatma@example.com',
                'password' => Hash::make('password123'),
                'role' => User::ROLE_USER,
                'status' => User::STATUS_ACTIVE,
                'phone' => '+216 34 567 890',
                'email_verified_at' => now(),
                'gender' => 'female',
                'city' => 'Sfax',
                'address' => 'Rue de la République',
                'postal_code' => '3000',
                'country' => 'Tunisie',
            ],
            [
                'first_name' => 'Mohamed',
                'last_name' => 'Jebali',
                'email' => 'mohamed@example.com',
                'password' => Hash::make('password123'),
                'role' => User::ROLE_USER,
                'status' => User::STATUS_ACTIVE,
                'phone' => '+216 45 678 901',
                'email_verified_at' => now(),
                'gender' => 'male',
                'city' => 'Sousse',
                'address' => 'Boulevard de la Corniche',
                'postal_code' => '4000',
                'country' => 'Tunisie',
            ],
        ];

        foreach ($users as $userData) {
            User::firstOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }
    }
}
