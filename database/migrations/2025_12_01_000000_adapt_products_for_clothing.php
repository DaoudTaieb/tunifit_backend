<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            // Rename and adapt columns for clothing
            // Remove try_on_image (not needed for clothing)
            if (Schema::hasColumn('products', 'try_on_image')) {
                $table->dropColumn('try_on_image');
            }
            
            // Add 'style' field (clothing style) - after couleur if it exists, otherwise after images
            if (!Schema::hasColumn('products', 'style')) {
                if (Schema::hasColumn('products', 'couleur')) {
                    $table->enum('style', ['Casual', 'Formel', 'Sport', 'Élégant', 'Décontracté', 'Chic', 'Vintage', 'Moderne'])->nullable()->after('couleur');
                } else {
                    $table->enum('style', ['Casual', 'Formel', 'Sport', 'Élégant', 'Décontracté', 'Chic', 'Vintage', 'Moderne'])->nullable()->after('images');
                }
            } elseif (Schema::hasColumn('products', 'forme')) {
                // Rename 'forme' to 'style' if forme exists
                $table->renameColumn('forme', 'style');
            }
            
            // Keep 'marque' (brand) - still relevant for clothing
            // Keep 'couleur' (color) - still relevant for clothing
            
            // Add 'gender' field for clothing
            if (!Schema::hasColumn('products', 'gender')) {
                $table->enum('gender', ['Homme', 'Femme', 'Unisexe', 'Enfant'])->default('Unisexe')->after('style');
            }
            
            // Add 'sizes' field (JSON array of available sizes)
            if (!Schema::hasColumn('products', 'sizes')) {
                $table->json('sizes')->nullable()->after('gender'); // e.g., ["XS", "S", "M", "L", "XL"]
            }
            
            // Add 'material' field (fabric/material)
            if (!Schema::hasColumn('products', 'material')) {
                $table->string('material')->nullable()->after('sizes'); // e.g., "Coton", "Polyester", "Laine"
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            // Revert changes
            if (Schema::hasColumn('products', 'style')) {
                $table->renameColumn('style', 'forme');
            }
            
            if (Schema::hasColumn('products', 'gender')) {
                $table->dropColumn('gender');
            }
            
            if (Schema::hasColumn('products', 'sizes')) {
                $table->dropColumn('sizes');
            }
            
            if (Schema::hasColumn('products', 'material')) {
                $table->dropColumn('material');
            }
            
            // Re-add try_on_image if needed
            if (!Schema::hasColumn('products', 'try_on_image')) {
                $table->string('try_on_image')->nullable()->after('images');
            }
        });
    }
};

