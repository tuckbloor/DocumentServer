<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('display_name')->nullable();
            $table->string('avatar_path')->nullable(); // Relative path on a configured storage disk
            $table->string('avatar_disk', 50)->default('public');
            $table->string('cover_image_path')->nullable();
            $table->string('job_title')->nullable();
            $table->string('department')->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('location')->nullable();
            $table->string('website')->nullable();
            $table->text('bio')->nullable();
            $table->string('timezone', 64)->default('Europe/London');
            $table->string('locale', 12)->default('en_GB');
            $table->boolean('show_email_on_profile')->default(false);
            $table->boolean('show_phone_on_profile')->default(false);
            $table->timestamps();
        });

        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('theme', 20)->default('light'); // light, dark, system
            $table->string('document_view', 20)->default('grid'); // grid, list
            $table->unsignedSmallInteger('documents_per_page')->default(15);
            $table->boolean('sidebar_collapsed')->default(false);
            $table->boolean('email_when_shared')->default(true);
            $table->boolean('email_when_updated')->default(false);
            $table->boolean('email_when_commented')->default(true);
            $table->boolean('in_app_notifications')->default(true);
            $table->json('dashboard_widgets')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
        Schema::dropIfExists('user_profiles');
    }
};
