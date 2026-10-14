<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Ramsey\Uuid\Uuid;
use RuntimeException;

class DocumentServerDocumentsSeeder extends Seeder
{
    public const MANIFEST = __DIR__.'/data/it_demo_documents.json';
    public const PRIVATE_PREFIX = 'storage/app/private/';
    public const DOCUMENT_PREFIX = 'document-server/';

    /**
     * Preflight the complete pack BEFORE inserting a single document.
     * Database storage_path is relative to the LOCAL disk's root storage/app/private.
     *
     * @return array<int, array<string, mixed>>
     */
    public function validatedManifest(): array
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Demo documents may only be seeded in local or testing.');
        }
        if (! is_file(self::MANIFEST)) {
            throw new RuntimeException('Missing manifest: '.self::MANIFEST);
        }
        $entries = json_decode(file_get_contents(self::MANIFEST), true, flags: JSON_THROW_ON_ERROR);
        if (! is_array($entries) || count($entries) !== 100) {
            throw new RuntimeException('Demo manifest must contain exactly 100 documents.');
        }
        $counts = ['docx' => 0, 'xlsx' => 0];
        $seenPaths = [];
        foreach ($entries as $index => &$entry) {
            if (! is_array($entry) || ! isset($entry['storage_path'], $entry['file_name'], $entry['title'], $entry['category'], $entry['document_type'])) {
                throw new RuntimeException("Invalid manifest entry at index {$index}.");
            }
            $manifestPath = $entry['storage_path'];
            if (! is_string($manifestPath) || ! str_starts_with($manifestPath, self::PRIVATE_PREFIX.self::DOCUMENT_PREFIX)
                || str_contains($manifestPath, '..') || str_contains($manifestPath, '\\')) {
                throw new RuntimeException("Unsafe or non-private document path: {$manifestPath}");
            }
            $relativePath = substr($manifestPath, strlen(self::PRIVATE_PREFIX));
            $category = $entry['category'];
            $filename = $entry['file_name'];
            $extension = strtolower($entry['document_type']);
            if (! is_string($filename) || basename($relativePath) !== $filename
                || ! in_array($extension, ['docx', 'xlsx'], true)
                || pathinfo($filename, PATHINFO_EXTENSION) !== $extension
                || dirname($relativePath) !== self::DOCUMENT_PREFIX.$category
                || preg_match('/[^A-Za-z0-9_-]/', $category)) {
                throw new RuntimeException("Unexpected file metadata/path: {$relativePath}");
            }
            if (isset($seenPaths[$relativePath])) {
                throw new RuntimeException("Duplicate document path in manifest: {$relativePath}");
            }
            $seenPaths[$relativePath] = true;
            if (! Storage::disk('local')->exists($relativePath)) {
                throw new RuntimeException("Private document missing: storage/app/private/{$relativePath}. Copy the 100-file ZIP first.");
            }
            $actualPath = Storage::disk('local')->path($relativePath);
            if (! is_file($actualPath)) {
                throw new RuntimeException("Not a readable local file: {$relativePath}");
            }
            $entry['_relative_path'] = $relativePath;
            $entry['_size'] = filesize($actualPath);
            $entry['_sha256'] = hash_file('sha256', $actualPath);
            $counts[$extension]++;
        }
        unset($entry);
        if ($counts['docx'] !== 50 || $counts['xlsx'] !== 50) {
            throw new RuntimeException('Document pack must contain 50 Word and 50 Excel documents.');
        }
        return $entries;
    }

    public function run(): void
    {
        $manifest = $this->validatedManifest();
        if (! DB::table('users')->where('id', 1)->exists()) {
            throw new RuntimeException('User ID 1 must exist before seeding documents.');
        }
        $now = now();
        $categories = [];
        $folders = [];
        foreach (array_values(array_unique(array_column($manifest, 'category'))) as $position => $folderName) {
            $slug = strtolower($folderName);
            $prettyName = str_replace('-', ' ', $folderName);
            $categoryId = DB::table('document_categories')->where('slug', $slug)->value('id');
            if (! $categoryId) {
                $categoryId = DB::table('document_categories')->insertGetId([
                    'name' => $prettyName,
                    'slug' => $slug,
                    'icon' => 'folder',
                    'colour' => '#2458A6',
                    'sort_order' => $position + 1,
                    'created_by' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
            $categories[$folderName] = $categoryId;
            $folderUuid = Uuid::uuid5(Uuid::NAMESPACE_URL, 'document-server-demo-folder/'.$slug)->toString();
            $folderId = DB::table('document_folders')->where('public_id', $folderUuid)->value('id');
            if (! $folderId) {
                $folderId = DB::table('document_folders')->insertGetId([
                    'public_id' => $folderUuid,
                    'name' => $prettyName,
                    'owner_id' => 1,
                    'description' => "DEMO: {$prettyName} reference documents and registers.",
                    'sort_order' => $position + 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
            $folders[$folderName] = $folderId;
        }
        foreach ($manifest as $index => $row) {
            $relativePath = $row['_relative_path'];
            $extension = strtolower($row['document_type']);
            $mime = $extension === 'docx'
                ? 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
            $uuid = Uuid::uuid5(Uuid::NAMESPACE_URL, 'document-server-demo-document/'.$relativePath)->toString();
            $docId = DB::table('documents')->where('public_id', $uuid)->value('id');
            if (! $docId) {
                $docId = DB::table('documents')->insertGetId([
                    'public_id' => $uuid,
                    'owner_id' => 1, // Your account can view all 100 documents immediately.
                    'folder_id' => $folders[$row['category']],
                    'category_id' => $categories[$row['category']],
                    'title' => $row['title'],
                    'description' => 'DEMO IT department file for safe training, sharing and editing tests.',
                    'original_filename' => $row['file_name'],
                    'extension' => $extension,
                    'mime_type' => $mime,
                    'size_bytes' => $row['_size'],
                    'current_version' => 1,
                    'status' => 'active',
                    'is_template' => false,
                    'last_edited_by' => 1,
                    'last_edited_at' => $now->copy()->subDays($index % 15),
                    'created_at' => $now->copy()->subDays(45 + ($index % 20)),
                    'updated_at' => $now->copy()->subDays($index % 15),
                ]);
            }
            // Preserve existing revisions and the current version if this seeder is rerun.
            if (! DB::table('document_versions')->where('document_id', $docId)->where('version_number', 1)->exists()) {
                DB::table('document_versions')->insert([
                    'document_id' => $docId,
                    'version_number' => 1,
                    'storage_disk' => 'local',
                    'storage_path' => $relativePath,
                    'original_filename' => $row['file_name'],
                    'mime_type' => $mime,
                    'size_bytes' => $row['_size'],
                    'checksum_sha256' => $row['_sha256'],
                    'source' => 'upload',
                    'change_summary' => 'Initial DEMO document import',
                    'created_by' => 1,
                    'created_at' => $now->copy()->subDays(45 + ($index % 20)),
                    'updated_at' => $now->copy()->subDays(45 + ($index % 20)),
                ]);
            }
            // A stable tag lets us find demo files without adding a new migration.
            $demoTag = DB::table('document_tags')->where('slug', 'demo')->value('id');
            if (! $demoTag) {
                $demoTag = DB::table('document_tags')->insertGetId([
                    'name' => 'DEMO', 'slug' => 'demo', 'colour' => '#CC0000',
                    'created_by' => 1, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
            if (! DB::table('document_tag')->where('document_id', $docId)->where('document_tag_id', $demoTag)->exists()) {
                DB::table('document_tag')->insert(['document_id' => $docId, 'document_tag_id' => $demoTag]);
            }
            if (! DB::table('document_activities')->where('document_id', $docId)->where('event', 'created')->exists()) {
                DB::table('document_activities')->insert([
                    'document_id' => $docId, 'user_id' => 1,
                    'event' => 'created', 'document_title' => $row['title'],
                    'metadata' => json_encode(['filename' => $row['file_name'], 'demo' => true]),
                    'created_at' => $now->copy()->subDays(45 + ($index % 20)),
                ]);
            }
        }
    }
}
