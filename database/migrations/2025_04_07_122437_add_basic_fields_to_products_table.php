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
            // Add basic fields for products (marque, couleur)
            // Using string instead of enum for flexibility
            if (!Schema::hasColumn('products', 'marque')) {
                $table->string('marque')->nullable()->after('images');
            }
            
            if (!Schema::hasColumn('products', 'couleur')) {
                $table->string('couleur')->nullable()->after('marque');
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
            if (Schema::hasColumn('products', 'marque')) {
                $table->dropColumn('marque');
            }
            
            if (Schema::hasColumn('products', 'couleur')) {
                $table->dropColumn('couleur');
            }
        });
    }
};

