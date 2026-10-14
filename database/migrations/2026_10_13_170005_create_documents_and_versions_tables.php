<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique(); // Safe public identifier (authorisation still required)
            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('folder_id')->nullable()->constrained('document_folders')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('document_categories')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('original_filename');
            $table->string('extension', 20);
            $table->string('mime_type', 150);
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->unsignedInteger('current_version')->default(0);
            $table->string('status', 20)->default('active'); // active, archived
            $table->boolean('is_template')->default(false);
            $table->foreignId('last_edited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_edited_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->softDeletes(); // Recycle bin
            $table->index(['owner_id', 'created_at']);
            $table->index(['folder_id', 'title']);
            $table->index(['category_id', 'status']);
        });

        // Every saved version points to a private server file, not a publicly served URL.
        Schema::create('document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('storage_disk', 50)->default('local');
            $table->string('storage_path', 1000);
            $table->string('original_filename');
            $table->string('mime_type', 150);
            $table->unsignedBigInteger('size_bytes');
            $table->char('checksum_sha256', 64)->nullable();
            $table->string('source', 30)->default('upload'); // upload, desktop, restore
            $table->text('change_summary')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['document_id', 'version_number']);
            $table->index(['document_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_versions');
        Schema::dropIfExists('documents');
    }
};
