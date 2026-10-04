<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Columns that hold provider credentials and were previously stored as plaintext.
     */
    private const SECRET_COLUMNS = ['access_token', 'webhook_verify_token'];

    public function up(): void
    {
        if (! Schema::hasTable('whats_app_configs')) {
            return;
        }

        // Read through the query builder so the model's `encrypted` cast is bypassed.
        // Until this migration runs, the existing values are still plaintext.
        $rows = DB::table('whats_app_configs')
            ->select('id', ...self::SECRET_COLUMNS)
            ->get();

        foreach ($rows as $row) {
            $updates = [];

            foreach (self::SECRET_COLUMNS as $column) {
                $value = $row->{$column};

                if ($value === null || $value === '') {
                    continue;
                }

                // Already encrypted (e.g. the migration is re-run) — leave it alone.
                try {
                    Crypt::decryptString($value);

                    continue;
                } catch (DecryptException) {
                    // Plaintext, fall through and encrypt it.
                }

                $updates[$column] = Crypt::encryptString($value);
            }

            if ($updates !== []) {
                DB::table('whats_app_configs')->where('id', $row->id)->update($updates);
            }
        }
    }

    public function down(): void
    {
        $rows = DB::table('whats_app_configs')
            ->select('id', ...self::SECRET_COLUMNS)
            ->get();

        foreach ($rows as $row) {
            $updates = [];

            foreach (self::SECRET_COLUMNS as $column) {
                $value = $row->{$column};

                if ($value === null || $value === '') {
                    continue;
                }

                try {
                    $updates[$column] = Crypt::decryptString($value);
                } catch (DecryptException) {
                    continue;
                }
            }

            if ($updates !== []) {
                DB::table('whats_app_configs')->where('id', $row->id)->update($updates);
            }
        }
    }
};
