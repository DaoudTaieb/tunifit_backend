<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('product_views', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable(); // allow null for visitors
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->integer('views')->default(0);
            $table->timestamp('last_visited_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'product_id']); // prevent duplicates
           
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_views');
    }
};
