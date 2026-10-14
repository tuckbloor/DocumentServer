<?php

namespace App\Support\StandaloneDav;

use App\Services\DocumentServer\StandaloneRevisionService;
use App\Services\DocumentServer\StandaloneSessionService;
use App\Support\DocumentServer\DocumentAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\File;

final class StandaloneDavFile extends File
{
    public function __construct(private string $secret, private StandaloneSessionService $sessions,
        private StandaloneRevisionService $revisions, private ?string $ifMatch = null) {}

    private function current(): array
    {
        $resolved = $this->sessions->resolve($this->secret);
        $doc = $resolved->document;
        $version = DB::table('document_versions')->where('document_id', $doc->id)
            ->where('version_number', $doc->current_version)->first();
        if (!$version || $version->storage_disk !== 'local'
            || !str_starts_with($version->storage_path, 'document-server/')
            || str_contains($version->storage_path, '..')
            || !Storage::disk('local')->exists($version->storage_path)) throw new NotFound('Revision unavailable');
        return [$resolved->session, $doc, $version];
    }

    public function getName() { return $this->sessions->resolve($this->secret)->document->original_filename; }

    public function get()
    {
        [$session, $doc, $version] = $this->current();
        DocumentAccess::record((int)$session->user_id, (int)$doc->id, 'opened', $doc->title,
            ['source' => 'standalone-webdav']);
        return fopen(Storage::disk('local')->path($version->storage_path), 'rb');
    }

    public function put($data)
    {
        [$session] = $this->current();
        return $this->revisions->save($session, $data, $this->ifMatch);
    }

    public function getSize() { return (int)$this->current()[2]->size_bytes; }
    public function getETag() { return StandaloneRevisionService::etag($this->current()[2]); }
    public function getLastModified() { return strtotime($this->current()[1]->updated_at); }
    public function getContentType()
    {
        return $this->current()[1]->extension === 'xlsx'
            ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            : 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
    }
}
