<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cafe_category', function (Blueprint $table): void {
            $table->foreignUlid('cafe_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('source')->default('admin');
            $table->decimal('confidence', 4, 3)->nullable();
            $table->primary(['cafe_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cafe_category');
    }
};
