<?php

namespace App\Services\Sync;

/**
 * Reads the envelope the client queues for every type added after the original four.
 *
 * A payload with no `action` is a create, and a payload with no `body` is the flat
 * body itself. That keeps a todo or customer queued by an older build valid.
 */
class SyncAction
{
    public static function name(array $payload): string
    {
        $action = $payload['action'] ?? 'create';

        return is_string($action) && $action !== '' ? $action : 'create';
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function body(array $payload): array
    {
        if (isset($payload['body']) && is_array($payload['body'])) {
            return $payload['body'];
        }

        $copy = $payload;
        unset($copy['action'], $copy['id']);

        return $copy;
    }

    public static function id(array $payload): int
    {
        return (int) ($payload['id'] ?? 0);
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array{server_id: int, resource_type: string, result: array<string, mixed>}
     */
    public static function result(string $type, int $id, array $extra = []): array
    {
        return [
            'server_id' => $id,
            'resource_type' => $type,
            'result' => ['id' => $id, ...$extra],
        ];
    }
}
