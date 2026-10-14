<?php

namespace App\Http\Controllers\DocumentServer;

use App\Http\Controllers\Controller;
use App\Support\DocumentServer\DocumentAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DocumentShareController extends Controller
{
    public function store(Request $request, int $document): RedirectResponse
    {
        $doc = DocumentAccess::document($document, (int) $request->user()->id, 'share');
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'permission' => ['required', 'in:view,edit'],
        ]);
        if ((int) $data['user_id'] === (int) $doc->owner_id) {
            throw ValidationException::withMessages(['user_id' => 'The document owner already has full access.']);
        }
        if (! DB::table('users')->where('id', $data['user_id'])->where('account_status', 'active')->exists()) {
            throw ValidationException::withMessages(['user_id' => 'This account is not active.']);
        }
        DB::table('document_shares')->updateOrInsert(
            ['document_id' => $doc->id, 'user_id' => $data['user_id']],
            [
                'shared_by' => $request->user()->id,
                'permission' => $data['permission'],
                'revoked_at' => null,
                'expires_at' => null,
                'can_reshare' => false,
                'updated_at' => now(),
            ]
        );
        DB::table('document_notifications')->insert([
            'user_id' => $data['user_id'], 'actor_id' => $request->user()->id,
            'document_id' => $doc->id, 'type' => 'document_shared',
            'title' => 'A document was shared with you',
            'message' => $doc->title.' — '.strtoupper($data['permission']).' access',
            'created_at' => now(),
        ]);
        DocumentAccess::record((int) $request->user()->id, $doc->id, 'shared', $doc->title, ['recipient_id' => $data['user_id'], 'permission' => $data['permission']]);
        return back()->with('success', 'Document shared successfully.');
    }

    public function destroy(Request $request, int $document, int $recipient): RedirectResponse
    {
        $doc = DocumentAccess::document($document, (int) $request->user()->id, 'share');
        DB::table('document_shares')->where('document_id', $doc->id)->where('user_id', $recipient)
            ->whereNull('revoked_at')->update(['revoked_at' => now(), 'updated_at' => now()]);
        DocumentAccess::record((int) $request->user()->id, $doc->id, 'revoked', $doc->title, ['recipient_id' => $recipient]);
        return back()->with('success', 'Document access removed.');
    }
}
