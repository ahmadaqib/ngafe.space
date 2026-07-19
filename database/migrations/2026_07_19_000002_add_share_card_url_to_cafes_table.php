<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cafes', function (Blueprint $table): void {
            $table->string('share_card_url')->nullable()->after('trending_score');
        });
    }

    public function down(): void
    {
        Schema::table('cafes', function (Blueprint $table): void {
            $table->dropColumn('share_card_url');
        });
    }
};
