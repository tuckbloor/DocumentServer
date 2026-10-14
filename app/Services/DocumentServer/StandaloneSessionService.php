<?php

namespace App\Services\DocumentServer;

use App\Support\DocumentServer\DocumentAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

/** Desktop URLs are scoped bearer capabilities, not another account/password. */
final class StandaloneSessionService
{
    public function issue(int $documentId, int $userId): array
    {
        return DB::transaction(function () use ($documentId, $userId): array {
            $doc = DB::table('documents')->where('id', $documentId)
                ->whereNull('deleted_at')->lockForUpdate()->first();
            abort_unless($doc && $doc->status === 'active', 404);
            abort_unless(in_array(strtolower($doc->extension), ['docx', 'xlsx'], true), 422, 'Only DOCX/XLSX are supported.');
            DocumentAccess::require($doc, $userId, 'view');
            $canEdit = DocumentAccess::allowed($doc, $userId, 'edit');
            if ($canEdit) {
                $other = DB::table('document_edit_sessions')
                    ->where('document_id', $documentId)->where('status', 'active')
                    ->where('editor_name', 'standalone-edit')
                    ->where('expires_at', '>', now())->first();
                if ($other) {
                    if ((int) $other->user_id !== $userId) {
                        // Never take control away from a different user.
                        throw new HttpException(423, 'This document is being edited by another user.');
                    }

                    // Keep this user's previous URL valid. LibreOffice may still be
                    // performing HEAD/OPTIONS/GET on it while another Open is clicked.
                    // Closing it here caused legitimate WebDAV requests to return 404.
                    // A second capability for the same user does not grant anyone else
                    // access, and all sessions are revoked by end() or on expiry.
                }
            }
            $secret = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
            $sessionId = DB::table('document_edit_sessions')->insertGetId([
                'document_id' => $documentId,
                'user_id' => $userId,
                'session_token_hash' => hash('sha256', $secret),
                'base_version' => (int) $doc->current_version,
                'editor_name' => $canEdit ? 'standalone-edit' : 'standalone-view',
                'status' => 'active',
                'started_at' => now(),
                'last_heartbeat_at' => now(),
                'expires_at' => now()->addHours(4),
            ]);
            DocumentAccess::record($userId, $documentId, 'desktop_session_started', $doc->title,
                ['session_id' => $sessionId, 'mode' => $canEdit ? 'edit' : 'view']);

            return ['secret' => $secret, 'document' => $doc, 'session_id' => $sessionId, 'mode' => $canEdit ? 'edit' : 'view'];
        });
    }

    public function resolve(string $secret): object
    {
        // Respond 404 for all failed checks: do not expose tokens, users or file names.
        if (! preg_match('/^[A-Za-z0-9_-]{43}$/D', $secret)) {
            $this->denied('bad_token_format');
        }

        $session = DB::table('document_edit_sessions')
            ->where('session_token_hash', hash('sha256', $secret))
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->first();
        if (! $session) {
            $this->denied('no_active_session');
        }

        $user = DB::table('users')->where('id', $session->user_id)->first();
        if (! $user || $user->account_status !== 'active' || ! $user->email_verified_at) {
            $this->denied('inactive_or_unverified_user');
        }

        $document = DB::table('documents')
            ->where('id', $session->document_id)
            ->whereNull('deleted_at')
            ->first();
        if (! $document || $document->status !== 'active') {
            $this->denied('document_unavailable');
        }

        $action = $session->editor_name === 'standalone-edit' ? 'edit' : 'view';
        if (! DocumentAccess::allowed($document, (int) $session->user_id, $action)) {
            $this->denied('permission_revoked');
        }

        return (object) ['session' => $session, 'document' => $document];
    }

    private function denied(string $reason): never
    {
        if (app()->environment('local')) {
            // No document URL, bearer token, file name or personal details in logs.
            Log::warning('Document Server session denied', ['reason' => $reason]);
        }
        abort(404);
    }

    public function end(int $documentId, int $userId): void
    {
        DB::table('document_edit_sessions')
            ->where('document_id', $documentId)->where('user_id', $userId)
            ->where('editor_name', 'standalone-edit')->where('status', 'active')
            ->update(['status' => 'closed', 'ended_at' => now(), 'expires_at' => now()]);
    }
}
