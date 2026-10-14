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

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $userId = (int) $request->user()->id;
        $tab = in_array($request->query('tab'), ['all', 'mine', 'shared', 'recent', 'favourites', 'trash'], true)
            ? $request->query('tab') : 'all';
        $view = in_array($request->query('view'), ['grid', 'list'], true) ? $request->query('view') : null;
        $perPage = (int) (DB::table('user_preferences')->where('user_id', $userId)->value('documents_per_page') ?: 15);
        $perPage = min(max($perPage, 5), 50);

        $visible = DocumentAccess::visibleTo($userId);
        $query = $tab === 'trash'
            ? DB::table('documents as d')->where('d.owner_id', $userId)->whereNotNull('d.deleted_at')
            : DocumentAccess::visibleTo($userId);

        if ($tab === 'mine') {
            $query->where('d.owner_id', $userId);
        } elseif ($tab === 'shared') {
            $query->where('d.owner_id', '<>', $userId);
        } elseif ($tab === 'favourites') {
            $query->whereExists(fn (Builder $q) => $q->selectRaw('1')->from('document_favourites as df')
                ->whereColumn('df.document_id', 'd.id')->where('df.user_id', $userId));
        } elseif ($tab === 'recent') {
            $query->join('document_recent_views as dr', function ($join) use ($userId) {
                $join->on('dr.document_id', '=', 'd.id')->where('dr.user_id', '=', $userId);
            });
        }

        $search = trim(mb_substr((string) $request->query('search', ''), 0, 120));
        if ($search !== '') {
            $query->where(function (Builder $q) use ($search) {
                $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $search);
                $q->where('d.title', 'like', '%'.$escaped.'%')
                    ->orWhere('d.original_filename', 'like', '%'.$escaped.'%');
            });
        }
        $type = $request->query('type');
        if ($type === 'word') {
            $query->where('d.extension', 'docx');
        } elseif ($type === 'excel') {
            $query->where('d.extension', 'xlsx');
        }
        $category = $request->integer('category');
        if ($category > 0) {
            $query->where('d.category_id', $category);
        }

        $sort = in_array($request->query('sort'), ['latest', 'name', 'oldest'], true) ? $request->query('sort') : 'latest';
        if ($tab === 'recent') {
            $query->orderByDesc('dr.last_opened_at');
        } elseif ($sort === 'name') {
            $query->orderBy('d.title');
        } elseif ($sort === 'oldest') {
            $query->orderBy('d.updated_at');
        } else {
            $query->orderByDesc($tab === 'trash' ? 'd.deleted_at' : 'd.updated_at');
        }

        $docs = $query
            ->leftJoin('document_categories as c', 'c.id', '=', 'd.category_id')
            ->leftJoin('users as owner', 'owner.id', '=', 'd.owner_id')
            ->select('d.*', 'c.name as category_name', 'owner.name as owner_name')
            ->paginate($perPage)
            ->withQueryString();

        $ids = $docs->getCollection()->pluck('id')->all();
        $myShares = DB::table('document_shares')->where('user_id', $userId)->whereIn('document_id', $ids)
            ->whereNull('revoked_at')->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->get()->keyBy('document_id');
        $ownedIds = $docs->getCollection()->where('owner_id', $userId)->pluck('id')->all();
        $recipients = DB::table('document_shares as ds')->join('users as u', 'u.id', '=', 'ds.user_id')
            ->leftJoin('user_profiles as up', 'up.user_id', '=', 'u.id')
            ->whereIn('ds.document_id', $ownedIds)->whereNull('ds.revoked_at')
            ->where(fn (Builder $q) => $q->whereNull('ds.expires_at')->orWhere('ds.expires_at', '>', now()))
            ->select('ds.document_id', 'u.id', 'u.name', 'u.email', 'ds.permission', 'up.avatar_path', 'up.avatar_disk')
            ->get()->groupBy('document_id');
        $favourites = DB::table('document_favourites')->where('user_id', $userId)->whereIn('document_id', $ids)
            ->pluck('document_id')->all();

        $docs->through(function ($doc) use ($userId, $myShares, $recipients, $favourites) {
            $owned = (int) $doc->owner_id === $userId;
            $share = $myShares->get($doc->id);
            $canShare = $owned || ($share !== null && (bool) $share->can_reshare);

            return [
                'id' => $doc->id,
                'public_id' => $doc->public_id,
                'title' => $doc->title,
                'filename' => $doc->original_filename,
                'extension' => $doc->extension,
                'category' => $doc->category_name ?: 'Uncategorised',
                'owner' => $doc->owner_name,
                'mine' => $owned,
                'can_edit' => $owned || ($share !== null && $share->permission === 'edit'),
                'can_share' => $canShare,
                'favourite' => in_array($doc->id, $favourites),
                'shared_with' => ($canShare ? ($recipients->get($doc->id) ?? collect()) : collect())->map(fn ($u) => [
                    'id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'permission' => $u->permission,
                    'avatar' => $u->avatar_path && $u->avatar_disk === 'public' ? Storage::disk('public')->url($u->avatar_path) : '/images/users/avatar-default.png',
                ])->values()->all(),
                'version' => $doc->current_version,
                'size_bytes' => $doc->size_bytes,
                'updated_at' => $doc->updated_at,
                'deleted_at' => $doc->deleted_at,
            ];
        });

        $profile = DB::table('user_profiles')->where('user_id', $userId)->first();
        $avatar = $profile?->avatar_path && $profile->avatar_disk === 'public'
            ? Storage::disk('public')->url($profile->avatar_path) : '/images/users/avatar-default.png';
        $role = DB::table('role_user as ru')->join('roles as r', 'ru.role_id', '=', 'r.id')
            ->where('ru.user_id', $userId)->orderByRaw("case r.slug when 'administrator' then 1 when 'manager' then 2 else 3 end")
            ->value('r.name') ?: 'Member';

        $sharedOwned = (clone $visible)->whereExists(fn (Builder $q) => $q->selectRaw('1')->from('document_shares as ds')
            ->whereColumn('ds.document_id', 'd.id')->whereNull('ds.revoked_at')
            ->where(fn (Builder $dates) => $dates->whereNull('ds.expires_at')->orWhere('ds.expires_at', '>', now())))->count();

        return Inertia::render('Dashboard', [
            'documents' => $docs,
            'stats' => [
                'total' => (clone $visible)->count(),
                'word' => (clone $visible)->where('d.extension', 'docx')->count(),
                'excel' => (clone $visible)->where('d.extension', 'xlsx')->count(),
                'shared' => $sharedOwned,
                'users' => DB::table('users')->where('account_status', 'active')->count(),
                'received' => (clone $visible)->where('d.owner_id', '<>', $userId)->count(),
            ],
            'categories' => DB::table('document_categories')->orderBy('sort_order')->get(['id', 'name']),
            'users' => DB::table('users')->where('id', '<>', $userId)->where('account_status', 'active')->orderBy('name')->get(['id', 'name', 'email']),
            'filters' => ['tab' => $tab, 'search' => $search, 'type' => $type ?: '', 'category' => $category ?: '', 'sort' => $sort, 'show_categories' => $request->boolean('show_categories'), 'view' => $view ?: (DB::table('user_preferences')->where('user_id', $userId)->value('document_view') ?: 'grid')],
            'viewer' => ['name' => $request->user()->name, 'avatar' => $avatar, 'role' => $role],
            'unreadNotifications' => DB::table('document_notifications')->where('user_id', $userId)->whereNull('read_at')->count(),
        ]);
    }
}
