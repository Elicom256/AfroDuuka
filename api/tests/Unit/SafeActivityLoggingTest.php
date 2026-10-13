<?php

namespace Tests\Unit;

use App\Models\ActivityLog;
use App\Services\ActivityLogService;
use App\Support\Activity\SafeActivityLogger;
use RuntimeException;
use Spatie\Activitylog\ActivityLogger;
use Tests\TestCase;

/**
 * An audit write is evidence, not the business action. If it fails it must be reported
 * and dropped, never surfaced to the operator as a failed sale, expense or payment.
 *
 * The two write paths are covered independently: the model trait goes through Spatie's
 * ActivityLogger (rebound to SafeActivityLogger), while controllers call
 * ActivityLogService::activity() directly.
 */
class SafeActivityLoggingTest extends TestCase
{
    public function test_the_activity_logger_is_bound_to_the_failure_tolerant_subclass(): void
    {
        $this->assertInstanceOf(SafeActivityLogger::class, app(ActivityLogger::class));
    }

    public function test_the_logger_swallows_a_failed_activity_write(): void
    {
        config(['activitylog.activity_model' => ExplodingActivity::class]);

        $result = app(ActivityLogger::class)
            ->useLog('test')
            ->event('created')
            ->log('this write cannot succeed');

        $this->assertNull($result, 'A failed audit write must not surface to the caller.');
    }

    public function test_the_service_swallows_a_failed_activity_write(): void
    {
        $thrown = false;

        ActivityLog::creating(function () use (&$thrown) {
            if (! $thrown) {
                $thrown = true;

                throw new RuntimeException('audit backend is down');
            }
        });

        $result = (new ActivityLogService)->activity('test', 'this write cannot succeed');

        $this->assertNull($result, 'A failed audit write must not surface to the caller.');
    }
}

/**
 * A stand-in activity model whose persistence always fails, used to prove the logger
 * does not let that failure escape. It never reaches the database.
 */
class ExplodingActivity extends ActivityLog
{
    public function save(array $options = []): bool
    {
        throw new RuntimeException('audit backend is down');
    }
}
