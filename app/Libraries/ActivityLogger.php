<?php

namespace App\Libraries;

use CodeIgniter\HTTP\IncomingRequest;

final class ActivityLogger
{
    public static function record(IncomingRequest $request, string $action, string $entity, $entityId = null, array $details = []): void
    {
        $type = (string) session('user_type');
        $actorType = in_array($type, ['employee', 'customer'], true) ? $type : 'system';
        $actorId = in_array($actorType, ['employee', 'customer'], true) ? (int) session('user_id') : null;

        db_connect()->table('ACTIVITY_LOG')->insert([
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'employee_id' => $actorType === 'employee' ? $actorId : null,
            'action' => $action,
            'entity_type' => $entity,
            'entity_id' => $entityId === null ? null : (string) $entityId,
            'details' => $details ? json_encode($details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null,
            'ip_address' => $request->getIPAddress(),
        ]);
    }
}
