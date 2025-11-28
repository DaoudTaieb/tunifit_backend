<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Testimonial;

class TestimonialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $testimonials = [
            [
                'name' => 'Sarah Johnson',
                'role' => 'Fashion Designer',
                'content' => 'J\'adore la qualité des vêtements TuniFit ! Les matériaux sont excellents, les coupes sont parfaites et la livraison est rapide. Je recommande vivement !',
                'rating' => 5,
                'image_url' => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=400&h=400&fit=crop',
                'is_active' => true,
                'order' => 1,
            ],
            [
                'name' => 'Michael Chen',
                'role' => 'Software Engineer',
                'content' => 'La collection TuniFit offre un excellent rapport qualité-prix. Les vêtements sont confortables, durables et toujours à la mode. Service client impeccable !',
                'rating' => 5,
                'image_url' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&h=400&fit=crop',
                'is_active' => true,
                'order' => 2,
            ],
            [
                'name' => 'Emma Rodriguez',
                'role' => 'Marketing Executive',
                'content' => 'Service client exceptionnel ! J\'ai eu un problème avec une commande et ils l\'ont résolu immédiatement. Les vêtements sont de grande qualité et je reçois des compliments tout le temps.',
                'rating' => 4,
                'image_url' => 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=400&h=400&fit=crop',
                'is_active' => true,
                'order' => 3,
            ],
        ];

        foreach ($testimonials as $testimonial) {
            Testimonial::firstOrCreate(
                ['name' => $testimonial['name']],
                $testimonial
            );
        }
    }
}

