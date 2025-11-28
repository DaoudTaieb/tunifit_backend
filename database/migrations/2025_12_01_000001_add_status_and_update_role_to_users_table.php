<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Update role enum to include super_admin
            DB::statement("ALTER TABLE `users` MODIFY COLUMN `role` ENUM('user', 'admin', 'super_admin') DEFAULT 'user'");
            
            // Add status column
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active')->after('role');
            
            // Add other missing fields if they don't exist
            if (!Schema::hasColumn('users', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable()->after('phone');
            }
            
            if (!Schema::hasColumn('users', 'gender')) {
                $table->enum('gender', ['male', 'female', 'other'])->nullable()->after('date_of_birth');
            }
            
            if (!Schema::hasColumn('users', 'avatar')) {
                $table->string('avatar')->nullable()->after('gender');
            }
            
            if (!Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('avatar');
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
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'status')) {
                $table->dropColumn('status');
            }
            
            if (Schema::hasColumn('users', 'date_of_birth')) {
                $table->dropColumn('date_of_birth');
            }
            
            if (Schema::hasColumn('users', 'gender')) {
                $table->dropColumn('gender');
            }
            
            if (Schema::hasColumn('users', 'avatar')) {
                $table->dropColumn('avatar');
            }
            
            if (Schema::hasColumn('users', 'last_login_at')) {
                $table->dropColumn('last_login_at');
            }
            
            // Revert role enum
            DB::statement("ALTER TABLE `users` MODIFY COLUMN `role` ENUM('user', 'admin') DEFAULT 'user'");
        });
    }
};

