<?php

namespace Tests\Unit;

use App\Support\Features\FeatureFlags;
use Tests\TestCase;

class FeatureFlagsTest extends TestCase
{
    private FeatureFlags $flags;

    protected function setUp(): void
    {
        parent::setUp();

        $this->flags = new FeatureFlags;
    }

    public function test_an_unknown_feature_is_off(): void
    {
        $this->assertFalse($this->flags->enabled('does_not_exist'));
    }

    public function test_without_a_business_the_shipped_default_decides(): void
    {
        // No authenticated user, so BusinessContext::businessId() is null and neither
        // the settings row nor the plan is consulted — config/features.php decides.
        $this->assertTrue($this->flags->enabled('customers'));
        $this->assertTrue($this->flags->enabled('suppliers'));
        $this->assertFalse($this->flags->enabled('reports'));
        $this->assertFalse($this->flags->enabled('promotions'));
        $this->assertFalse($this->flags->enabled('attendance'));
    }

    public function test_label_falls_back_to_the_feature_key(): void
    {
        $this->assertSame('Reports', $this->flags->label('reports'));
        $this->assertSame('Something', $this->flags->label('something'));
    }

    public function test_message_names_the_feature(): void
    {
        $message = $this->flags->message('reports');

        $this->assertStringContainsString('Reports', $message);
        $this->assertStringContainsString('not enabled', $message);
    }
}
