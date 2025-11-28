<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Récupérer tous les créateurs (admins)
        $creators = User::where('role', User::ROLE_ADMIN)->get();
        if ($creators->isEmpty()) {
            return; // Pas de créateurs, on ne peut pas créer de produits
        }
        
        $creatorIndex = 0;

        // Récupérer les catégories
        $hommeTshirts = Category::where('slug', 'homme-t-shirts')->first();
        $hommePantalons = Category::where('slug', 'homme-pantalons')->first();
        $hommeChemises = Category::where('slug', 'homme-chemises')->first();
        $femmeRobes = Category::where('slug', 'femme-robes')->first();
        $femmeHauts = Category::where('slug', 'femme-hauts')->first();
        $femmePantalons = Category::where('slug', 'femme-pantalons')->first();
        $enfantTshirts = Category::where('slug', 'enfant-t-shirts')->first();
        $enfantPantalons = Category::where('slug', 'enfant-pantalons')->first();
        $enfantRobes = Category::where('slug', 'enfant-robes')->first();
        $accessoiresSacs = Category::where('slug', 'accessoires-sacs')->first();
        $accessoiresChaussures = Category::where('slug', 'accessoires-chaussures')->first();
        $accessoiresBijoux = Category::where('slug', 'accessoires-bijoux')->first();

        $products = [
            // Produits Homme - T-Shirts
            [
                'name' => 'T-Shirt Premium Coton',
                'description' => 'T-shirt en coton 100% bio, confortable et respirant. Parfait pour un look décontracté.',
                'price' => 45.00,
                'stock' => 50,
                'category_id' => $hommeTshirts?->id,
                'images' => [
                    'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=800&h=800&fit=crop',
                    'https://images.unsplash.com/photo-1583743814966-8936f5b7be1a?w=800&h=800&fit=crop'
                ],
                'marque' => 'Nike',
                'couleur' => 'Blanc',
                'style' => 'Casual',
                'gender' => 'Homme',
                'sizes' => ['S', 'M', 'L', 'XL'],
                'material' => 'Coton 100%',
            ],
            [
                'name' => 'T-Shirt Sport Performance',
                'description' => 'T-shirt technique pour le sport, évacue la transpiration et sèche rapidement.',
                'price' => 65.00,
                'stock' => 30,
                'category_id' => $hommeTshirts?->id,
                'images' => ['https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=800&h=800&fit=crop'],
                'marque' => 'Adidas',
                'couleur' => 'Noir',
                'style' => 'Sport',
                'gender' => 'Homme',
                'sizes' => ['M', 'L', 'XL', 'XXL'],
                'material' => 'Polyester',
            ],
            [
                'name' => 'T-Shirt Vintage',
                'description' => 'T-shirt au style rétro avec design unique, 100% coton.',
                'price' => 35.00,
                'stock' => 25,
                'category_id' => $hommeTshirts?->id,
                'images' => ['https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=800&q=80'],
                'marque' => 'Zara',
                'couleur' => 'Bleu',
                'style' => 'Vintage',
                'gender' => 'Homme',
                'sizes' => ['S', 'M', 'L'],
                'material' => 'Coton',
            ],

            // Produits Homme - Pantalons
            [
                'name' => 'Jean Slim Fit',
                'description' => 'Jean moderne coupe slim, confortable et élégant. Idéal pour toutes les occasions.',
                'price' => 120.00,
                'stock' => 40,
                'category_id' => $hommePantalons?->id,
                'images' => [
                    'https://images.unsplash.com/photo-1542272604-787c3835535d?w=800&h=800&fit=crop',
                    'https://images.unsplash.com/photo-1473966968600-fa801b869a1a?w=800&h=800&fit=crop'
                ],
                'marque' => 'Levi\'s',
                'couleur' => 'Bleu',
                'style' => 'Casual',
                'gender' => 'Homme',
                'sizes' => ['30', '32', '34', '36', '38'],
                'material' => 'Denim',
            ],
            [
                'name' => 'Pantalon Chino',
                'description' => 'Pantalon chino classique, parfait pour un look business casual.',
                'price' => 85.00,
                'stock' => 35,
                'category_id' => $hommePantalons?->id,
                'images' => ['https://images.unsplash.com/photo-1473966968600-fa801b869a1a?w=800&h=800&fit=crop'],
                'marque' => 'Gap',
                'couleur' => 'Beige',
                'style' => 'Formel',
                'gender' => 'Homme',
                'sizes' => ['30', '32', '34', '36'],
                'material' => 'Coton',
            ],

            // Produits Homme - Chemises
            [
                'name' => 'Chemise Formelle Blanche',
                'description' => 'Chemise classique en coton, parfaite pour le bureau et les occasions formelles.',
                'price' => 95.00,
                'stock' => 45,
                'category_id' => $hommeChemises?->id,
                'images' => ['https://images.unsplash.com/photo-1603252109303-2751441dd157?w=800&h=800&fit=crop'],
                'marque' => 'Calvin Klein',
                'couleur' => 'Blanc',
                'style' => 'Formel',
                'gender' => 'Homme',
                'sizes' => ['S', 'M', 'L', 'XL'],
                'material' => 'Coton',
            ],
            [
                'name' => 'Chemise Casual',
                'description' => 'Chemise décontractée à manches courtes, idéale pour l\'été.',
                'price' => 55.00,
                'stock' => 30,
                'category_id' => $hommeChemises?->id,
                'images' => ['https://images.unsplash.com/photo-1603252109303-2751441dd157?w=800&h=800&fit=crop'],
                'marque' => 'Tommy Hilfiger',
                'couleur' => 'Bleu',
                'style' => 'Casual',
                'gender' => 'Homme',
                'sizes' => ['S', 'M', 'L', 'XL'],
                'material' => 'Coton',
            ],

            // Produits Femme - Robes
            [
                'name' => 'Robe Midi Élégante',
                'description' => 'Robe midi élégante, parfaite pour les occasions spéciales. Coupe ajustée et confortable.',
                'price' => 150.00,
                'stock' => 20,
                'category_id' => $femmeRobes?->id,
                'images' => [
                    'https://images.unsplash.com/photo-1515372039744-b8f02a3ae446?w=800&h=800&fit=crop',
                    'https://images.unsplash.com/photo-1594633313593-bab3825d0caf?w=800&h=800&fit=crop'
                ],
                'marque' => 'Zara',
                'couleur' => 'Noir',
                'style' => 'Élégant',
                'gender' => 'Femme',
                'sizes' => ['XS', 'S', 'M', 'L'],
                'material' => 'Polyester',
            ],
            [
                'name' => 'Robe d\'Été Florale',
                'description' => 'Robe légère et aérée avec motif floral, idéale pour l\'été.',
                'price' => 75.00,
                'stock' => 35,
                'category_id' => $femmeRobes?->id,
                'images' => ['https://images.unsplash.com/photo-1515372039744-b8f02a3ae446?w=800&h=800&fit=crop'],
                'marque' => 'H&M',
                'couleur' => 'Multicolore',
                'style' => 'Casual',
                'gender' => 'Femme',
                'sizes' => ['S', 'M', 'L', 'XL'],
                'material' => 'Coton',
            ],
            [
                'name' => 'Robe Cocktail',
                'description' => 'Robe cocktail chic et moderne, parfaite pour les soirées.',
                'price' => 180.00,
                'stock' => 15,
                'category_id' => $femmeRobes?->id,
                'images' => ['https://images.unsplash.com/photo-1515372039744-b8f02a3ae446?w=800&q=80'],
                'marque' => 'Forever 21',
                'couleur' => 'Rouge',
                'style' => 'Chic',
                'gender' => 'Femme',
                'sizes' => ['XS', 'S', 'M'],
                'material' => 'Soie',
            ],

            // Produits Femme - Hauts
            [
                'name' => 'Blouse Blanche Classique',
                'description' => 'Blouse élégante en coton, intemporelle et polyvalente.',
                'price' => 65.00,
                'stock' => 40,
                'category_id' => $femmeHauts?->id,
                'images' => ['https://images.unsplash.com/photo-1594633313593-bab3825d0caf?w=800&h=800&fit=crop'],
                'marque' => 'Uniqlo',
                'couleur' => 'Blanc',
                'style' => 'Formel',
                'gender' => 'Femme',
                'sizes' => ['XS', 'S', 'M', 'L'],
                'material' => 'Coton',
            ],
            [
                'name' => 'Top Sport',
                'description' => 'Top de sport respirant, parfait pour le fitness et les activités sportives.',
                'price' => 45.00,
                'stock' => 50,
                'category_id' => $femmeHauts?->id,
                'images' => ['https://images.unsplash.com/photo-1551698618-1dfe5d97d256?w=800&h=800&fit=crop'],
                'marque' => 'Nike',
                'couleur' => 'Rose',
                'style' => 'Sport',
                'gender' => 'Femme',
                'sizes' => ['S', 'M', 'L', 'XL'],
                'material' => 'Polyester',
            ],

            // Produits Femme - Pantalons
            [
                'name' => 'Pantalon Taille Haute',
                'description' => 'Pantalon taille haute, confortable et stylé. Parfait pour un look moderne.',
                'price' => 90.00,
                'stock' => 30,
                'category_id' => $femmePantalons?->id,
                'images' => ['https://images.unsplash.com/photo-1506629905607-3e3a5a4c0c8e?w=800&h=800&fit=crop'],
                'marque' => 'Zara',
                'couleur' => 'Noir',
                'style' => 'Moderne',
                'gender' => 'Femme',
                'sizes' => ['36', '38', '40', '42'],
                'material' => 'Coton',
            ],
            [
                'name' => 'Legging Sport',
                'description' => 'Legging de sport haute performance, stretch et confortable.',
                'price' => 55.00,
                'stock' => 45,
                'category_id' => $femmePantalons?->id,
                'images' => ['https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=800&h=800&fit=crop'],
                'marque' => 'Adidas',
                'couleur' => 'Noir',
                'style' => 'Sport',
                'gender' => 'Femme',
                'sizes' => ['S', 'M', 'L', 'XL'],
                'material' => 'Polyester',
            ],

            // Produits Enfant - T-Shirts
            [
                'name' => 'T-Shirt Enfant Coloré',
                'description' => 'T-shirt amusant et coloré pour enfants, 100% coton bio.',
                'price' => 25.00,
                'stock' => 40,
                'category_id' => $enfantTshirts?->id,
                'images' => ['https://images.unsplash.com/photo-1503454537195-1dcabb73ffb9?w=800&h=800&fit=crop'],
                'marque' => 'H&M',
                'couleur' => 'Multicolore',
                'style' => 'Casual',
                'gender' => 'Enfant',
                'sizes' => ['4 ans', '6 ans', '8 ans', '10 ans', '12 ans'],
                'material' => 'Coton 100%',
            ],
            [
                'name' => 'T-Shirt Enfant Sport',
                'description' => 'T-shirt de sport pour enfants, confortable et résistant.',
                'price' => 30.00,
                'stock' => 35,
                'category_id' => $enfantTshirts?->id,
                'images' => ['https://images.unsplash.com/photo-1503454537195-1dcabb73ffb9?w=800&h=800&fit=crop'],
                'marque' => 'Nike',
                'couleur' => 'Bleu',
                'style' => 'Sport',
                'gender' => 'Enfant',
                'sizes' => ['6 ans', '8 ans', '10 ans', '12 ans'],
                'material' => 'Polyester',
            ],

            // Produits Enfant - Pantalons
            [
                'name' => 'Pantalon Enfant Jeans',
                'description' => 'Pantalon jean confortable pour enfants, résistant aux jeux.',
                'price' => 45.00,
                'stock' => 30,
                'category_id' => $enfantPantalons?->id,
                'images' => ['https://images.unsplash.com/photo-1503454537195-1dcabb73ffb9?w=800&h=800&fit=crop'],
                'marque' => 'Gap',
                'couleur' => 'Bleu',
                'style' => 'Casual',
                'gender' => 'Enfant',
                'sizes' => ['4 ans', '6 ans', '8 ans', '10 ans', '12 ans'],
                'material' => 'Denim',
            ],
            [
                'name' => 'Short Enfant Sport',
                'description' => 'Short de sport pour enfants, léger et confortable.',
                'price' => 20.00,
                'stock' => 50,
                'category_id' => $enfantPantalons?->id,
                'images' => ['https://images.unsplash.com/photo-1503454537195-1dcabb73ffb9?w=800&h=800&fit=crop'],
                'marque' => 'Adidas',
                'couleur' => 'Noir',
                'style' => 'Sport',
                'gender' => 'Enfant',
                'sizes' => ['4 ans', '6 ans', '8 ans', '10 ans'],
                'material' => 'Polyester',
            ],

            // Produits Enfant - Robes
            [
                'name' => 'Robe Enfant Princesse',
                'description' => 'Robe élégante pour petites filles, avec motifs colorés.',
                'price' => 55.00,
                'stock' => 25,
                'category_id' => $enfantRobes?->id,
                'images' => ['https://images.unsplash.com/photo-1503454537195-1dcabb73ffb9?w=800&h=800&fit=crop'],
                'marque' => 'Zara',
                'couleur' => 'Rose',
                'style' => 'Élégant',
                'gender' => 'Enfant',
                'sizes' => ['4 ans', '6 ans', '8 ans', '10 ans'],
                'material' => 'Coton',
            ],
            [
                'name' => 'Robe Enfant Été',
                'description' => 'Robe légère et colorée pour l\'été, parfaite pour jouer.',
                'price' => 35.00,
                'stock' => 40,
                'category_id' => $enfantRobes?->id,
                'images' => ['https://images.unsplash.com/photo-1503454537195-1dcabb73ffb9?w=800&h=800&fit=crop'],
                'marque' => 'H&M',
                'couleur' => 'Multicolore',
                'style' => 'Casual',
                'gender' => 'Enfant',
                'sizes' => ['4 ans', '6 ans', '8 ans', '10 ans', '12 ans'],
                'material' => 'Coton',
            ],

            // Produits Accessoires - Sacs
            [
                'name' => 'Sac à Main Cuir',
                'description' => 'Sac à main élégant en cuir véritable, design moderne et spacieux.',
                'price' => 120.00,
                'stock' => 20,
                'category_id' => $accessoiresSacs?->id,
                'images' => ['https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=800&h=800&fit=crop'],
                'marque' => 'Zara',
                'couleur' => 'Noir',
                'style' => 'Élégant',
                'gender' => 'Unisexe',
                'sizes' => ['Unique'],
                'material' => 'Cuir',
            ],
            [
                'name' => 'Sac à Dos Sport',
                'description' => 'Sac à dos sportif, résistant et confortable. Parfait pour le quotidien.',
                'price' => 65.00,
                'stock' => 30,
                'category_id' => $accessoiresSacs?->id,
                'images' => ['https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=800&h=800&fit=crop'],
                'marque' => 'Nike',
                'couleur' => 'Noir',
                'style' => 'Sport',
                'gender' => 'Unisexe',
                'sizes' => ['Unique'],
                'material' => 'Polyester',
            ],

            // Produits Accessoires - Chaussures
            [
                'name' => 'Baskets Sport',
                'description' => 'Baskets de sport confortables, idéales pour la course et le fitness.',
                'price' => 95.00,
                'stock' => 35,
                'category_id' => $accessoiresChaussures?->id,
                'images' => ['https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=800&h=800&fit=crop'],
                'marque' => 'Nike',
                'couleur' => 'Blanc',
                'style' => 'Sport',
                'gender' => 'Unisexe',
                'sizes' => ['38', '39', '40', '41', '42', '43', '44'],
                'material' => 'Cuir synthétique',
            ],
            [
                'name' => 'Chaussures de Ville',
                'description' => 'Chaussures élégantes pour la ville, confortables et stylées.',
                'price' => 110.00,
                'stock' => 25,
                'category_id' => $accessoiresChaussures?->id,
                'images' => ['https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=800&h=800&fit=crop'],
                'marque' => 'Zara',
                'couleur' => 'Noir',
                'style' => 'Formel',
                'gender' => 'Unisexe',
                'sizes' => ['38', '39', '40', '41', '42', '43'],
                'material' => 'Cuir',
            ],

            // Produits Accessoires - Bijoux
            [
                'name' => 'Collier Élégant',
                'description' => 'Collier en argent avec pendentif, design moderne et raffiné.',
                'price' => 75.00,
                'stock' => 40,
                'category_id' => $accessoiresBijoux?->id,
                'images' => ['https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?w=800&h=800&fit=crop'],
                'marque' => 'Zara',
                'couleur' => 'Argent',
                'style' => 'Élégant',
                'gender' => 'Unisexe',
                'sizes' => ['Unique'],
                'material' => 'Argent',
            ],
            [
                'name' => 'Montre Classique',
                'description' => 'Montre élégante avec bracelet en cuir, design intemporel.',
                'price' => 150.00,
                'stock' => 20,
                'category_id' => $accessoiresBijoux?->id,
                'images' => ['https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=800&h=800&fit=crop'],
                'marque' => 'Zara',
                'couleur' => 'Noir',
                'style' => 'Formel',
                'gender' => 'Unisexe',
                'sizes' => ['Unique'],
                'material' => 'Cuir',
            ],
        ];

        foreach ($products as $productData) {
            // Skip if category doesn't exist
            if (!$productData['category_id']) {
                continue;
            }
            
            // Calculer le prix promo si promotion
            $promotionPercentage = rand(0, 30); // 0-30% de réduction aléatoire
            if ($promotionPercentage > 0) {
                $productData['promotion_percentage'] = $promotionPercentage;
                $productData['promo_price'] = round($productData['price'] * (1 - $promotionPercentage / 100), 2);
            } else {
                $productData['promo_price'] = $productData['price'];
            }

            // Assigner un créateur de manière cyclique
            $productData['created_by'] = $creators[$creatorIndex % $creators->count()]->id;
            $creatorIndex++;

            // Générer size_stock si sizes existe
            if (isset($productData['sizes']) && is_array($productData['sizes'])) {
                $sizeStock = [];
                $totalStock = $productData['stock'];
                $numSizes = count($productData['sizes']);
                
                // Répartir le stock entre les tailles
                foreach ($productData['sizes'] as $index => $size) {
                    if ($index === $numSizes - 1) {
                        // Dernière taille : prend le reste
                        $sizeStock[$size] = $totalStock;
                    } else {
                        // Répartition aléatoire mais équilibrée
                        $stockForSize = rand(1, max(1, floor($totalStock / $numSizes * 2)));
                        $sizeStock[$size] = $stockForSize;
                        $totalStock -= $stockForSize;
                    }
                }
                $productData['size_stock'] = $sizeStock;
            }

            Product::firstOrCreate(
                ['name' => $productData['name'], 'category_id' => $productData['category_id']],
                $productData
            );
        }
    }
}
