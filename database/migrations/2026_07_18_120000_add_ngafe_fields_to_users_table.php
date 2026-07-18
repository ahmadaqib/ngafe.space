<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('google_sub')->nullable()->unique()->after('id');
            $table->string('display_alias_seed', 64)->nullable()->after('email');
            $table->string('role')->default('user')->after('remember_token');
            $table->string('status')->default('active')->after('role');
            $table->text('app_authentication_secret')->nullable()->after('status');
            $table->text('app_authentication_recovery_codes')->nullable()->after('app_authentication_secret');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['google_sub']);
            $table->dropColumn(['google_sub', 'display_alias_seed', 'role', 'status', 'app_authentication_secret', 'app_authentication_recovery_codes']);
        });
    }
};
