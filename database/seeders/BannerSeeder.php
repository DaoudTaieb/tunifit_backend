<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Banner;

class BannerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Bannière 1 - Collection Homme
        Banner::firstOrCreate(
            ['title' => 'Nouvelle Collection Homme'],
            [
                'description' => 'Découvrez notre nouvelle collection de vêtements pour homme. Style moderne et confortable.',
                'image_url' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=1200&h=600&fit=crop',
                'button_text' => 'Découvrir',
                'button_link' => '/products?gender=Homme',
                'order' => 1,
                'is_active' => true,
            ]
        );

        // Bannière 2 - Collection Femme
        Banner::firstOrCreate(
            ['title' => 'Collection Femme Printemps-Été'],
            [
                'description' => 'Tendances mode pour femme. Élégance et style à chaque saison.',
                'image_url' => 'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=1200&h=600&fit=crop',
                'button_text' => 'Voir la collection',
                'button_link' => '/products?gender=Femme',
                'order' => 2,
                'is_active' => true,
            ]
        );

        // Bannière 3 - Promotions
        Banner::firstOrCreate(
            ['title' => 'Soldes - Jusqu\'à -50%'],
            [
                'description' => 'Profitez de nos meilleures offres. Des réductions exceptionnelles sur toute la collection.',
                'image_url' => 'https://images.unsplash.com/photo-1607082349566-187342175e2f?w=1200&h=600&fit=crop',
                'button_text' => 'Acheter maintenant',
                'button_link' => '/products?promotion=true',
                'order' => 3,
                'is_active' => true,
            ]
        );
    }
}

