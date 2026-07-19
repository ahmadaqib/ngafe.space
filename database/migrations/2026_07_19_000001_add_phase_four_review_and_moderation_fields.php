<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table): void {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->string('content_hash', 64)->nullable()->after('body')->index();
            $table->text('moderation_reason')->nullable()->after('is_edited');
            $table->foreignId('moderated_by')->nullable()->after('moderation_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable()->after('moderated_by');
        });

        Schema::table('photos', function (Blueprint $table): void {
            $table->text('processing_error')->nullable()->after('status');
        });

        Schema::table('reports', function (Blueprint $table): void {
            $table->boolean('priority')->default(false)->after('status')->index();
        });

        Schema::create('moderation_audit_logs', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('subject_type');
            $table->string('subject_id');
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['subject_type', 'subject_id']);
        });

        Schema::create('content_appeals', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('review_id')->constrained()->cascadeOnDelete();
            $table->string('reporter_name');
            $table->string('reporter_email');
            $table->text('reason');
            $table->string('status')->default('submitted');
            $table->unsignedTinyInteger('appeal_count')->default(0);
            $table->text('decision')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_appeals');
        Schema::dropIfExists('moderation_audit_logs');

        Schema::table('reports', fn (Blueprint $table) => $table->dropColumn('priority'));
        Schema::table('photos', fn (Blueprint $table) => $table->dropColumn('processing_error'));
        Schema::table('reviews', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('moderated_by');
            $table->dropColumn(['content_hash', 'moderation_reason', 'moderated_at']);
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });
    }
};
