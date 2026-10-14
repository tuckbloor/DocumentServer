<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DocumentFavouritesPageTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $colleague;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create(['email_verified_at' => now()]);
        $this->colleague = User::factory()->create(['email_verified_at' => now()]);
    }

    private function document(string $title = 'Example Policy', string $extension = 'docx', ?int $category = null): int
    {
        return DB::table('documents')->insertGetId([
            'public_id' => (string) Str::uuid(),
            'owner_id' => $this->owner->id,
            'category_id' => $category,
            'title' => $title,
            'original_filename' => Str::slug($title).'.'.$extension,
            'extension' => $extension,
            'mime_type' => $extension === 'docx'
                ? 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'size_bytes' => 4500,
            'current_version' => 1,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function favourite(User $user, int $documentId): void
    {
        DB::table('document_favourites')->insert([
            'user_id' => $user->id,
            'document_id' => $documentId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function share(int $id, bool $expired = false): void
    {
        DB::table('document_shares')->insert([
            'document_id' => $id,
            'user_id' => $this->colleague->id,
            'shared_by' => $this->owner->id,
            'permission' => 'view',
            'expires_at' => $expired ? now()->subHour() : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/document-server/favourites')->assertRedirect('/login');
    }

    public function test_owner_sees_only_own_favourites(): void
    {
        $saved = $this->document('Saved Policy');
        $this->document('Not Saved');
        $this->favourite($this->owner, $saved);

        $this->actingAs($this->owner)
            ->get('/document-server/favourites')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('DocumentServerFavourites')
                ->where('count', 1)
                ->has('documents.data', 1)
                ->where('documents.data.0.id', $saved)
            );

        $this->actingAs($this->colleague)
            ->get('/document-server/favourites')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('count', 0)
                ->has('documents.data', 0)
            );
    }

    public function test_shared_document_is_accessible_when_share_is_active(): void
    {
        $id = $this->document('Shared Policy');
        $this->share($id);
        $this->favourite($this->colleague, $id);

        $this->actingAs($this->colleague)
            ->get('/document-server/favourites')
            ->assertInertia(fn (Assert $page) => $page
                ->where('count', 1)
                ->where('documents.data.0.id', $id)
            );
    }

    public function test_expired_or_revoked_access_does_not_show_private_favourite(): void
    {
        $id = $this->document('Restricted Policy');
        $this->share($id, expired: true);
        $this->favourite($this->colleague, $id);

        $this->actingAs($this->colleague)
            ->get('/document-server/favourites')
            ->assertInertia(fn (Assert $page) => $page
                ->where('count', 0)
                ->has('documents.data', 0)
            );

        DB::table('document_shares')->where('document_id', $id)
            ->update(['expires_at' => null, 'revoked_at' => now()]);
        $this->actingAs($this->colleague)
            ->get('/document-server/favourites')
            ->assertInertia(fn (Assert $page) => $page->where('count', 0));
    }

    public function test_deleted_document_does_not_appear_in_favourites(): void
    {
        $id = $this->document('Deleted Policy');
        $this->favourite($this->owner, $id);
        DB::table('documents')->where('id', $id)->update(['deleted_at' => now()]);

        $this->actingAs($this->owner)
            ->get('/document-server/favourites')
            ->assertInertia(fn (Assert $page) => $page->where('count', 0));
    }

    public function test_favourite_button_adds_and_removes_without_duplicates(): void
    {
        $id = $this->document();
        $this->actingAs($this->owner)->post("/documents/{$id}/favourite")
            ->assertRedirect();
        $this->assertDatabaseHas('document_favourites', [
            'user_id' => $this->owner->id, 'document_id' => $id,
        ]);

        $this->actingAs($this->owner)->post("/documents/{$id}/favourite")
            ->assertRedirect();
        $this->assertDatabaseMissing('document_favourites', [
            'user_id' => $this->owner->id, 'document_id' => $id,
        ]);
    }

    public function test_cannot_favourite_other_users_private_document(): void
    {
        $id = $this->document();
        $this->actingAs($this->colleague)->post("/documents/{$id}/favourite")
            ->assertForbidden();
    }

    public function test_favourites_support_word_excel_and_text_search(): void
    {
        $word = $this->document('Backup Policy');
        $excel = $this->document('Asset Tracker', 'xlsx');
        $this->favourite($this->owner, $word);
        $this->favourite($this->owner, $excel);

        $this->actingAs($this->owner)
            ->get('/document-server/favourites?type=excel')
            ->assertInertia(fn (Assert $page) => $page
                ->where('count', 2)
                ->has('documents.data', 1)
                ->where('documents.data.0.id', $excel)
            );
        $this->actingAs($this->owner)
            ->get('/document-server/favourites?search=Backup')
            ->assertInertia(fn (Assert $page) => $page
                ->has('documents.data', 1)
                ->where('documents.data.0.id', $word)
            );
    }
}
