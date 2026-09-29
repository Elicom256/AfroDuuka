<?php

use App\Events\WhatsAppNotificationEvents\BusinessRegistered;
use App\Jobs\ProcessWhatsAppNotificationJob;
use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\Country;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TestDispatch extends TestCase
{
    use RefreshDatabase;

    public function test_trace(): void
    {
        Queue::fake();

        // Check the raw listener list from the dispatcher
        $dispatcher = Event::getFacadeRoot();
        $ref = new ReflectionClass($dispatcher);
        $prop = $ref->getProperty('listeners');
        $prop->setAccessible(true);
        $allListeners = $prop->getValue($dispatcher);
        
        if (isset($allListeners[BusinessRegistered::class])) {
            $listeners = $allListeners[BusinessRegistered::class];
            echo "=== Raw listeners for BusinessRegistered: " . count($listeners) . " ===\n";
            foreach ($listeners as $i => $listener) {
                echo "  [$i] " . var_export($listener, true) . "\n";
            }
        }

        // Also check if there's a cached events file
        $cachedEvents = base_path('bootstrap/cache/events.php');
        if (file_exists($cachedEvents)) {
            echo "\n=== Cached events file exists ===\n";
            $cached = require $cachedEvents;
            if (isset($cached[BusinessRegistered::class])) {
                echo "Cached listeners: " . count($cached[BusinessRegistered::class]) . "\n";
                foreach ($cached[BusinessRegistered::class] as $i => $listener) {
                    echo "  [$i] " . var_export($listener, true) . "\n";
                }
            }
        } else {
            echo "\n=== No cached events file ===\n";
        }

        $this->assertTrue(true);
    }
}
