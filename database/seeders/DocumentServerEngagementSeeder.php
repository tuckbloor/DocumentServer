<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use RuntimeException;

class DocumentServerEngagementSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Demo activity may only be seeded in local or testing.');
        }
        $manifest = (new DocumentServerDocumentsSeeder())->validatedManifest();
        $now = now();
        foreach ($manifest as $index => $row) {
            $uuid = Uuid::uuid5(Uuid::NAMESPACE_URL, 'document-server-demo-document/'.$row['_relative_path'])->toString();
            $doc = DB::table('documents')->where('public_id', $uuid)->first();
            if (! $doc) {
                throw new RuntimeException('Documents must be seeded before shares and activity.');
            }
            // First colleague always gets access; a second colleague gets view access on alternate documents.
            $primary = 2 + ($index % 5);
            $secondary = 2 + (($index + 2) % 5);
            $recipients = [
                ['id' => $primary, 'permission' => ($index % 3 === 0) ? 'edit' : 'view'],
            ];
            if ($index % 2 === 0) {
                $recipients[] = ['id' => $secondary, 'permission' => 'view'];
            }
            foreach ($recipients as $recipient) {
                $exists = DB::table('document_shares')
                    ->where('document_id', $doc->id)->where('user_id', $recipient['id'])->exists();
                if (! $exists) {
                    DB::table('document_shares')->insert([
                        'document_id' => $doc->id,
                        'user_id' => $recipient['id'],
                        'shared_by' => 1,
                        'permission' => $recipient['permission'],
                        'can_reshare' => false,
                        'created_at' => $now->copy()->subDays($index % 12),
                        'updated_at' => $now->copy()->subDays($index % 12),
                    ]);
                }
            }
            if ($index < 15 && ! DB::table('document_notifications')
                ->where('user_id', $primary)->where('document_id', $doc->id)->where('type', 'document_shared')->exists()) {
                DB::table('document_notifications')->insert([
                    'user_id' => $primary,
                    'actor_id' => 1,
                    'document_id' => $doc->id,
                    'type' => 'document_shared',
                    'title' => 'Document shared with you',
                    'message' => "{$row['title']} is ready to view in Document Server.",
                    'data' => json_encode(['demo' => true, 'permission' => $recipients[0]['permission']]),
                    'created_at' => $now->copy()->subDays($index % 7),
                ]);
            }
            if ($index % 9 === 0 && ! DB::table('document_comments')->where('document_id', $doc->id)->exists()) {
                DB::table('document_comments')->insert([
                    'document_id' => $doc->id,
                    'user_id' => $primary,
                    'version_number' => 1,
                    'body' => 'DEMO comment: please review this file during the next team meeting.',
                    'created_at' => $now->copy()->subDays($index % 9),
                    'updated_at' => $now->copy()->subDays($index % 9),
                ]);
            }
            if ($index % 9 === 0 && ! DB::table('document_favourites')->where('user_id', 1)->where('document_id', $doc->id)->exists()) {
                DB::table('document_favourites')->insert([
                    'user_id' => 1, 'document_id' => $doc->id,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
            if ($index % 6 === 0 && ! DB::table('document_favourites')->where('user_id', $primary)->where('document_id', $doc->id)->exists()) {
                DB::table('document_favourites')->insert([
                    'user_id' => $primary, 'document_id' => $doc->id,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
            if ($index >= 85 && ! DB::table('document_recent_views')->where('user_id', 1)->where('document_id', $doc->id)->exists()) {
                DB::table('document_recent_views')->insert([
                    'user_id' => 1, 'document_id' => $doc->id,
                    'last_opened_at' => $now->copy()->subHours(100 - $index),
                    'open_count' => 1,
                ]);
            }
            if ($index % 7 === 0 && ! DB::table('document_recent_views')->where('user_id', $primary)->where('document_id', $doc->id)->exists()) {
                DB::table('document_recent_views')->insert([
                    'user_id' => $primary, 'document_id' => $doc->id,
                    'last_opened_at' => $now->copy()->subHours(100 - $index),
                    'open_count' => 1,
                ]);
            }
        }
    }
}
