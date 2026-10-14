<?php

namespace App\Http\Controllers\DocumentServer;

use App\Http\Controllers\Controller;
use App\Support\DocumentServer\DocumentAccess;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class DocumentFavouritesPageController extends Controller
{
    public function index(Request $request): Response
    {
        $userId = (int) $request->user()->id;
        $searchInput = $request->query('search', '');
        $search = trim(mb_substr(is_string($searchInput) ? $searchInput : '', 0, 120));
        $type = $request->query('type');
        $type = in_array($type, ['word', 'excel'], true) ? $type : '';
        $sort = $request->query('sort');
        $sort = in_array($sort, ['latest', 'oldest', 'name'], true) ? $sort : 'latest';

        // The existing access helper enforces ownership, active sharing and soft deletion.
        // Joining the favourites table additionally limits results to THIS user.
        $query = DocumentAccess::visibleTo($userId)
            ->join('document_favourites as df', function ($join) use ($userId) {
                $join->on('df.document_id', '=', 'd.id')
                    ->where('df.user_id', '=', $userId);
            });

        $count = (clone $query)->count('d.id');

        if ($search !== '') {
            $query->where(function (Builder $q) use ($search) {
                $q->where('d.title', 'like', '%'.$search.'%')
                    ->orWhere('d.original_filename', 'like', '%'.$search.'%');
            });
        }
        if ($type === 'word') {
            $query->where('d.extension', 'docx');
        } elseif ($type === 'excel') {
            $query->where('d.extension', 'xlsx');
        }

        match ($sort) {
            'name' => $query->orderBy('d.title')->orderBy('d.id'),
            'oldest' => $query->orderBy('d.updated_at')->orderBy('d.id'),
            default => $query->orderByDesc('d.updated_at')->orderByDesc('d.id'),
        };

        $documents = $query
            ->leftJoin('document_categories as c', 'c.id', '=', 'd.category_id')
            ->join('users as owner', 'owner.id', '=', 'd.owner_id')
            ->select([
                'd.id', 'd.title', 'd.original_filename as filename', 'd.extension',
                'd.size_bytes', 'd.current_version as version', 'd.updated_at',
                'd.owner_id', 'owner.name as owner', 'c.name as category',
            ])
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
            'category' => $doc->category ?: 'Uncategorised',
            'mine' => (int) $doc->owner_id === $userId,
        ]);

        $profile = DB::table('user_profiles')->where('user_id', $userId)->first();
        $role = DB::table('role_user as ru')
            ->join('roles as r', 'r.id', '=', 'ru.role_id')
            ->where('ru.user_id', $userId)
            ->orderByRaw("CASE r.slug WHEN 'administrator' THEN 1 WHEN 'manager' THEN 2 ELSE 3 END")
            ->value('r.name') ?: 'Member';

        return Inertia::render('DocumentServerFavourites', [
            'documents' => $documents,
            'count' => $count,
            'filters' => ['search' => $search, 'type' => $type, 'sort' => $sort],
            'viewer' => [
                'name' => $request->user()->name,
                'role' => $role,
                'avatar' => $profile?->avatar_path && $profile->avatar_disk === 'public'
                    ? Storage::disk('public')->url($profile->avatar_path)
                    : '/images/users/avatar-default.png',
            ],
            'unreadNotifications' => DB::table('document_notifications')
                ->where('user_id', $userId)->whereNull('read_at')->count(),
        ]);
    }
}
