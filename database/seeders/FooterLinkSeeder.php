<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FooterLink;

class FooterLinkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $links = [
            [
                'name' => 'Terms of Service',
                'url' => '/terms',
                'order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Privacy Policy',
                'url' => '/privacy',
                'order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Cookie Policy',
                'url' => '/cookies',
                'order' => 3,
                'is_active' => true,
            ],
        ];

        foreach ($links as $link) {
            FooterLink::firstOrCreate(
                ['name' => $link['name']],
                $link
            );
        }
    }
}

