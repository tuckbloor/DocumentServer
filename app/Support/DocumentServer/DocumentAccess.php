<?php

namespace App\Support\DocumentServer;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class DocumentAccess
{
    public static function activeShare(int $documentId, int $userId): ?object
    {
        return DB::table('document_shares')
            ->where('document_id', $documentId)
            ->where('user_id', $userId)
            ->whereNull('revoked_at')
            ->where(fn (Builder $query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->first();
    }

    public static function allowed(object $document, int $userId, string $action = 'view'): bool
    {
        if ((int) $document->owner_id === $userId) {
            return true;
        }

        $share = self::activeShare((int) $document->id, $userId);

        return match ($action) {
            'view' => $share !== null,
            'edit' => $share !== null && $share->permission === 'edit',
            'share' => $share !== null && (bool) $share->can_reshare,
            'owner' => false,
            default => false,
        };
    }

    public static function require(object $document, int $userId, string $action = 'view'): void
    {
        abort_unless(self::allowed($document, $userId, $action), 403);
    }

    public static function visibleTo(int $userId, bool $includeDeleted = false): Builder
    {
        $query = DB::table('documents as d')->where(function (Builder $q) use ($userId) {
            $q->where('d.owner_id', $userId)
                ->orWhereExists(function (Builder $shared) use ($userId) {
                    $shared->selectRaw('1')
                        ->from('document_shares as ds')
                        ->whereColumn('ds.document_id', 'd.id')
                        ->where('ds.user_id', $userId)
                        ->whereNull('ds.revoked_at')
                        ->where(function (Builder $expiry) {
                            $expiry->whereNull('ds.expires_at')->orWhere('ds.expires_at', '>', now());
                        });
                });
        });

        if (! $includeDeleted) {
            $query->whereNull('d.deleted_at');
        }

        return $query;
    }

    public static function document(int $documentId, int $userId, string $action = 'view', bool $includeDeleted = false): object
    {
        $document = DB::table('documents')->where('id', $documentId)
            ->when(! $includeDeleted, fn (Builder $q) => $q->whereNull('deleted_at'))
            ->first();

        abort_unless($document !== null, 404);
        self::require($document, $userId, $action);

        return $document;
    }

    public static function record(int $userId, ?int $documentId, string $event, ?string $title = null, array $meta = []): void
    {
        DB::table('document_activities')->insert([
            'document_id' => $documentId,
            'user_id' => $userId,
            'event' => $event,
            'document_title' => $title,
            'metadata' => json_encode($meta, JSON_THROW_ON_ERROR),
            'created_at' => now(),
        ]);
    }
}
