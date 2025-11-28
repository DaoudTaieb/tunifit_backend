<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('bottom_banner_title')->nullable()->after('image');
            $table->text('bottom_banner_description')->nullable()->after('bottom_banner_title');
            $table->string('bottom_banner_button_text')->nullable()->after('bottom_banner_description');
            $table->string('bottom_banner_button_link')->nullable()->after('bottom_banner_button_text');
            $table->string('bottom_banner_image')->nullable()->after('bottom_banner_button_link');
        });
    }

    public function down()
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn([
                'bottom_banner_title',
                'bottom_banner_description',
                'bottom_banner_button_text',
                'bottom_banner_button_link',
                'bottom_banner_image',
            ]);
        });
    }
};

