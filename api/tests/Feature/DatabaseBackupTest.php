<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DatabaseBackupTest extends TestCase
{
    use RefreshDatabase;

    public function test_backup_uploads_to_configured_disk_and_prunes_expired_archives(): void
    {
        config([
            'backup.disk' => 'spaces',
            'backup.prefix' => 'database',
            'backup.retention_days' => 30,
        ]);
        Storage::fake('spaces');

        $expiredPath = 'database/duukaflow-expired.dump';
        Storage::disk('spaces')->put($expiredPath, 'expired archive');
        touch(Storage::disk('spaces')->path($expiredPath), now()->subDays(31)->timestamp);

        $archivePath = tempnam(sys_get_temp_dir(), 'duukaflow-test-');
        file_put_contents($archivePath, 'custom-format archive fixture');

        try {
            $this->artisan('duukaflow:database:backup', [
                '--archive' => $archivePath,
            ])->assertExitCode(0);
        } finally {
            unlink($archivePath);
        }

        $files = Storage::disk('spaces')->allFiles('database');
        $this->assertCount(1, $files);
        $this->assertStringStartsWith('database/duukaflow-', $files[0]);
        $this->assertNotSame(0, Storage::disk('spaces')->size($files[0]));
        $this->assertFalse(Storage::disk('spaces')->exists($expiredPath));
    }

    public function test_production_rejects_local_only_backup_targets(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        config(['backup.disk' => 'backups']);

        $this->artisan('duukaflow:database:backup', [
            'path' => storage_path('app/backups/manual.dump'),
        ])->assertExitCode(1);
    }

    public function test_production_requires_dump_from_postgres_container(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        config(['backup.disk' => 'spaces']);
        Storage::fake('spaces');

        $this->artisan('duukaflow:database:backup')->assertExitCode(1);
    }
}
