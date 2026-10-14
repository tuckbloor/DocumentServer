<?php

namespace App\Services\DocumentServer;

use App\Support\DocumentServer\DocumentAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\PreconditionFailed;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Exception\InsufficientStorage;

final class StandaloneRevisionService
{
    private const MAX_SIZE = 20 * 1024 * 1024;

    public static function etag(object $version): string
    {
        return '"ds-'.(int)$version->document_id.'-'.(int)$version->version_number.'-'.($version->checksum_sha256 ?: 'original').'"';
    }

    /** Writes to a unique private file. Updates the current version atomically. */
    public function save(object $session, $body, ?string $ifMatch = null): string
    {
        if ($session->editor_name !== 'standalone-edit') throw new Forbidden('View-only session');
        $document = DB::table('documents')->where('id', $session->document_id)->whereNull('deleted_at')->first();
        if (!$document || $document->status !== 'active') throw new NotFound('Document unavailable');
        if (!DocumentAccess::allowed($document, (int)$session->user_id, 'edit')) throw new Forbidden('Permission revoked');
        if (in_array(strtolower((string)$document->extension), ['docx','xlsx'], true) === false) throw new Forbidden('Unsupported file type');

        $stream = fopen('php://temp/maxmemory:2097152', 'w+b');
        if (!$stream) throw new InsufficientStorage('Temporary stream unavailable');
        try {
            if (is_resource($body)) {
                $read = stream_copy_to_stream($body, $stream, self::MAX_SIZE + 1);
                if ($read === false || $read > self::MAX_SIZE) throw new Forbidden('Maximum file size is 20 MB');
            } elseif (is_string($body)) {
                if (strlen($body) > self::MAX_SIZE) throw new Forbidden('Maximum file size is 20 MB');
                fwrite($stream, $body);
            } else throw new Forbidden('Invalid upload stream');
            if (ftell($stream) < 4) throw new Forbidden('Refusing empty/incomplete Office file');
            rewind($stream);
            if (fread($stream, 4) !== "PK\x03\x04") throw new Forbidden('Expected an Office DOCX/XLSX ZIP file');
            rewind($stream);
            $path = 'document-server/revisions/'.$document->public_id.'/'.Str::uuid().'.'.$document->extension;
            if (!Storage::disk('local')->put($path, $stream)) throw new InsufficientStorage('Private storage unavailable');
        } finally {
            fclose($stream);
        }

        try {
            $disk = Storage::disk('local');
            $size = $disk->size($path);
            $hash = hash_file('sha256', $disk->path($path));
            if (!$hash || $size < 4 || $size > self::MAX_SIZE) throw new Forbidden('Invalid document');
            return DB::transaction(function () use ($session, $document, $ifMatch, $path, $size, $hash): string {
                $freshSession = DB::table('document_edit_sessions')->where('id', $session->id)->lockForUpdate()->first();
                if (!$freshSession || $freshSession->status !== 'active' || strtotime($freshSession->expires_at) <= time()
                    || $freshSession->editor_name !== 'standalone-edit') throw new Forbidden('Editing session ended');
                $freshDoc = DB::table('documents')->where('id', $document->id)->whereNull('deleted_at')->lockForUpdate()->first();
                if (!$freshDoc || $freshDoc->status !== 'active') throw new NotFound('Document removed');
                if (!DocumentAccess::allowed($freshDoc, (int)$freshSession->user_id, 'edit')) throw new Forbidden('Permission revoked');
                if ((int)$freshDoc->current_version !== (int)$freshSession->base_version)
                    throw new PreconditionFailed('Another version exists. Save blocked to protect changes.');
                $latest = DB::table('document_versions')->where('document_id', $freshDoc->id)
                    ->where('version_number', $freshDoc->current_version)->first();
                if (!$latest) throw new NotFound('Current revision unavailable');
                $etag = self::etag($latest);
                if ($ifMatch && trim($ifMatch) !== '*'
                    && !in_array($etag, array_map('trim', explode(',', $ifMatch)), true)) {
                    throw new PreconditionFailed('Document changed since opening');
                }
                $hashCurrent = $latest->checksum_sha256;
                if (!$hashCurrent && $latest->storage_disk === 'local'
                    && Storage::disk('local')->exists($latest->storage_path)) {
                    $hashCurrent = hash_file('sha256', Storage::disk('local')->path($latest->storage_path));
                }
                if ($hashCurrent && hash_equals($hashCurrent, $hash)) {
                    DB::table('document_edit_sessions')->where('id', $freshSession->id)->update(['last_heartbeat_at' => now()]);
                    return $etag;
                }
                $next = (int)$freshDoc->current_version + 1;
                $now = now();
                DB::table('document_versions')->insert([
                    'document_id' => $freshDoc->id, 'version_number' => $next,
                    'storage_disk' => 'local', 'storage_path' => $path,
                    'original_filename' => $freshDoc->original_filename, 'mime_type' => $freshDoc->mime_type,
                    'size_bytes' => $size, 'checksum_sha256' => $hash,
                    'source' => 'desktop', 'change_summary' => 'Saved from standalone desktop Office',
                    'created_by' => $freshSession->user_id, 'created_at' => $now, 'updated_at' => $now,
                ]);
                DB::table('documents')->where('id', $freshDoc->id)->update([
                    'current_version' => $next, 'size_bytes' => $size,
                    'last_edited_by' => $freshSession->user_id, 'last_edited_at' => $now, 'updated_at' => $now,
                ]);
                DB::table('document_edit_sessions')->where('id', $freshSession->id)->update([
                    'base_version' => $next, 'last_heartbeat_at' => $now,
                ]);
                DocumentAccess::record((int)$freshSession->user_id, (int)$freshDoc->id,
                    'saved', $freshDoc->title, ['version' => $next, 'source' => 'standalone-webdav']);
                return '"ds-'.(int)$freshDoc->id.'-'.$next.'-'.$hash.'"';
            }, 3);
        } finally {
            if (!DB::table('document_versions')->where('storage_disk', 'local')->where('storage_path', $path)->exists()) {
                Storage::disk('local')->delete($path);
            }
        }
    }
}
