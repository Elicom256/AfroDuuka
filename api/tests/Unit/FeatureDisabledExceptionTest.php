<?php

namespace Tests\Unit;

use App\Exceptions\FeatureDisabledException;
use Tests\TestCase;

class FeatureDisabledExceptionTest extends TestCase
{
    public function test_it_is_a_403_that_carries_the_feature(): void
    {
        $exception = new FeatureDisabledException('reports', 'Reports is not enabled.');

        $this->assertSame(403, $exception->getStatusCode());
        $this->assertSame('Reports is not enabled.', $exception->getMessage());
        $this->assertSame('reports', $exception->feature);
    }
}
