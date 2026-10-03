<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Throwable;

class DatabaseBackup extends Command
{
    protected $signature = 'duukaflow:database:backup
                            {path? : Backup file path, or restore source with --restore}
                            {--restore= : Restore a custom-format PostgreSQL dump}
                            {--download= : Write a stored backup archive to STDOUT}
                            {--archive= : Upload an existing custom-format dump file}
                            {--stdin : Read a custom-format dump from STDIN}
                            {--force : Confirm a destructive restore}';

    protected $description = 'Create or restore a PostgreSQL database backup';

    private const ARCHIVE_PREFIX = 'duukaflow-';

    public function handle(): int
    {
        if ($downloadPath = $this->option('download')) {
            return $this->download($downloadPath);
        }

        if (config('database.default') !== 'pgsql') {
            $this->error('Database backup currently supports only the pgsql connection.');

            return self::FAILURE;
        }

        if ($restorePath = $this->option('restore')) {
            return $this->restore($restorePath);
        }

        return $this->backup($this->argument('path'));
    }

    private function backup(?string $path): int
    {
        $diskName = (string) config('backup.disk');
        if (app()->environment('production') && (
            $diskName !== 'spaces'
            || $path !== null
            || (! $this->option('stdin') && ! $this->option('archive'))
        )) {
            $this->error('Production backups must stream a PostgreSQL-container dump to the DigitalOcean Spaces disk.');

            return self::FAILURE;
        }

        if ($this->option('stdin') && $this->option('archive')) {
            $this->error('Choose either --stdin or --archive, not both.');

            return self::FAILURE;
        }

        $archiveOption = $this->option('archive');
        if ($archiveOption !== null && ! is_file($archiveOption)) {
            $this->error("Backup archive not found: {$archiveOption}");

            return self::FAILURE;
        }

        $temporaryPath = null;
        try {
            $sourcePath = $archiveOption;
            if ($sourcePath === null) {
                $temporaryPath = tempnam(sys_get_temp_dir(), self::ARCHIVE_PREFIX);
                if ($temporaryPath === false) {
                    $this->error('Could not create a temporary backup file.');

                    return self::FAILURE;
                }

                if ($this->option('stdin')) {
                    $source = fopen('php://stdin', 'rb');
                    $destination = fopen($temporaryPath, 'wb');
                    if ($source === false || $destination === false) {
                        if (is_resource($source)) {
                            fclose($source);
                        }
                        if (is_resource($destination)) {
                            fclose($destination);
                        }
                        $this->error('Could not read the backup archive from STDIN.');

                        return self::FAILURE;
                    }

                    try {
                        $copiedBytes = stream_copy_to_stream($source, $destination);
                    } finally {
                        fclose($source);
                        fclose($destination);
                    }

                    if ($copiedBytes === false || $copiedBytes === 0) {
                        $this->error('The backup archive received on STDIN is empty.');

                        return self::FAILURE;
                    }
                } else {
                    $process = new Process([
                        'pg_dump',
                        '--format=custom',
                        '--no-owner',
                        '--file=' . $temporaryPath,
                        ...$this->connectionArguments(),
                        '--dbname=' . config('database.connections.pgsql.database'),
                    ]);
                    $process->setEnv(['PGPASSWORD' => (string) config('database.connections.pgsql.password')]);
                    $process->setTimeout(null);
                    $process->run();

                    if (! $process->isSuccessful()) {
                        $this->error('Database backup failed.');
                        $this->line(trim($process->getErrorOutput()));

                        return self::FAILURE;
                    }
                }

                $sourcePath = $temporaryPath;
            }

            if ($path !== null) {
                $directory = dirname($path);
                if (! is_dir($directory) && ! mkdir($directory, 0750, true) && ! is_dir($directory)) {
                    $this->error("Could not create backup directory: {$directory}");

                    return self::FAILURE;
                }

                if (! copy($sourcePath, $path)) {
                    $this->error("Could not write backup file: {$path}");

                    return self::FAILURE;
                }

                $this->info("Database backup created: {$path}");

                return self::SUCCESS;
            }

            $disk = Storage::disk($diskName);
            $filename = self::ARCHIVE_PREFIX.now()->format('Ymd-His').'-'.bin2hex(random_bytes(4)).'.dump';
            $prefix = trim((string) config('backup.prefix'), '/');
            $archivePath = $prefix === '' ? $filename : $prefix.'/'.$filename;
            $stream = fopen($sourcePath, 'rb');

            try {
                if ($stream === false || ! $disk->put($archivePath, $stream)) {
                    $this->error('Could not store the database backup archive.');

                    return self::FAILURE;
                }
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            $this->pruneOldBackups($disk, $prefix);
            $this->info("Database backup created on [{$diskName}]: {$archivePath}");

            return self::SUCCESS;
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Database backup could not be stored. Check the configured backup disk and credentials.');

            return self::FAILURE;
        } finally {
            if ($temporaryPath !== null && is_file($temporaryPath)) {
                unlink($temporaryPath);
            }
        }
    }

    private function download(string $path): int
    {
        try {
            $stream = Storage::disk((string) config('backup.disk'))->readStream($path);
            if ($stream === false) {
                $this->error("Backup archive not found: {$path}");

                return self::FAILURE;
            }

            try {
                if (stream_copy_to_stream($stream, fopen('php://stdout', 'wb')) === false) {
                    $this->error('Could not write the backup archive to STDOUT.');

                    return self::FAILURE;
                }
            } finally {
                fclose($stream);
            }

            return self::SUCCESS;
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Could not download the backup archive from the configured disk.');

            return self::FAILURE;
        }
    }

    private function pruneOldBackups($disk, string $prefix): void
    {
        $retentionDays = (int) config('backup.retention_days', 30);
        if ($retentionDays <= 0) {
            return;
        }

        $cutoff = now()->subDays($retentionDays)->timestamp;
        foreach ($disk->listContents($prefix, true) as $file) {
            if (
                $file->isFile()
                && str_starts_with(basename($file->path()), self::ARCHIVE_PREFIX)
                && $file->lastModified() < $cutoff
            ) {
                $disk->delete($file->path());
            }
        }

    }

    private function restore(string $path): int
    {
        if (! $this->option('force')) {
            $this->error('Restore is destructive. Re-run with --force after verifying the dump.');

            return self::FAILURE;
        }

        $restorePath = $path;
        $temporaryPath = null;

        if (! is_file($restorePath)) {
            $temporaryPath = tempnam(sys_get_temp_dir(), self::ARCHIVE_PREFIX);
            if ($temporaryPath === false) {
                $this->error('Could not create a temporary restore file.');

                return self::FAILURE;
            }
        }

        try {
            if ($temporaryPath !== null) {
                $source = Storage::disk((string) config('backup.disk'))->readStream($path);
                if ($source === false) {
                    $this->error("Backup file not found on the configured backup disk: {$path}");

                    return self::FAILURE;
                }

                $destination = fopen($temporaryPath, 'wb');
                if ($destination === false) {
                    fclose($source);
                    $this->error('Could not open the temporary restore file.');

                    return self::FAILURE;
                }

                try {
                    if (stream_copy_to_stream($source, $destination) === false) {
                        $this->error('Could not download the backup archive for restore.');

                        return self::FAILURE;
                    }
                } finally {
                    fclose($source);
                    fclose($destination);
                }

                $restorePath = $temporaryPath;
            }

            $process = new Process([
                'pg_restore',
                '--clean',
                '--if-exists',
                '--no-owner',
                '--dbname=' . config('database.connections.pgsql.database'),
                ...$this->connectionArguments(),
                $restorePath,
            ]);
            $process->setEnv(['PGPASSWORD' => (string) config('database.connections.pgsql.password')]);
            $process->setTimeout(null);
            $process->run();

            if (! $process->isSuccessful()) {
                $this->error('Database restore failed.');
                $this->line(trim($process->getErrorOutput()));

                return self::FAILURE;
            }

            $this->info("Database restored from: {$path}");

            return self::SUCCESS;
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Could not restore the database backup. Check the archive and database connection.');

            return self::FAILURE;
        } finally {
            if ($temporaryPath !== null && is_file($temporaryPath)) {
                unlink($temporaryPath);
            }
        }
    }

    private function connectionArguments(): array
    {
        $connection = config('database.connections.pgsql');

        return [
            '--host=' . $connection['host'],
            '--port=' . $connection['port'],
            '--username=' . $connection['username'],
        ];
    }
}
