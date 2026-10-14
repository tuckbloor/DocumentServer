<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class StandaloneDesktopTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $colleague;
    private int $docId;
    private const DOCX = "PK\x03\x04"."FAKE-OFFICE-DOCUMENT-V1";

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->owner = User::factory()->create(['email_verified_at' => now(), 'account_status' => 'active']);
        $this->colleague = User::factory()->create(['email_verified_at' => now(), 'account_status' => 'active']);
        // A different path for every test avoids collisions with an earlier streamed GET
        // on Windows, where an open file handle can prevent cleanup.
        $path = 'document-server/IT-Policies/'.Str::uuid().'/example.docx';
        $this->assertTrue(Storage::disk('local')->put($path, self::DOCX));
        Storage::disk('local')->assertExists($path);
        $now = now();
        $this->docId = DB::table('documents')->insertGetId([
            'public_id' => (string) Str::uuid(), 'owner_id' => $this->owner->id,
            'title' => 'Demo Policy', 'original_filename' => 'example.docx', 'extension' => 'docx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'size_bytes' => strlen(self::DOCX), 'current_version' => 1, 'status' => 'active',
            'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('document_versions')->insert([
            'document_id' => $this->docId, 'version_number' => 1, 'storage_disk' => 'local',
            'storage_path' => $path, 'original_filename' => 'example.docx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'size_bytes' => strlen(self::DOCX), 'checksum_sha256' => hash('sha256', self::DOCX),
            'source' => 'upload', 'created_by' => $this->owner->id,
            'created_at' => $now, 'updated_at' => $now,
        ]);
    }

    private function createSession(User $user): array
    {
        return $this->actingAs($user)->postJson("/documents/{$this->docId}/standalone/open")
            ->assertOk()->json();
    }

    private function davPath(string $uri): string
    {
        // The office URI contains the actual HTTPS resource URL after the |u| delimiter.
        $httpUrl = explode('|u|', $uri, 2)[1];
        return parse_url($httpUrl, PHP_URL_PATH);
    }

    /**
     * Consume the entire streamed WebDAV response.
     * Merely asserting HTTP 200 leaves the file stream open, potentially
     * interfering with Storage::fake() cleanup in later tests on Windows.
     */
    private function assertReadableDavDocument(string $officeUri): void
    {
        $response = $this->get($this->davPath($officeUri));
        $this->assertSame(200, $response->getStatusCode());

        ob_start();
        try {
            $response->sendContent();
            $contents = ob_get_contents();
        } finally {
            ob_end_clean();
        }

        $this->assertSame(self::DOCX, $contents);
    }

    private function share(string $permission = 'view'): void
    {
        DB::table('document_shares')->insert([
            'document_id' => $this->docId, 'user_id' => $this->colleague->id,
            'shared_by' => $this->owner->id, 'permission' => $permission,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_guest_cannot_start_desktop_session(): void
    {
        $this->postJson("/documents/{$this->docId}/standalone/open")->assertUnauthorized();
    }

    public function test_unshared_document_cannot_be_opened_by_colleague(): void
    {
        $this->actingAs($this->colleague)->postJson("/documents/{$this->docId}/standalone/open")
            ->assertForbidden();
    }

    public function test_owner_gets_an_edit_link_without_separate_password(): void
    {
        $link = $this->createSession($this->owner);
        $this->assertSame('edit', $link['mode']);
        $this->assertStringStartsWith('ms-word:ofe|u|http', $link['open_uri']);
        $this->assertStringContainsString('/standalone-dav/', $link['open_uri']);
        $session = DB::table('document_edit_sessions')->first();
        $this->assertSame('standalone-edit', $session->editor_name);
        $this->assertStringNotContainsString($session->session_token_hash, $link['open_uri']);
    }

    public function test_view_only_user_gets_a_read_only_link_and_no_write_permission(): void
    {
        $this->share();
        $link = $this->createSession($this->colleague);
        $this->assertSame('view', $link['mode']);
        $this->assertStringStartsWith('ms-word:ofv|u|', $link['open_uri']);
        $this->call('PUT', $this->davPath($link['open_uri']), [], [], [], [], self::DOCX.'-modified')
            ->assertStatus(403);
        $this->assertDatabaseCount('document_versions', 1);
    }

    public function test_only_one_editor_can_hold_an_active_session(): void
    {
        $this->share('edit');
        $this->createSession($this->owner);
        $this->actingAs($this->colleague)->postJson("/documents/{$this->docId}/standalone/open")
            ->assertStatus(423);
    }

    public function test_owner_can_reopen_without_invalidating_inflight_webdav_requests(): void
    {
        $first = $this->createSession($this->owner);
        $second = $this->createSession($this->owner);

        $this->assertNotSame($first['open_uri'], $second['open_uri']);
        $this->assertSame(2, DB::table('document_edit_sessions')
            ->where('document_id', $this->docId)->where('status', 'active')->count());
        // LibreOffice may still use the first link after a second Open.
        $this->assertReadableDavDocument($first['open_uri']);
        $this->assertReadableDavDocument($second['open_uri']);
        // End editing revokes every session owned by the same user.
        $this->actingAs($this->owner)->postJson("/documents/{$this->docId}/standalone/close")->assertOk();
        $this->get($this->davPath($first['open_uri']))->assertNotFound();
        $this->get($this->davPath($second['open_uri']))->assertNotFound();
    }

    public function test_dav_get_reads_private_file(): void
    {
        $link = $this->createSession($this->owner);
        $this->assertReadableDavDocument($link['open_uri']);
        $this->assertDatabaseHas('document_activities', [
            'document_id' => $this->docId,
            'event' => 'opened',
        ]);
    }

    public function test_changed_put_creates_a_new_immutable_version_and_preserves_original(): void
    {
        $link = $this->createSession($this->owner);
        $path = $this->davPath($link['open_uri']);
        $bytes = "PK\x03\x04"."FAKE-OFFICE-DOCUMENT-V2";
        $this->call('PUT', $path, [], [], [], [], $bytes)->assertSuccessful();
        $this->assertDatabaseHas('documents', ['id' => $this->docId, 'current_version' => 2]);
        $this->assertDatabaseCount('document_versions', 2);
        $first = DB::table('document_versions')->where('document_id', $this->docId)
            ->where('version_number', 1)->first();
        $second = DB::table('document_versions')->where('document_id', $this->docId)
            ->where('version_number', 2)->first();
        $this->assertSame(self::DOCX, Storage::disk('local')->get($first->storage_path));
        $this->assertSame($bytes, Storage::disk('local')->get($second->storage_path));
        $this->assertNotSame($first->storage_path, $second->storage_path);
    }

    public function test_unchanged_save_does_not_create_revision(): void
    {
        $link = $this->createSession($this->owner);
        $this->call('PUT', $this->davPath($link['open_uri']), [], [], [], [], self::DOCX)
            ->assertSuccessful();
        $this->assertDatabaseCount('document_versions', 1);
    }

    public function test_end_editing_revokes_the_url(): void
    {
        $link = $this->createSession($this->owner);
        $path = $this->davPath($link['open_uri']);
        $this->actingAs($this->owner)->postJson("/documents/{$this->docId}/standalone/close")
            ->assertOk();
        $this->get($path)->assertNotFound();
    }

    public function test_expired_session_cannot_read_or_write(): void
    {
        $link = $this->createSession($this->owner);
        DB::table('document_edit_sessions')->update(['expires_at' => now()->subMinute()]);
        $this->get($this->davPath($link['open_uri']))->assertNotFound();
        $this->call('PUT', $this->davPath($link['open_uri']), [], [], [], [], self::DOCX)
            ->assertNotFound();
    }

    public function test_access_revocation_is_checked_on_every_request(): void
    {
        $this->share('edit');
        $link = $this->createSession($this->colleague);
        DB::table('document_shares')->where('document_id', $this->docId)
            ->where('user_id', $this->colleague->id)->update(['revoked_at' => now()]);
        $this->get($this->davPath($link['open_uri']))->assertNotFound();
    }

    public function test_stale_base_version_blocks_overwriting_newer_revision(): void
    {
        $link = $this->createSession($this->owner);
        // Version 2 must exist on disk: the WebDAV file node validates storage
        // before delegating to the stale-session (HTTP 412) revision check.
        $newerBytes = "PK\x03\x04".'FAKE-OFFICE-DOCUMENT-V2';
        $newerPath = 'document-server/IT-Policies/'.Str::uuid().'/another.docx';
        $this->assertTrue(Storage::disk('local')->put($newerPath, $newerBytes));
        Storage::disk('local')->assertExists($newerPath);

        DB::table('documents')->where('id', $this->docId)->update(['current_version' => 2]);
        DB::table('document_versions')->insert([
            'document_id' => $this->docId, 'version_number' => 2, 'storage_disk' => 'local',
            'storage_path' => $newerPath, 'original_filename' => 'example.docx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'size_bytes' => strlen($newerBytes), 'checksum_sha256' => hash('sha256', $newerBytes),
            'source' => 'upload', 'created_by' => $this->owner->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->call('PUT', $this->davPath($link['open_uri']), [], [], [], [], "PK\x03\x04.".'new')->assertStatus(412);
        $this->assertDatabaseCount('document_versions', 2);
    }
}
