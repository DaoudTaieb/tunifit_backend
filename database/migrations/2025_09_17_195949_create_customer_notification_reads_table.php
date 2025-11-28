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
        Schema::create('customer_notification_reads', function (Blueprint $table) {
    $table->id();
    $table->foreignId('notification_id')
        ->constrained('customer_notifications')
        ->onDelete('cascade');
    $table->foreignId('user_id')
        ->constrained('users')
        ->onDelete('cascade');
    $table->timestamp('read_at')->nullable();
    $table->unique(['notification_id', 'user_id']); // prevent duplicates
});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('customer_notification_reads');
    }
};
