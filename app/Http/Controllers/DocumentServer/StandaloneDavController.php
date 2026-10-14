<?php

namespace App\Http\Controllers\DocumentServer;

use App\Http\Controllers\Controller;
use App\Services\DocumentServer\StandaloneRevisionService;
use App\Services\DocumentServer\StandaloneSessionService;
use App\Support\StandaloneDav\StandaloneDavRoot;
use App\Support\StandaloneDav\StandaloneDavSapi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Sabre\DAV\Locks\Backend\File as LockFile;
use Sabre\DAV\Locks\Plugin as LockPlugin;
use Sabre\DAV\Server;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

final class StandaloneDavController extends Controller
{
    public function handle(string $token, Request $request, StandaloneSessionService $sessions,
        StandaloneRevisionService $revisions): Response
    {
        // The URI token replaces an Office password. Never expose it over a real network without TLS.
        $loopback = app()->environment('local') && in_array($request->getHost(), ['localhost', '127.0.0.1'], true);
        abort_unless($request->isSecure() || app()->environment('testing') || $loopback, 403, 'HTTPS required');
        try {
            $resolved = $sessions->resolve($token); // Check token and permissions on every WebDAV request.
        } catch (HttpExceptionInterface $e) {
            if (app()->environment('local')) {
                Log::warning('Document Server WebDAV denied', [
                    'method' => $request->method(),
                    'status' => $e->getStatusCode(),
                ]);
            }
            throw $e;
        }
        if ($resolved->session->editor_name !== 'standalone-edit'
            && in_array(strtoupper($request->method()), ['PUT', 'LOCK', 'UNLOCK', 'PROPPATCH'], true)) {
            abort(403, 'View-only session');
        }
        StandaloneDavSapi::prepare($request);
        $server = new Server(
            new StandaloneDavRoot($token, $sessions, $revisions, $request->header('If-Match')),
            new StandaloneDavSapi
        );
        $server->setBaseUri('/standalone-dav/'.$token.'/');
        $server->debugExceptions = false;
        // Word commonly requires WebDAV LOCK/UNLOCK to enable editing.
        $lockFile = storage_path('app/private/document-server-standalone-locks.db');
        $server->addPlugin(new LockPlugin(new LockFile($lockFile)));
        $server->start();
        $result = StandaloneDavSapi::takeResponse();
        if (! $result) {
            return response('WebDAV response unavailable', 500);
        }
        $headers = [];
        foreach ($result->getHeaders() as $key => $values) {
            $headers[$key] = $values;
        }
        $headers['Cache-Control'] = 'private, no-store';
        $headers['Referrer-Policy'] = 'no-referrer';
        $headers['X-Content-Type-Options'] = 'nosniff';
        $status = $result->getStatus();
        // Keep an authenticated, short-lived status for the dashboard. No bearer URL,
        // password or full request headers are recorded in the cache.
        $cacheKey = 'document-server:standalone:dav:'.(int) $resolved->session->id;
        $state = Cache::get($cacheKey, []);
        $state['last_method'] = strtoupper($request->method());
        $state['last_status'] = $status;
        $state['last_request_at'] = now()->toIso8601String();
        if (in_array($state['last_method'], ['PUT', 'MOVE', 'COPY', 'DELETE', 'PROPPATCH'], true)) {
            $state['last_write_method'] = $state['last_method'];
            $state['last_write_status'] = $status;
            $state['last_write_at'] = $state['last_request_at'];
        }
        Cache::put($cacheKey, $state, now()->addHours(4));
        // Log only the request method, response status and numeric document ID.
        // Never log the bearer-token URL or headers containing it.
        if (app()->environment('local')) {
            Log::info('Document Server WebDAV response', [
                'method' => $request->method(),
                'status' => $status,
                'document_id' => (int) $resolved->document->id,
            ]);
        }
        $body = $result->getBody();
        if ($body === null || $request->isMethod('HEAD')) {
            return response('', $status, $headers);
        }

        return new StreamedResponse(function () use ($body): void {
            if (is_resource($body)) {
                try {
                    while (! feof($body)) {
                        echo fread($body, 65536);
                    }
                } finally {
                    fclose($body);
                }
            } elseif (is_callable($body)) {
                $body();
            } else {
                echo $body;
            }
        }, $status, $headers);
    }
}
