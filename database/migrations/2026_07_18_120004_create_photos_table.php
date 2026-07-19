<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('photos', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('review_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('cafe_id')->constrained()->cascadeOnDelete();
            $table->string('url_card');
            $table->string('url_full');
            $table->unsignedInteger('width');
            $table->unsignedInteger('height');
            $table->string('status')->default('pending');
            $table->string('content_hash', 64)->unique();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photos');
    }
};
