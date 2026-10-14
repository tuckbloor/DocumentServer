<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Short-lived one-time browser -> C# app handoff, with a hash of the secret ticket.
        Schema::create('document_launch_tickets', function (Blueprint $table) {
            $table->id();
            $table->char('ticket_hash', 64)->unique();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('purpose', 12)->default('edit'); // view, edit
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['user_id', 'expires_at']);
        });

        Schema::create('document_edit_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->char('session_token_hash', 64)->unique();
            $table->unsignedInteger('base_version');
            $table->string('editor_name', 100)->nullable(); // Word, Excel, in-app editor
            $table->string('status', 20)->default('active'); // active, closed, expired
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('last_heartbeat_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('ended_at')->nullable();
            $table->index(['document_id', 'status', 'expires_at']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_edit_sessions');
        Schema::dropIfExists('document_launch_tickets');
    }
};
