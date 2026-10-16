<?php

namespace Tests\Feature\Sync;

use App\Models\Business;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SyncOperationConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_concurrent_claims_for_the_same_business_operation_uuid_have_one_winner(): void
    {
        if (! function_exists('pcntl_fork')) {
            $this->markTestSkipped('pcntl is required for the concurrent claim test.');
        }

        $business = Business::factory()->create();
        $role = Role::factory()->create([
            'business_id' => $business->id,
            'name' => 'Executive',
        ]);
        $user = User::factory()->create([
            'business_id' => $business->id,
            'role_id' => $role->id,
        ]);
        $operationId = fake()->uuid();
        $attributes = [
            'business_id' => $business->id,
            'user_id' => $user->id,
            'operation_id' => $operationId,
            'type' => 'todo',
            'status' => 'processing',
            'attempts' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $connection = DB::connection();
        $connection->commit();
        DB::purge('pgsql');

        $children = [];
        $channels = [];

        for ($index = 0; $index < 2; $index++) {
            $channel = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
            $this->assertNotFalse($channel);

            $pid = pcntl_fork();
            $this->assertNotSame(-1, $pid, 'Could not fork a claim worker.');

            if ($pid === 0) {
                fclose($channel[0]);
                fread($channel[1], 2);
                DB::purge('pgsql');

                try {
                    $inserted = DB::table('sync_operations')->insertOrIgnore($attributes);
                    fclose($channel[1]);
                    exit($inserted === 1 ? 0 : 1);
                } catch (\Throwable) {
                    fclose($channel[1]);
                    exit(2);
                }
            }

            fclose($channel[1]);
            $children[] = $pid;
            $channels[] = $channel[0];
        }

        foreach ($channels as $channel) {
            fwrite($channel, 'go');
            fclose($channel);
        }

        $insertWinners = 0;
        foreach ($children as $pid) {
            pcntl_waitpid($pid, $status);
            $exitCode = pcntl_wexitstatus($status);
            $this->assertContains($exitCode, [0, 1], 'A claim worker failed unexpectedly.');
            $insertWinners += $exitCode === 0 ? 1 : 0;
        }

        $this->assertSame(1, $insertWinners);
        $this->assertSame(1, DB::table('sync_operations')
            ->where('business_id', $business->id)
            ->where('operation_id', $operationId)
            ->count());

        DB::table('sync_operations')
            ->where('business_id', $business->id)
            ->where('operation_id', $operationId)
            ->delete();
        DB::commit();
    }
}
