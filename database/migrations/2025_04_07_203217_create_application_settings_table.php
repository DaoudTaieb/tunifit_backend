<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('application_settings', function (Blueprint $table) {
            $table->id();
            $table->string('application_name')->nullable();
            $table->string('matricule_fiscal')->nullable();
            $table->string('numero_tel')->nullable();
            $table->string('fax')->nullable();
            $table->string('adresse')->nullable();
            $table->boolean('fodec')->nullable();
            $table->string('responsable')->nullable();
            $table->binary('logo')->nullable();
            $table->string('route_reporting')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_settings');
    }
};