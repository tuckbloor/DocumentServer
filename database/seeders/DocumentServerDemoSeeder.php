<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Repeatable, development-only entrypoint. Does not change the registered user's password.
 * Run: php artisan db:seed --class=DocumentServerDemoSeeder
 */
class DocumentServerDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Demo seed data is only permitted in local or testing environments.');
        }

        // Fail before writing any database records if the 100-file pack is incomplete.
        (new DocumentServerDocumentsSeeder)->validatedManifest();

        DB::transaction(function (): void {
            $this->call([
                DocumentServerUsersSeeder::class,
                DocumentServerDocumentsSeeder::class,
                DocumentServerEngagementSeeder::class,
            ]);
        });

        $this->command?->info('Document Server demo seeded: 5 new users, 100 private files, folders, categories, versions, shares and dashboard activity.');
    }
}
