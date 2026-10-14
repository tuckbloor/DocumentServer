<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DocumentDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $colleague;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->owner = User::factory()->create(['email_verified_at' => now()]);
        $this->colleague = User::factory()->create(['email_verified_at' => now()]);
    }

    private function createDocument(): int
    {
        $now = now();
        $path = 'document-server/tests/example.docx';
        Storage::disk('local')->put($path, file_get_contents(base_path('tests/Fixtures/sample.docx')));
        $id = DB::table('documents')->insertGetId([
            'public_id' => (string) Str::uuid(), 'owner_id' => $this->owner->id,
            'title' => 'Example Policy', 'original_filename' => 'example.docx', 'extension' => 'docx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'size_bytes' => Storage::disk('local')->size($path), 'current_version' => 1,
            'status' => 'active', 'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('document_versions')->insert([
            'document_id' => $id, 'version_number' => 1, 'storage_disk' => 'local',
            'storage_path' => $path, 'original_filename' => 'example.docx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'size_bytes' => Storage::disk('local')->size($path), 'source' => 'upload',
            'created_by' => $this->owner->id, 'created_at' => $now, 'updated_at' => $now,
        ]);
        return $id;
    }

    private function share(int $document, string $permission = 'view', bool $expired = false): void
    {
        DB::table('document_shares')->insert([
            'document_id' => $document, 'user_id' => $this->colleague->id,
            'shared_by' => $this->owner->id, 'permission' => $permission,
            'expires_at' => $expired ? now()->subHour() : null,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function wordUpload(string $name = 'demo.docx'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, file_get_contents(base_path('tests/Fixtures/sample.docx')));
    }

    public function test_guests_must_log_in_before_using_documents(): void
    {
        $id = $this->createDocument();
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get("/documents/{$id}/download")->assertRedirect('/login');
        $this->post('/documents')->assertRedirect('/login');
    }

    public function test_dashboard_uses_database_document_counts_and_owner_access(): void
    {
        $this->createDocument();
        $this->actingAs($this->owner)->get('/dashboard')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')->where('stats.total', 1)->where('stats.word', 1)
            ->where('stats.excel', 0)->has('documents.data', 1));
        $this->actingAs($this->colleague)->get('/dashboard')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('stats.total', 0)->has('documents.data', 0));
    }

    public function test_private_download_requires_owner_or_active_share(): void
    {
        $id = $this->createDocument();
        $this->actingAs($this->colleague)->get("/documents/{$id}/download")->assertForbidden();
        $this->share($id);
        $this->actingAs($this->colleague)->get("/documents/{$id}/download")->assertOk()
            ->assertHeader('content-disposition');
        $this->assertDatabaseHas('document_recent_views', ['document_id' => $id, 'user_id' => $this->colleague->id]);
        $this->assertDatabaseHas('document_activities', ['document_id' => $id, 'event' => 'downloaded']);
    }

    public function test_expired_shares_do_not_grant_access(): void
    {
        $id = $this->createDocument();
        $this->share($id, 'edit', expired: true);
        $this->actingAs($this->colleague)->get("/documents/{$id}/download")->assertForbidden();
        $this->actingAs($this->colleague)->post("/documents/{$id}/favourite")->assertForbidden();
    }

    public function test_owner_can_share_and_revoke_access(): void
    {
        $id = $this->createDocument();
        $this->actingAs($this->colleague)->post("/documents/{$id}/shares", [
            'user_id' => $this->owner->id, 'permission' => 'edit',
        ])->assertForbidden();
        $this->actingAs($this->owner)->post("/documents/{$id}/shares", [
            'user_id' => $this->colleague->id, 'permission' => 'edit',
        ])->assertRedirect();
        $this->assertDatabaseHas('document_shares', [
            'document_id' => $id, 'user_id' => $this->colleague->id, 'permission' => 'edit',
        ]);
        $this->actingAs($this->colleague)->get("/documents/{$id}/download")->assertOk();
        $this->actingAs($this->owner)->delete("/documents/{$id}/shares/{$this->colleague->id}")->assertRedirect();
        $this->actingAs($this->colleague)->get("/documents/{$id}/download")->assertForbidden();
    }

    public function test_upload_registers_file_in_private_storage_and_creates_version(): void
    {
        $this->actingAs($this->owner)->post('/documents', ['files' => [$this->wordUpload()]])->assertRedirect();
        $doc = DB::table('documents')->where('owner_id', $this->owner->id)->first();
        $this->assertNotNull($doc);
        $this->assertSame(1, (int) $doc->current_version);
        $rev = DB::table('document_versions')->where('document_id', $doc->id)->first();
        $this->assertSame('local', $rev->storage_disk);
        $this->assertStringStartsWith('document-server/', $rev->storage_path);
        Storage::disk('local')->assertExists($rev->storage_path);
        $this->assertSame(64, strlen($rev->checksum_sha256));
    }

    public function test_view_permission_does_not_allow_saving_revisions(): void
    {
        $id = $this->createDocument();
        $this->share($id, 'view');
        $this->actingAs($this->colleague)->post("/documents/{$id}/revisions", [
            'file' => $this->wordUpload(), 'expected_version' => 1,
        ])->assertForbidden();
        $this->assertDatabaseCount('document_versions', 1);
    }

    public function test_owner_can_save_new_version_but_not_overwrite_when_version_is_stale(): void
    {
        $id = $this->createDocument();
        $this->actingAs($this->owner)->post("/documents/{$id}/revisions", [
            'file' => $this->wordUpload('new.docx'), 'expected_version' => 1,
        ])->assertRedirect();
        $this->assertDatabaseHas('documents', ['id' => $id, 'current_version' => 2]);
        $this->assertDatabaseCount('document_versions', 2);
        $this->actingAs($this->owner)->post("/documents/{$id}/revisions", [
            'file' => $this->wordUpload('old.docx'), 'expected_version' => 1,
        ])->assertSessionHasErrors('expected_version');
        $this->assertDatabaseCount('document_versions', 2);
    }

    public function test_owner_can_delete_and_restore_but_colleague_cannot(): void
    {
        $id = $this->createDocument();
        $this->share($id, 'edit');
        $this->actingAs($this->colleague)->delete("/documents/{$id}")->assertForbidden();
        $this->actingAs($this->owner)->delete("/documents/{$id}")->assertRedirect();
        $this->assertNotNull(DB::table('documents')->where('id', $id)->value('deleted_at'));
        $this->actingAs($this->colleague)->get("/documents/{$id}/download")->assertNotFound();
        $this->actingAs($this->owner)->post("/documents/{$id}/restore")->assertRedirect();
        $this->assertNull(DB::table('documents')->where('id', $id)->value('deleted_at'));
    }

    public function test_favourites_are_user_specific(): void
    {
        $id = $this->createDocument();
        $this->actingAs($this->owner)->post("/documents/{$id}/favourite")->assertRedirect();
        $this->assertDatabaseHas('document_favourites', ['document_id' => $id, 'user_id' => $this->owner->id]);
        $this->actingAs($this->owner)->get('/dashboard?tab=favourites')->assertInertia(fn (Assert $page) => $page
            ->has('documents.data', 1));
        $this->actingAs($this->owner)->post("/documents/{$id}/favourite")->assertRedirect();
        $this->assertDatabaseCount('document_favourites', 0);
    }

    public function test_user_directory_and_audit_log_require_administrator_role(): void
    {
        $this->actingAs($this->owner)->get('/document-server/users')->assertForbidden();
        $role = DB::table('roles')->insertGetId([
            'name' => 'Administrator', 'slug' => 'administrator', 'is_system' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('role_user')->insert([
            'role_id' => $role, 'user_id' => $this->owner->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->actingAs($this->owner)->get('/document-server/users')->assertOk();
        $this->actingAs($this->owner)->get('/document-server/activity')->assertOk();
        $this->actingAs($this->colleague)->get('/document-server/activity')->assertForbidden();
    }
}
