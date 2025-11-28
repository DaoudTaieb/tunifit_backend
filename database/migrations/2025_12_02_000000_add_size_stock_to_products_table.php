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
            // Add size_stock field to store stock per size (JSON: {"S": 10, "M": 5, "L": 0, "XL": 3})
            if (!Schema::hasColumn('products', 'size_stock')) {
                $table->json('size_stock')->nullable()->after('stock');
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
            if (Schema::hasColumn('products', 'size_stock')) {
                $table->dropColumn('size_stock');
            }
        });
    }
};

