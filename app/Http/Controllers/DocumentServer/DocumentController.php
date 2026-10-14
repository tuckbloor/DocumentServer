<?php

namespace App\Http\Controllers\DocumentServer;

use App\Http\Controllers\Controller;
use App\Support\DocumentServer\DocumentAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class DocumentController extends Controller
{
    private function allowedExtension(string $filename): string
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        abort_unless(in_array($extension, ['docx', 'xlsx'], true), 422);

        return $extension;
    }

    private function privateFile(string $path, string $disk): string
    {
        // Imported and newly uploaded files are private; do not serve arbitrary disks or paths.
        abort_unless($disk === 'local' && str_starts_with($path, 'document-server/')
            && ! str_contains($path, '..') && ! str_contains($path, '\\'), 404);
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->path($path);
    }

    public function upload(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'files' => ['required', 'array', 'min:1', 'max:10'],
            'files.*' => ['required', 'file', 'mimes:docx,xlsx', 'max:20480'],
            'category_id' => ['nullable', 'integer', 'exists:document_categories,id'],
        ]);
        $createdPaths = [];
        try {
            DB::transaction(function () use ($data, $request, &$createdPaths) {
                foreach ($data['files'] as $file) {
                    $name = basename(str_replace('\\', '/', $file->getClientOriginalName()));
                    $extension = $this->allowedExtension($name);
                    $uuid = (string) Str::uuid();
                    $path = $file->storeAs('document-server/uploads/'.$request->user()->id.'/'.$uuid, $uuid.'.'.$extension, 'local');
                    if ($path === false) {
                        throw new \RuntimeException('Private file storage failed.');
                    }
                    $createdPaths[] = $path;
                    $now = now();
                    $id = DB::table('documents')->insertGetId([
                        'public_id' => $uuid,
                        'owner_id' => $request->user()->id,
                        'category_id' => $data['category_id'] ?? null,
                        'title' => Str::limit(pathinfo($name, PATHINFO_FILENAME), 255, ''),
                        'original_filename' => $name,
                        'extension' => $extension,
                        'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                        'size_bytes' => Storage::disk('local')->size($path),
                        'current_version' => 1,
                        'last_edited_by' => $request->user()->id,
                        'last_edited_at' => $now,
                        'status' => 'active',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    DB::table('document_versions')->insert([
                        'document_id' => $id,
                        'version_number' => 1,
                        'storage_disk' => 'local',
                        'storage_path' => $path,
                        'original_filename' => $name,
                        'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                        'size_bytes' => Storage::disk('local')->size($path),
                        'checksum_sha256' => hash_file('sha256', Storage::disk('local')->path($path)),
                        'source' => 'upload',
                        'created_by' => $request->user()->id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    DocumentAccess::record((int) $request->user()->id, $id, 'created', $name);
                }
            });
        } catch (Throwable $exception) {
            foreach ($createdPaths as $path) {
                Storage::disk('local')->delete($path);
            }
            throw $exception;
        }

        return back()->with('success', count($createdPaths).' document(s) uploaded securely.');
    }

    public function download(Request $request, int $document): BinaryFileResponse
    {
        $doc = DocumentAccess::document($document, (int) $request->user()->id);
        $version = DB::table('document_versions')->where('document_id', $doc->id)
            ->where('version_number', $doc->current_version)->first();
        abort_unless($version !== null, 404);
        $path = $this->privateFile($version->storage_path, $version->storage_disk);
        $recentKey = ['user_id' => $request->user()->id, 'document_id' => $doc->id];
        $previousCount = DB::table('document_recent_views')->where($recentKey)->value('open_count');
        DB::table('document_recent_views')->updateOrInsert($recentKey, [
            'last_opened_at' => now(), 'open_count' => ((int) $previousCount) + 1,
        ]);
        DocumentAccess::record((int) $request->user()->id, $doc->id, 'downloaded', $doc->title);

        return response()->download($path, $doc->original_filename, ['Cache-Control' => 'private, no-store']);
    }

    public function versions(Request $request, int $document): JsonResponse
    {
        $doc = DocumentAccess::document($document, (int) $request->user()->id);
        $versions = DB::table('document_versions as v')
            ->leftJoin('users as u', 'u.id', '=', 'v.created_by')
            ->where('v.document_id', $doc->id)->orderByDesc('v.version_number')
            ->get(['v.version_number', 'v.created_at', 'v.change_summary', 'v.size_bytes', 'u.name as author']);

        return response()->json(['versions' => $versions]);
    }

    public function downloadVersion(Request $request, int $document, int $version): BinaryFileResponse
    {
        $doc = DocumentAccess::document($document, (int) $request->user()->id);
        $rev = DB::table('document_versions')->where('document_id', $doc->id)->where('version_number', $version)->first();
        abort_unless($rev !== null, 404);
        $path = $this->privateFile($rev->storage_path, $rev->storage_disk);

        return response()->download($path, $rev->original_filename, ['Cache-Control' => 'private, no-store']);
    }

    public function revision(Request $request, int $document): RedirectResponse
    {
        $doc = DocumentAccess::document($document, (int) $request->user()->id, 'edit');
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:docx,xlsx', 'max:20480'],
            'expected_version' => ['required', 'integer', 'min:1'],
            'change_summary' => ['nullable', 'string', 'max:500'],
        ]);
        $extension = $this->allowedExtension($data['file']->getClientOriginalName());
        if ($extension !== $doc->extension) {
            throw ValidationException::withMessages(['file' => 'The replacement must have the same file type.']);
        }
        $uuid = (string) Str::uuid();
        $path = $data['file']->storeAs('document-server/revisions/'.$doc->public_id, $uuid.'.'.$extension, 'local');
        if ($path === false) {
            throw new \RuntimeException('Could not save the revision.');
        }
        try {
            DB::transaction(function () use ($request, $doc, $data, $path) {
                $current = DB::table('documents')->where('id', $doc->id)->whereNull('deleted_at')->lockForUpdate()->first();
                abort_unless($current !== null, 404);
                DocumentAccess::require($current, (int) $request->user()->id, 'edit');
                if ((int) $current->current_version !== (int) $data['expected_version']) {
                    throw ValidationException::withMessages(['expected_version' => 'Another user saved a new version. Refresh before replacing the file.']);
                }
                $next = (int) $current->current_version + 1;
                $name = basename(str_replace('\\', '/', $data['file']->getClientOriginalName()));
                $size = Storage::disk('local')->size($path);
                DB::table('document_versions')->insert([
                    'document_id' => $current->id, 'version_number' => $next,
                    'storage_disk' => 'local', 'storage_path' => $path,
                    'original_filename' => $name,
                    'mime_type' => $data['file']->getMimeType() ?: 'application/octet-stream',
                    'size_bytes' => $size,
                    'checksum_sha256' => hash_file('sha256', Storage::disk('local')->path($path)),
                    'source' => 'upload', 'change_summary' => $data['change_summary'] ?? null,
                    'created_by' => $request->user()->id,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                DB::table('documents')->where('id', $current->id)->update([
                    'original_filename' => $name,
                    'mime_type' => $data['file']->getMimeType() ?: 'application/octet-stream',
                    'size_bytes' => $size, 'current_version' => $next,
                    'last_edited_by' => $request->user()->id,
                    'last_edited_at' => now(), 'updated_at' => now(),
                ]);
                DocumentAccess::record((int) $request->user()->id, $current->id, 'saved', $current->title, ['version' => $next]);
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        return back()->with('success', 'New document version saved.');
    }

    public function destroy(Request $request, int $document): RedirectResponse
    {
        $doc = DocumentAccess::document($document, (int) $request->user()->id, 'owner');
        DB::table('documents')->where('id', $doc->id)->update(['deleted_at' => now(), 'updated_at' => now()]);
        DocumentAccess::record((int) $request->user()->id, $doc->id, 'deleted', $doc->title);

        return back()->with('success', 'Document moved to Recycle Bin.');
    }

    public function restore(Request $request, int $document): RedirectResponse
    {
        $doc = DocumentAccess::document($document, (int) $request->user()->id, 'owner', true);
        abort_unless($doc->deleted_at !== null, 404);
        DB::table('documents')->where('id', $doc->id)->update(['deleted_at' => null, 'updated_at' => now()]);
        DocumentAccess::record((int) $request->user()->id, $doc->id, 'restored', $doc->title);

        return redirect('/dashboard?tab=trash')->with('success', 'Document restored.');
    }
}
