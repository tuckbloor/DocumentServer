<?php

namespace App\Http\Controllers\DocumentServer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class DocumentDirectoryController extends Controller
{
    private function requireAdministrator(Request $request): void
    {
        abort_unless(DB::table('role_user as ru')->join('roles as r', 'r.id', '=', 'ru.role_id')
            ->where('ru.user_id', $request->user()->id)->where('r.slug', 'administrator')->exists(), 403);
    }

    private function viewer(Request $request): array
    {
        $profile = DB::table('user_profiles')->where('user_id', $request->user()->id)->first();

        return [
            'name' => $request->user()->name,
            'role' => 'Administrator',
            'avatar' => $profile?->avatar_path && $profile->avatar_disk === 'public'
                ? Storage::disk('public')->url($profile->avatar_path) : '/images/users/avatar-default.png',
        ];
    }

    public function users(Request $request): Response
    {
        $this->requireAdministrator($request);
        $users = DB::table('users as u')
            ->leftJoin('user_profiles as p', 'p.user_id', '=', 'u.id')
            ->leftJoin('role_user as ru', 'ru.user_id', '=', 'u.id')
            ->leftJoin('roles as r', 'r.id', '=', 'ru.role_id')
            ->select('u.id', 'u.name', 'u.email', 'u.account_status', 'p.job_title', 'p.avatar_path', 'r.name as role')
            ->orderBy('u.id')->get()->map(fn ($u) => [
                'id' => $u->id, 'name' => $u->name, 'email' => $u->email,
                'status' => $u->account_status, 'job_title' => $u->job_title,
                'role' => $u->role ?: 'Member',
                'avatar' => $u->avatar_path ? Storage::disk('public')->url($u->avatar_path) : '/images/users/avatar-default.png',
            ]);

        return Inertia::render('DocumentServerUsers', ['team' => $users, 'viewer' => $this->viewer($request)]);
    }

    public function activity(Request $request): Response
    {
        $this->requireAdministrator($request);
        $activity = DB::table('document_activities as da')
            ->leftJoin('users as u', 'u.id', '=', 'da.user_id')
            ->select('da.id', 'da.document_title', 'da.event', 'da.created_at', 'u.name as user_name')
            ->orderByDesc('da.id')->paginate(25);

        return Inertia::render('DocumentServerActivity', ['activity' => $activity, 'viewer' => $this->viewer($request)]);
    }
}
