<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();

            // Just keep the user_id reference, no FK
            $table->unsignedBigInteger('created_by')->nullable();

            $table->string('type');           // e.g. order.created
            $table->string('title');
            $table->text('message')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();

            $table->timestamps();

            // Index for faster queries (optional)
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};