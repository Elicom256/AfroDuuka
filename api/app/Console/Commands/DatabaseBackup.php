<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class DatabaseBackup extends Command
{
    protected $signature = 'duukaflow:database:backup
                            {path? : Backup file path, or restore source with --restore}
                            {--restore= : Restore a custom-format PostgreSQL dump}
                            {--force : Confirm a destructive restore}';

    protected $description = 'Create or restore a PostgreSQL database backup';

    public function handle(): int
    {
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
        $path ??= storage_path('app/backups/duukaflow-' . now()->format('Ymd-His') . '.dump');
        $directory = dirname($path);

        if (! is_dir($directory) && ! mkdir($directory, 0750, true) && ! is_dir($directory)) {
            $this->error("Could not create backup directory: {$directory}");

            return self::FAILURE;
        }

        $process = new Process([
            'pg_dump',
            '--format=custom',
            '--no-owner',
            '--file=' . $path,
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

        $this->info("Database backup created: {$path}");

        return self::SUCCESS;
    }

    private function restore(string $path): int
    {
        if (! $this->option('force')) {
            $this->error('Restore is destructive. Re-run with --force after verifying the dump.');

            return self::FAILURE;
        }

        if (! is_file($path)) {
            $this->error("Backup file not found: {$path}");

            return self::FAILURE;
        }

        $process = new Process([
            'pg_restore',
            '--clean',
            '--if-exists',
            '--no-owner',
            '--dbname=' . config('database.connections.pgsql.database'),
            ...$this->connectionArguments(),
            $path,
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
