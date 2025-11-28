<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Récupérer les créateurs
        $creators = User::where('role', User::ROLE_ADMIN)->get();
        $creatorIndex = 0;
        $categories = [
            [
                'name' => 'Homme',
                'slug' => 'homme',
                'description' => 'Collection complète de vêtements pour hommes',
                'image' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=1200&h=400&fit=crop',
                'status' => Category::STATUS_ACTIVE,
                'featured' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Femme',
                'slug' => 'femme',
                'description' => 'Collection élégante de vêtements pour femmes',
                'image' => 'https://images.unsplash.com/photo-1483985988355-763728e1935b?w=1200&h=400&fit=crop',
                'status' => Category::STATUS_ACTIVE,
                'featured' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Enfant',
                'slug' => 'enfant',
                'description' => 'Vêtements confortables et colorés pour enfants',
                'image' => 'https://images.unsplash.com/photo-1503454537195-1dcabb73ffb9?w=1200&h=400&fit=crop',
                'status' => Category::STATUS_ACTIVE,
                'featured' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Accessoires',
                'slug' => 'accessoires',
                'description' => 'Accessoires de mode et compléments',
                'image' => 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=1200&h=400&fit=crop',
                'status' => Category::STATUS_ACTIVE,
                'featured' => false,
                'sort_order' => 4,
            ],
        ];

        foreach ($categories as $categoryData) {
            // Assigner un créateur à chaque catégorie principale
            if ($creators->count() > 0) {
                $categoryData['created_by'] = $creators[$creatorIndex % $creators->count()]->id;
                $creatorIndex++;
            }
            
            Category::firstOrCreate(
                ['slug' => $categoryData['slug']],
                $categoryData
            );
        }

        // Sous-catégories pour Homme
        $hommeCategory = Category::where('slug', 'homme')->first();
        if ($hommeCategory) {
            $hommeSubcategories = [
                [
                    'name' => 'T-Shirts',
                    'slug' => 'homme-t-shirts',
                    'description' => 'T-shirts confortables pour hommes',
                    'parent_id' => $hommeCategory->id,
                    'status' => Category::STATUS_ACTIVE,
                    'sort_order' => 1,
                ],
                [
                    'name' => 'Pantalons',
                    'slug' => 'homme-pantalons',
                    'description' => 'Pantalons et jeans pour hommes',
                    'parent_id' => $hommeCategory->id,
                    'status' => Category::STATUS_ACTIVE,
                    'sort_order' => 2,
                ],
                [
                    'name' => 'Chemises',
                    'slug' => 'homme-chemises',
                    'description' => 'Chemises élégantes pour hommes',
                    'parent_id' => $hommeCategory->id,
                    'status' => Category::STATUS_ACTIVE,
                    'sort_order' => 3,
                ],
            ];

            foreach ($hommeSubcategories as $subcategoryData) {
                // Assigner le même créateur que la catégorie parente
                $subcategoryData['created_by'] = $hommeCategory->created_by;
                Category::firstOrCreate(
                    ['slug' => $subcategoryData['slug']],
                    $subcategoryData
                );
            }
        }

        // Sous-catégories pour Femme
        $femmeCategory = Category::where('slug', 'femme')->first();
        if ($femmeCategory) {
            $femmeSubcategories = [
                [
                    'name' => 'Robes',
                    'slug' => 'femme-robes',
                    'description' => 'Robes élégantes et modernes',
                    'parent_id' => $femmeCategory->id,
                    'status' => Category::STATUS_ACTIVE,
                    'sort_order' => 1,
                ],
                [
                    'name' => 'Hauts',
                    'slug' => 'femme-hauts',
                    'description' => 'Tops, blouses et chemisiers',
                    'parent_id' => $femmeCategory->id,
                    'status' => Category::STATUS_ACTIVE,
                    'sort_order' => 2,
                ],
                [
                    'name' => 'Pantalons',
                    'slug' => 'femme-pantalons',
                    'description' => 'Pantalons et leggings pour femmes',
                    'parent_id' => $femmeCategory->id,
                    'status' => Category::STATUS_ACTIVE,
                    'sort_order' => 3,
                ],
            ];

            foreach ($femmeSubcategories as $subcategoryData) {
                // Assigner le même créateur que la catégorie parente
                $subcategoryData['created_by'] = $femmeCategory->created_by;
                Category::firstOrCreate(
                    ['slug' => $subcategoryData['slug']],
                    $subcategoryData
                );
            }
        }

        // Sous-catégories pour Enfant
        $enfantCategory = Category::where('slug', 'enfant')->first();
        if ($enfantCategory) {
            $enfantSubcategories = [
                [
                    'name' => 'T-Shirts',
                    'slug' => 'enfant-t-shirts',
                    'description' => 'T-shirts colorés et confortables pour enfants',
                    'parent_id' => $enfantCategory->id,
                    'status' => Category::STATUS_ACTIVE,
                    'sort_order' => 1,
                ],
                [
                    'name' => 'Pantalons',
                    'slug' => 'enfant-pantalons',
                    'description' => 'Pantalons et shorts pour enfants',
                    'parent_id' => $enfantCategory->id,
                    'status' => Category::STATUS_ACTIVE,
                    'sort_order' => 2,
                ],
                [
                    'name' => 'Robes',
                    'slug' => 'enfant-robes',
                    'description' => 'Robes et jupes pour filles',
                    'parent_id' => $enfantCategory->id,
                    'status' => Category::STATUS_ACTIVE,
                    'sort_order' => 3,
                ],
            ];

            foreach ($enfantSubcategories as $subcategoryData) {
                // Assigner le même créateur que la catégorie parente
                $subcategoryData['created_by'] = $enfantCategory->created_by;
                Category::firstOrCreate(
                    ['slug' => $subcategoryData['slug']],
                    $subcategoryData
                );
            }
        }

        // Sous-catégories pour Accessoires
        $accessoiresCategory = Category::where('slug', 'accessoires')->first();
        if ($accessoiresCategory) {
            $accessoiresSubcategories = [
                [
                    'name' => 'Sacs',
                    'slug' => 'accessoires-sacs',
                    'description' => 'Sacs à main, sacs à dos et portefeuilles',
                    'parent_id' => $accessoiresCategory->id,
                    'status' => Category::STATUS_ACTIVE,
                    'sort_order' => 1,
                ],
                [
                    'name' => 'Chaussures',
                    'slug' => 'accessoires-chaussures',
                    'description' => 'Chaussures de mode et sport',
                    'parent_id' => $accessoiresCategory->id,
                    'status' => Category::STATUS_ACTIVE,
                    'sort_order' => 2,
                ],
                [
                    'name' => 'Bijoux',
                    'slug' => 'accessoires-bijoux',
                    'description' => 'Bijoux et accessoires de mode',
                    'parent_id' => $accessoiresCategory->id,
                    'status' => Category::STATUS_ACTIVE,
                    'sort_order' => 3,
                ],
            ];

            foreach ($accessoiresSubcategories as $subcategoryData) {
                // Assigner le même créateur que la catégorie parente
                $subcategoryData['created_by'] = $accessoiresCategory->created_by;
                Category::firstOrCreate(
                    ['slug' => $subcategoryData['slug']],
                    $subcategoryData
                );
            }
        }
    }
}
