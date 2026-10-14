<?php

namespace App\Http\Controllers\DocumentServer;

use App\Http\Controllers\Controller;
use App\Support\DocumentServer\DocumentAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/** Current revision and remote write status, visible only to an authorised user. */
final class StandaloneStatusController extends Controller
{
    public function show(int $document, Request $request): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $doc = DB::table('documents')->where('id', $document)->whereNull('deleted_at')->first();
        abort_unless($doc && $doc->status === 'active', 404);
        DocumentAccess::require($doc, $userId, 'view');

        $sessionId = $request->integer('session_id');
        $session = $sessionId > 0 ? DB::table('document_edit_sessions')
            ->where('id', $sessionId)->where('document_id', $document)
            ->where('user_id', $userId)->first() : null;

        $state = $session ? Cache::get('document-server:standalone:dav:'.$session->id, []) : [];

        return response()->json([
            'version' => (int) $doc->current_version,
            'session_active' => $session !== null && $session->status === 'active'
                && strtotime((string) $session->expires_at) > time(),
            'last_request_method' => $state['last_method'] ?? null,
            'last_request_status' => $state['last_status'] ?? null,
            'last_write_method' => $state['last_write_method'] ?? null,
            'last_write_status' => $state['last_write_status'] ?? null,
            'last_write_at' => $state['last_write_at'] ?? null,
        ])->header('Cache-Control', 'no-store');
    }
}
