<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Preserve Laravel's existing users, passwords, email verification and 2FA fields.
        Schema::table('users', function (Blueprint $table) {
            $table->string('account_status', 20)->default('active'); // active, suspended, disabled
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->index('account_status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['account_status']);
            $table->dropColumn(['account_status', 'last_login_at', 'last_seen_at', 'suspended_at']);
        });
    }
};
