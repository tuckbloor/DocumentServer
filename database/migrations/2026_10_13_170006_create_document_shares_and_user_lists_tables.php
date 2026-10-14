<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // Recipient
            $table->foreignId('shared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('permission', 15)->default('view'); // view, edit
            $table->boolean('can_reshare')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->unique(['document_id', 'user_id']);
            $table->index(['user_id', 'revoked_at', 'expires_at']);
        });

        Schema::create('document_favourites', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['user_id', 'document_id']);
        });

        // Personal 'Recent' list, not just a global last-opened timestamp.
        Schema::create('document_recent_views', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->timestamp('last_opened_at');
            $table->unsignedInteger('open_count')->default(1);
            $table->primary(['user_id', 'document_id']);
            $table->index(['user_id', 'last_opened_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_recent_views');
        Schema::dropIfExists('document_favourites');
        Schema::dropIfExists('document_shares');
    }
};
