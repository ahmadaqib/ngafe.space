<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('cafe_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('body');
            $table->string('display_alias');
            $table->string('status')->default('published');
            $table->boolean('is_edited')->default(false);
            $table->timestamps();
            $table->unique(['user_id', 'cafe_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
