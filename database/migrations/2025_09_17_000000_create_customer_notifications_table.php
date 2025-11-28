<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('customer_notifications', function (Blueprint $table) {
            $table->id();

            // NULL => broadcast to all customers, otherwise send to a single user
            $table->foreignId('recipient_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->string('title');
            $table->text('message');

            // Optional: category/type of notif (promo, order, system, etc.)
            $table->string('type')->nullable();

            // Optional metadata payload (e.g., deep-links, CTA, image, etc.)
            $table->json('meta')->nullable();

            // Per-user read timestamp (for targeted ones). For broadcast,
            // you'll likely track reads in a pivot later; keeping a simple field now.
            $table->timestamp('read_at')->nullable();

            // Whether the notification is active/visible
            $table->boolean('is_active')->default(true);

            $table->timestamp('scheduled_at')->nullable(); // if you ever schedule sends
            $table->timestamps();

            $table->index(['recipient_user_id', 'is_active']);
            $table->index('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_notifications');
    }
};
