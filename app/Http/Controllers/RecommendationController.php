<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RecommendationController extends Controller
{
    /**
     * Find products by image filenames
     */
    public function findProductsByImages(Request $request)
    {
        $request->validate([
            'filenames' => 'required|array',
            'filenames.*' => 'string',
            'type' => 'required|in:sunglasses,eyeglasses',
        ]);

        $filenames = $request->input('filenames');
        $type = $request->input('type');

        Log::info("🟡 Received filenames: ", $filenames);
        Log::info("🟡 Glass type: $type");

        $baseFilenames = array_map('basename', $filenames);
        Log::info("🟡 Base filenames extracted: ", $baseFilenames);

        $allProducts = Product::all();
        Log::info("🟡 Total products fetched: " . $allProducts->count());

        $matchedProducts = [];

        foreach ($allProducts as $product) {
            $images = $this->getImagesArray($product->images);
            Log::info("🔍 Checking product #{$product->id} - '{$product->name}'");

            foreach ($images as $img) {
                foreach ($baseFilenames as $filename) {
                    Log::info("🔹 Comparing image '$img' with filename '$filename'");

                    if (basename($img) === $filename) {
                        Log::info("✅ EXACT MATCH FOUND: Product ID {$product->id}, image: $img");

                        $isSunglass = collect($images)->contains(fn($imgPath) => Str::contains($imgPath, '/images/sunglasses/'));

                        $typeMatch = ($type === 'sunglasses' && $isSunglass) ||
                                     ($type === 'eyeglasses' && !$isSunglass);

                        Log::info("🔍 Type match for '{$product->name}' → " . ($typeMatch ? '✅ yes' : '❌ no'));

                        if ($typeMatch) {
                            $matchedProducts[] = [
                                'id' => $product->id,
                                'name' => $product->name,
                                'price' => $product->price,
                                'description' => $product->description,
                                'marque' => $product->marque,
                                'couleur' => $product->couleur,
                                'forme' => $product->forme,
                                'images' => $images,
                                'matched_image' => $filename,
                            ];
                        }

                        break 2;
                    }
                }
            }
        }

        Log::info("✅ Total matched products: " . count($matchedProducts));

        return response()->json([
            'success' => true,
            'count' => count($matchedProducts),
            'products' => $matchedProducts,
            'type' => $type,
        ]);
    }

    /**
     * Process a list of AI recommendations to find product matches
     */
    public function processRecommendations(Request $request)
    {
        $request->validate([
            'recommendations' => 'required|array',
        ]);

        $recommendations = $request->input('recommendations');
        $result = [];

        foreach ($recommendations as $recommendation) {
            $filenames = [];

            if (isset($recommendation['stock_matches']) && is_array($recommendation['stock_matches'])) {
                foreach ($recommendation['stock_matches'] as $match) {
                    if (isset($match['filename'])) {
                        $filenames[] = basename($match['filename']);
                    }
                }
            }

            $matchedProducts = [];
            $allProducts = Product::all();

            foreach ($allProducts as $product) {
                $images = $this->getImagesArray($product->images);

                foreach ($images as $img) {
                    foreach ($filenames as $filename) {
                        if (basename($img) === $filename) {
                            $matchedProducts[] = [
                                'id' => $product->id,
                                'name' => $product->name,
                                'price' => $product->price,
                                'description' => $product->description,
                                'marque' => $product->marque,
                                'couleur' => $product->couleur,
                                'forme' => $product->forme,
                                'images' => $images,
                                'matched_image' => $filename,
                            ];
                            break 2;
                        }
                    }
                }
            }

            $result[] = [
                'model' => $recommendation['model'] ?? null,
                'matching_products' => $matchedProducts,
            ];
        }

        return response()->json([
            'success' => true,
            'processed_recommendations' => $result,
        ]);
    }

    /**
     * Safely decode product images JSON field
     */
    private function getImagesArray($images)
    {
        if (is_array($images)) return $images;
        if (is_string($images)) return json_decode($images, true) ?? [];
        return [];
    }
}
