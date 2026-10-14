<?php

namespace App\Http\Controllers\DocumentServer;

use App\Http\Controllers\Controller;
use App\Support\DocumentServer\DocumentAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class DocumentCategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $userId = (int) $request->user()->id;
        $categories = DB::table('document_categories')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        // These counts are based on the documents THIS user can see, not company-wide
        // totals. An unshared private document must never leak into another user's count.
        $counts = DocumentAccess::visibleTo($userId)
            ->select('d.category_id')
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw('SUM(CASE WHEN d.extension = ? THEN 1 ELSE 0 END) AS word_count', ['docx'])
            ->selectRaw('SUM(CASE WHEN d.extension = ? THEN 1 ELSE 0 END) AS excel_count', ['xlsx'])
            ->groupBy('d.category_id')
            ->get()
            ->keyBy(fn ($item) => $item->category_id === null ? 'uncategorised' : (string) $item->category_id);

        $categoryCards = $categories->map(function ($category) use ($counts) {
            $count = $counts->get((string) $category->id);

            return [
                'id' => (int) $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'icon' => $category->icon,
                'colour' => $category->colour,
                'sort_order' => (int) $category->sort_order,
                'document_count' => (int) ($count->total ?? 0),
                'word_count' => (int) ($count->word_count ?? 0),
                'excel_count' => (int) ($count->excel_count ?? 0),
            ];
        })->values();

        $selectedId = $request->query('category');
        $selected = null;
        $documents = null;

        if ($selectedId !== null && $selectedId !== '') {
            abort_unless(is_scalar($selectedId) && ctype_digit((string) $selectedId) && (int) $selectedId > 0, 404);
            $selected = $categoryCards->firstWhere('id', (int) $selectedId);
            abort_unless($selected !== null, 404);

            $documentsQuery = DocumentAccess::visibleTo($userId)
                ->where('d.category_id', (int) $selectedId);

            $search = trim(mb_substr((string) $request->query('search', ''), 0, 120));
            if ($search !== '') {
                $documentsQuery->where(function ($query) use ($search) {
                    $query->where('d.title', 'like', '%'.$search.'%')
                        ->orWhere('d.original_filename', 'like', '%'.$search.'%');
                });
            }

            $type = $request->query('type');
            if ($type === 'word') {
                $documentsQuery->where('d.extension', 'docx');
            } elseif ($type === 'excel') {
                $documentsQuery->where('d.extension', 'xlsx');
            }

            $sort = $request->query('sort');
            if ($sort === 'name') {
                $documentsQuery->orderBy('d.title')->orderBy('d.id');
            } elseif ($sort === 'oldest') {
                $documentsQuery->orderBy('d.updated_at')->orderBy('d.id');
            } else {
                $documentsQuery->orderByDesc('d.updated_at')->orderByDesc('d.id');
            }

            $documents = $documentsQuery
                ->join('users as owner', 'owner.id', '=', 'd.owner_id')
                ->select(
                    'd.id', 'd.title', 'd.original_filename as filename',
                    'd.extension', 'd.size_bytes', 'd.current_version as version',
                    'd.updated_at', 'd.owner_id', 'owner.name as owner'
                )
                ->paginate(12)
                ->withQueryString();

            $documents->through(fn ($doc) => [
                'id' => (int) $doc->id,
                'title' => $doc->title,
                'filename' => $doc->filename,
                'extension' => $doc->extension,
                'size_bytes' => (int) $doc->size_bytes,
                'version' => (int) $doc->version,
                'updated_at' => $doc->updated_at,
                'owner' => $doc->owner,
                'mine' => (int) $doc->owner_id === $userId,
            ]);
        }

        $profile = DB::table('user_profiles')->where('user_id', $userId)->first();
        $role = DB::table('role_user as ru')
            ->join('roles as r', 'r.id', '=', 'ru.role_id')
            ->where('ru.user_id', $userId)
            ->orderByRaw("CASE r.slug WHEN 'administrator' THEN 1 WHEN 'manager' THEN 2 ELSE 3 END")
            ->value('r.name') ?: 'Member';

        return Inertia::render('DocumentServerCategories', [
            'viewer' => [
                'name' => $request->user()->name,
                'role' => $role,
                'avatar' => $profile?->avatar_path && $profile->avatar_disk === 'public'
                    ? Storage::disk('public')->url($profile->avatar_path)
                    : '/images/users/avatar-default.png',
            ],
            'isAdmin' => $this->isAdministrator($userId),
            'categories' => $categoryCards,
            'selectedCategory' => $selected,
            'documents' => $documents,
            'uncategorisedCount' => (int) ($counts->get('uncategorised')->total ?? 0),
            'filters' => [
                'search' => $request->query('search', ''),
                'type' => in_array($request->query('type'), ['word', 'excel'], true) ? $request->query('type') : '',
                'sort' => in_array($request->query('sort'), ['name', 'oldest'], true) ? $request->query('sort') : 'latest',
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->requireAdministrator($request);
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100', Rule::unique('document_categories', 'name')],
        ]);
        $name = trim($validated['name']);
        $slug = $this->availableSlug($name);

        DB::table('document_categories')->insert([
            'name' => $name,
            'slug' => $slug,
            'icon' => 'folder',
            'colour' => '#2458A6',
            'sort_order' => (int) DB::table('document_categories')->max('sort_order') + 1,
            'created_by' => $request->user()->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('document-server.categories')->with('success', 'Category created.');
    }

    public function update(Request $request, int $category): RedirectResponse
    {
        $this->requireAdministrator($request);
        $existing = DB::table('document_categories')->where('id', $category)->first();
        abort_unless($existing, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100', Rule::unique('document_categories', 'name')->ignore($category)],
        ]);
        $name = trim($validated['name']);
        $slug = $this->availableSlug($name, $category);

        DB::table('document_categories')->where('id', $category)->update([
            'name' => $name,
            'slug' => $slug,
            'updated_at' => now(),
        ]);

        return redirect()->route('document-server.categories', ['category' => $category])
            ->with('success', 'Category renamed.');
    }

    public function destroy(Request $request, int $category): RedirectResponse
    {
        $this->requireAdministrator($request);
        abort_unless(DB::table('document_categories')->where('id', $category)->exists(), 404);

        // Do not silently detach or lose category organisation, including recycled files.
        if (DB::table('documents')->where('category_id', $category)->exists()) {
            throw ValidationException::withMessages([
                'category' => 'This category contains documents. Reassign them before deleting it.',
            ]);
        }

        DB::table('document_categories')->where('id', $category)->delete();

        return redirect()->route('document-server.categories')->with('success', 'Empty category deleted.');
    }

    private function isAdministrator(int $userId): bool
    {
        return DB::table('role_user as ru')
            ->join('roles as r', 'r.id', '=', 'ru.role_id')
            ->where('ru.user_id', $userId)
            ->where('r.slug', 'administrator')
            ->exists();
    }

    private function requireAdministrator(Request $request): void
    {
        abort_unless($this->isAdministrator((int) $request->user()->id), 403);
    }

    private function availableSlug(string $name, ?int $exceptId = null): string
    {
        $slug = Str::slug($name);
        if ($slug === '') {
            throw ValidationException::withMessages(['name' => 'Use at least one letter or number in the category name.']);
        }
        $query = DB::table('document_categories')->where('slug', $slug);
        if ($exceptId !== null) {
            $query->where('id', '<>', $exceptId);
        }
        if ($query->exists()) {
            throw ValidationException::withMessages(['name' => 'A category with a similar name already exists.']);
        }
        return $slug;
    }
}
