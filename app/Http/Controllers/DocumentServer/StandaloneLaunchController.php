<?php

namespace App\Http\Controllers\DocumentServer;

use App\Http\Controllers\Controller;
use App\Services\DocumentServer\StandaloneSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class StandaloneLaunchController extends Controller
{
    public function open(int $document, Request $request, StandaloneSessionService $sessions): JsonResponse
    {
        $issued = $sessions->issue($document, (int)$request->user()->id);
        $doc = $issued['document'];
        $filename = rawurlencode($doc->original_filename);
        $fileUrl = $request->getSchemeAndHttpHost().'/standalone-dav/'.$issued['secret'].'/'.$filename;
        $scheme = $doc->extension === 'xlsx' ? 'ms-excel:' : 'ms-word:';
        return response()->json([
            'open_uri' => $scheme.($issued['mode'] === 'edit' ? 'ofe|u|' : 'ofv|u|').$fileUrl,
            'mode' => $issued['mode'],
            'session_id' => $issued['session_id'],
            'expires_in_minutes' => 240,
        ])->header('Cache-Control', 'no-store')->header('Referrer-Policy', 'no-referrer');
    }

    public function close(int $document, Request $request, StandaloneSessionService $sessions): JsonResponse
    {
        $sessions->end($document, (int)$request->user()->id);
        return response()->json(['closed' => true]);
    }
}
