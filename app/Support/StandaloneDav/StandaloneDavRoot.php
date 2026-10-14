<?php

namespace App\Support\StandaloneDav;

use App\Services\DocumentServer\StandaloneRevisionService;
use App\Services\DocumentServer\StandaloneSessionService;
use Sabre\DAV\Collection;
use Sabre\DAV\Exception\NotFound;

/** A single-document WebDAV collection, no directory traversal or file enumeration. */
final class StandaloneDavRoot extends Collection
{
    public function __construct(private string $secret, private StandaloneSessionService $sessions,
        private StandaloneRevisionService $revisions, private ?string $ifMatch) {}

    public function getName()
    {
        return 'root';
    }

    public function getChildren(): array
    {
        return [new StandaloneDavFile($this->secret, $this->sessions, $this->revisions, $this->ifMatch)];
    }

    public function getChild($name)
    {
        $expected = $this->sessions->resolve($this->secret)->document->original_filename;
        if ($name !== $expected) {
            throw new NotFound('Unknown file');
        }

        return new StandaloneDavFile($this->secret, $this->sessions, $this->revisions, $this->ifMatch);
    }
}
