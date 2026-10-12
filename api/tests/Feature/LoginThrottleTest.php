<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Covers LoginThrottle, which the route-level throttle:auth limiter does not replace.
 * That limiter counts by IP, so it only bounds how fast one address can guess; these
 * tests pin the behaviour that a rotating attacker is stopped by.
 *
 * Each test uses its own address because the rate limiter's cache store is the
 * in-memory `array` driver under test, which lives for the whole process rather than
 * per test — a shared address would carry one test's failures into the next.
 */
class LoginThrottleTest extends TestCase
{
    use RefreshDatabase;

    private string $email = '';

    private string $ip = '';

    protected function setUp(): void
    {
        parent::setUp();

        // Both limiters are per-IP under the test environment, so each test gets its
        // own address. Without this the route-level throttle:auth budget (10/min) runs
        // out across the suite and answers 429, which would mask the account lockout
        // this class is actually about.
        $seed = RateLimiter::attempts('login-throttle-test:seed');
        RateLimiter::hit('login-throttle-test:seed');

        $this->email = sprintf('amina-%d@example.com', $seed);
        $this->ip = sprintf('198.51.100.%d', $seed % 250);

        $this->withServerVariables(['REMOTE_ADDR' => $this->ip]);
    }

    private function user(): User
    {
        $business = Business::factory()->create();
        $role = Role::factory()->create(['business_id' => $business->id, 'name' => 'Executive']);

        return User::factory()->create([
            'business_id' => $business->id,
            'role_id' => $role->id,
            'email' => $this->email,
            'password' => bcrypt('correct-horse'),
        ]);
    }

    private function attempt(string $password, ?string $email = null, ?string $ip = null): TestResponse
    {
        $this->withServerVariables(['REMOTE_ADDR' => $ip ?? $this->ip]);

        return $this->postJson('/api/users/login', [
            'email' => $email ?? $this->email,
            'password' => $password,
        ]);
    }

    public function test_wrong_passwords_keep_answering_401_until_the_account_locks(): void
    {
        $this->user();

        // MAX_ATTEMPTS failures are tolerated; the next one is refused. Locking on the
        // fifth attempt itself would reject a user who had merely mistyped five times.
        for ($i = 0; $i < 5; $i++) {
            $this->attempt('wrong')->assertStatus(401);
        }

        $this->attempt('wrong')->assertStatus(429)->assertHeader('Retry-After');
    }

    public function test_a_locked_account_cannot_be_probed_even_with_the_right_password(): void
    {
        $user = $this->user();

        for ($i = 0; $i < 5; $i++) {
            $this->attempt('wrong');
        }

        $this->attempt('correct-horse')->assertStatus(429);

        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_the_lockout_survives_a_change_of_source_address(): void
    {
        $this->user();

        for ($i = 0; $i < 5; $i++) {
            $this->attempt('wrong');
        }

        // The whole reason the account key exists: a new IP is a fresh budget for the
        // route limiter, but the account is still locked.
        $this->attempt('correct-horse', null, '203.0.113.7')->assertStatus(429);
    }

    public function test_a_lockout_does_not_confirm_whether_the_account_exists(): void
    {
        $this->user();

        for ($i = 0; $i < 5; $i++) {
            $this->attempt('wrong');
        }

        $locked = $this->attempt('x');

        // A different address for the unknown account, so this reaches the same account
        // lockout rather than exhausting the route limiter on top of it. Both refusals
        // would be 429 anyway, but the route limiter's own message would otherwise make
        // the two distinguishable — which is the property under test.
        $unknownEmail = 'nobody-'.uniqid().'@example.com';
        $otherIp = '203.0.113.42';

        for ($i = 0; $i < 5; $i++) {
            $this->attempt('wrong', $unknownEmail, $otherIp);
        }

        $unknown = $this->attempt('wrong', $unknownEmail, $otherIp);

        // An address that was never registered burns through its own budget to reach
        // the same refusal, so the 429 is not evidence of an account.
        $this->assertSame(429, $locked->status());
        $this->assertSame(429, $unknown->status());
        $this->assertSame($locked->json('message'), $unknown->json('message'));
    }

    public function test_correct_credentials_clear_the_counter(): void
    {
        $this->user();

        for ($i = 0; $i < 4; $i++) {
            $this->attempt('wrong')->assertStatus(401);
        }

        // Succeeds on the fifth, which would otherwise have consumed the last attempt.
        $this->attempt('correct-horse')->assertStatus(200);

        // The four failures were the user's own typos and must not linger: without the
        // reset, the next single typo locks them out immediately.
        for ($i = 0; $i < 4; $i++) {
            $this->attempt('wrong')->assertStatus(401);
        }

        $this->attempt('correct-horse')->assertStatus(200);
    }

    public function test_the_identifier_is_matched_case_insensitively(): void
    {
        $this->user();

        for ($i = 0; $i < 5; $i++) {
            $this->attempt('wrong');
        }

        // Otherwise "Amina@Example.com" would be a fresh budget for the same account.
        $this->attempt('correct-horse', strtoupper($this->email))->assertStatus(429);
    }
}
