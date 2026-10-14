<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DocumentCategoriesTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $colleague;

    private int $policies;

    private int $security;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['email_verified_at' => now()]);
        $this->colleague = User::factory()->create(['email_verified_at' => now()]);
        $this->policies = $this->category('IT Policies', 'it-policies', 1);
        $this->security = $this->category('Cybersecurity', 'cybersecurity', 2);
    }

    private function category(string $name, string $slug, int $order): int
    {
        return DB::table('document_categories')->insertGetId([
            'name' => $name,
            'slug' => $slug,
            'sort_order' => $order,
            'created_by' => $this->owner->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function document(int $category, User $owner, string $title, string $extension = 'docx'): int
    {
        return DB::table('documents')->insertGetId([
            'public_id' => (string) Str::uuid(),
            'owner_id' => $owner->id,
            'category_id' => $category,
            'title' => $title,
            'original_filename' => Str::slug($title).'.'.$extension,
            'extension' => $extension,
            'mime_type' => $extension === 'docx'
                ? 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'size_bytes' => 5120,
            'current_version' => 1,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function share(int $documentId, bool $expired = false): void
    {
        DB::table('document_shares')->insert([
            'document_id' => $documentId,
            'user_id' => $this->colleague->id,
            'shared_by' => $this->owner->id,
            'permission' => 'view',
            'expires_at' => $expired ? now()->subMinute() : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeAdministrator(): void
    {
        $roleId = DB::table('roles')->insertGetId([
            'name' => 'Administrator',
            'slug' => 'administrator',
            'is_system' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('role_user')->insert([
            'role_id' => $roleId,
            'user_id' => $this->owner->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/document-server/categories')->assertRedirect('/login');
        $this->post('/document-server/categories', ['name' => 'New Category'])->assertRedirect('/login');
    }

    public function test_categories_list_uses_accessible_document_counts(): void
    {
        $this->document($this->policies, $this->owner, 'Policy One');
        $this->document($this->policies, $this->owner, 'Policy Two', 'xlsx');
        $this->document($this->security, $this->owner, 'Security Plan');

        $this->actingAs($this->owner)->get('/document-server/categories')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('DocumentServerCategories')
                ->has('categories', 2)
                ->where('categories.0.document_count', 2)
                ->where('categories.0.word_count', 1)
                ->where('categories.0.excel_count', 1)
                ->where('categories.1.document_count', 1)
                ->where('documents', null)
            );

        $this->actingAs($this->colleague)->get('/document-server/categories')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('categories.0.document_count', 0)
                ->where('categories.1.document_count', 0)
                ->where('isAdmin', false)
            );
    }

    public function test_category_document_list_only_contains_accessible_files(): void
    {
        $shared = $this->document($this->policies, $this->owner, 'Shared Policy');
        $this->document($this->policies, $this->owner, 'Private Policy');
        $this->document($this->security, $this->owner, 'Security Memo');
        $this->share($shared);

        $this->actingAs($this->colleague)->get('/document-server/categories?category='.$this->policies)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('selectedCategory.id', $this->policies)
                ->where('selectedCategory.document_count', 1)
                ->has('documents.data', 1)
                ->where('documents.data.0.title', 'Shared Policy')
            );
    }

    public function test_expired_shares_and_recycled_files_do_not_count(): void
    {
        $expired = $this->document($this->policies, $this->owner, 'Expired Share');
        $this->share($expired, expired: true);
        $active = $this->document($this->policies, $this->owner, 'Deleted Shared File');
        $this->share($active);
        DB::table('documents')->where('id', $active)->update(['deleted_at' => now()]);

        $this->actingAs($this->colleague)->get('/document-server/categories?category='.$this->policies)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('selectedCategory.document_count', 0)
                ->has('documents.data', 0)
            );
    }

    public function test_category_filters_use_database_not_other_categories(): void
    {
        $this->document($this->policies, $this->owner, 'Password Policy');
        $this->document($this->policies, $this->owner, 'Hardware List', 'xlsx');
        $this->document($this->security, $this->owner, 'Password Policy - Security');

        $url = '/document-server/categories?category='.$this->policies.'&type=word&search=Password';
        $this->actingAs($this->owner)->get($url)->assertOk()->assertInertia(fn (Assert $page) => $page
            ->has('documents.data', 1)
            ->where('documents.data.0.title', 'Password Policy')
            ->where('filters.type', 'word')
        );
    }

    public function test_invalid_category_returns_not_found(): void
    {
        $this->actingAs($this->owner)->get('/document-server/categories?category=99999')->assertNotFound();
        $this->actingAs($this->owner)->get('/document-server/categories?category=unknown')->assertNotFound();
    }

    public function test_only_administrators_can_create_or_rename_categories(): void
    {
        $this->actingAs($this->owner)->post('/document-server/categories', ['name' => 'Cloud Support'])
            ->assertForbidden();
        $this->actingAs($this->owner)->patch('/document-server/categories/'.$this->policies, ['name' => 'IT Standards'])
            ->assertForbidden();

        $this->makeAdministrator();
        $this->actingAs($this->owner)->post('/document-server/categories', ['name' => 'Cloud Support'])
            ->assertRedirect();
        $this->assertDatabaseHas('document_categories', ['name' => 'Cloud Support', 'slug' => 'cloud-support']);

        $this->actingAs($this->owner)->patch('/document-server/categories/'.$this->policies, ['name' => 'IT Standards'])
            ->assertRedirect();
        $this->assertDatabaseHas('document_categories', ['id' => $this->policies, 'name' => 'IT Standards', 'slug' => 'it-standards']);
    }

    public function test_duplicate_categories_are_rejected(): void
    {
        $this->makeAdministrator();
        $this->actingAs($this->owner)->post('/document-server/categories', ['name' => 'IT Policies'])
            ->assertSessionHasErrors('name');
        $this->actingAs($this->owner)->patch('/document-server/categories/'.$this->security, ['name' => 'IT Policies'])
            ->assertSessionHasErrors('name');
        $this->assertDatabaseCount('document_categories', 2);
    }

    public function test_admin_can_delete_empty_category_but_not_one_with_documents(): void
    {
        $this->makeAdministrator();
        $this->actingAs($this->owner)->delete('/document-server/categories/'.$this->policies)
            ->assertRedirect();
        $this->assertDatabaseMissing('document_categories', ['id' => $this->policies]);

        $this->document($this->security, $this->owner, 'Security Policy');
        $this->actingAs($this->owner)->delete('/document-server/categories/'.$this->security)
            ->assertSessionHasErrors('category');
        $this->assertDatabaseHas('document_categories', ['id' => $this->security]);
    }

    public function test_non_admin_cannot_delete_even_empty_category(): void
    {
        $this->actingAs($this->colleague)->delete('/document-server/categories/'.$this->policies)
            ->assertForbidden();
        $this->assertDatabaseHas('document_categories', ['id' => $this->policies]);
    }

    public function test_results_are_paginated_twelve_per_page(): void
    {
        for ($i = 1; $i <= 14; $i++) {
            $this->document($this->policies, $this->owner, 'Policy '.$i);
        }
        $this->actingAs($this->owner)->get('/document-server/categories?category='.$this->policies)
            ->assertInertia(fn (Assert $page) => $page
                ->has('documents.data', 12)
                ->where('documents.total', 14)
                ->where('documents.last_page', 2)
            );
    }
}
