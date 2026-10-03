<?php

return [
    'disk' => env('BACKUP_DISK', 'backups'),
    'prefix' => trim(env('BACKUP_PREFIX', 'database'), '/'),
    'retention_days' => (int) env('BACKUP_RETENTION_DAYS', 30),
];