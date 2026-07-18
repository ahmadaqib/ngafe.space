<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cafes', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('slug');
            $table->string('city')->default('makassar');
            $table->string('area');
            $table->text('address')->nullable();
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->jsonb('opening_hours')->nullable();
            $table->jsonb('opening_hours_override')->nullable();
            $table->string('price_range')->nullable();
            $table->decimal('rating_avg', 3, 2)->nullable();
            $table->unsignedInteger('rating_count')->default(0);
            $table->decimal('quality_score', 6, 4)->nullable();
            $table->decimal('trending_score', 8, 2)->default(0);
            $table->string('status')->default('pending');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_verified_at')->nullable();
            $table->timestamps();
            $table->unique(['city', 'slug']);
            $table->index(['city', 'status']);
        });
    }
    public function down(): void { Schema::dropIfExists('cafes'); }
};
