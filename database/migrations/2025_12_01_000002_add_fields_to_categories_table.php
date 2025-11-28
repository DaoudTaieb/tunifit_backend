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
        Schema::table('categories', function (Blueprint $table) {
            if (!Schema::hasColumn('categories', 'parent_id')) {
                $table->foreignId('parent_id')->nullable()->after('image')->constrained('categories')->onDelete('cascade');
            }
            
            if (!Schema::hasColumn('categories', 'status')) {
                $table->enum('status', ['active', 'inactive'])->default('active')->after('parent_id');
            }
            
            if (!Schema::hasColumn('categories', 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('status');
            }
            
            if (!Schema::hasColumn('categories', 'meta_title')) {
                $table->string('meta_title')->nullable()->after('sort_order');
            }
            
            if (!Schema::hasColumn('categories', 'meta_description')) {
                $table->text('meta_description')->nullable()->after('meta_title');
            }
            
            if (!Schema::hasColumn('categories', 'featured')) {
                $table->boolean('featured')->default(false)->after('meta_description');
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
        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasColumn('categories', 'featured')) {
                $table->dropColumn('featured');
            }
            
            if (Schema::hasColumn('categories', 'meta_description')) {
                $table->dropColumn('meta_description');
            }
            
            if (Schema::hasColumn('categories', 'meta_title')) {
                $table->dropColumn('meta_title');
            }
            
            if (Schema::hasColumn('categories', 'sort_order')) {
                $table->dropColumn('sort_order');
            }
            
            if (Schema::hasColumn('categories', 'status')) {
                $table->dropColumn('status');
            }
            
            if (Schema::hasColumn('categories', 'parent_id')) {
                $table->dropForeign(['parent_id']);
                $table->dropColumn('parent_id');
            }
        });
    }
};

