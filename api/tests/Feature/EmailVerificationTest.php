<?php

namespace Tests\Feature;

use App\Mail\BusinessVerification;
use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\Country;
use App\Models\User;
use App\Services\Mail\BusinessVerificationMailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * The business email-verification flow end to end: the emailed token, its expiry and
 * single-use behaviour, and the resend endpoint's cooldown and rate limit.
 *
 * The token is issued through BusinessVerificationMailService rather than hand-written
 * into the column, because that service is the only thing that knows a token is stored
 * as a hash — a test that wrote a plaintext token itself would pass against a lookup
 * that had gone back to comparing plaintext.
 */
class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function business(array $attributes = []): Business
    {
        return Business::factory()->create(array_merge([
            'status' => 'pending_verification',
            'email_verified_at' => null,
        ], $attributes));
    }

    private function owner(Business $business): User
    {
        return User::factory()->create(['business_id' => $business->id]);
    }

    private function issueToken(Business $business): string
    {
        return app(BusinessVerificationMailService::class)->send($business);
    }

    /**
     * Reset this user's slice of the verification limiter.
     *
     * The limiter is keyed `verification:{id}` and the suite runs against the in-memory
     * array cache, which does not always come up empty for the next test. RefreshDatabase
     * restarts ids at 1, so without this the resend tests would share one budget and the
     * last of them would see a 429 that has nothing to do with what it asserts.
     */
    private function clearResendLimiter(User $user): void
    {
        RateLimiter::clear('verification:'.$user->id);
    }

    public function test_a_valid_token_activates_the_business_and_burns_the_link(): void
    {
        $business = $this->business();
        $token = $this->issueToken($business);

        $this->getJson('/api/verify-email?token='.$token)
            ->assertStatus(200)
            ->assertJsonPath('business.status', 'active');

        $business->refresh();

        $this->assertNotNull($business->email_verified_at);
        $this->assertSame('active', $business->status);
        // Single-use: both the token and its deadline go with the successful redemption.
        $this->assertNull($business->email_verification_token);
        $this->assertNull($business->email_verification_expires_at);
    }

    public function test_the_token_is_stored_as_a_hash_with_an_expiry_and_a_sent_stamp(): void
    {
        $business = $this->business();
        $token = $this->issueToken($business);

        $business->refresh();

        // The plaintext lives only in the URL; a leaked row cannot be turned back into a
        // working link.
        $this->assertNotSame($token, $business->email_verification_token);
        $this->assertSame(hash('sha256', $token), $business->email_verification_token);
        $this->assertNotNull($business->email_verification_sent_at);
        $this->assertNotNull($business->email_verification_expires_at);
        // The email promises 24 hours; the column has to agree. Measured from the sent
        // stamp rather than `now()` so the assertion does not race the wall clock, and
        // with a second of slack for the two separate now() calls that set them.
        $this->assertEqualsWithDelta(
            BusinessVerificationMailService::TOKEN_TTL_HOURS * 3600,
            $business->email_verification_sent_at->diffInSeconds($business->email_verification_expires_at),
            1
        );
    }

    public function test_an_expired_token_is_refused_without_activating_the_business(): void
    {
        $business = $this->business();
        $token = $this->issueToken($business);

        $business->forceFill(['email_verification_expires_at' => now()->subMinute()])->save();

        $this->getJson('/api/verify-email?token='.$token)->assertStatus(404);

        $business->refresh();
        $this->assertNull($business->email_verified_at);
        $this->assertSame('pending_verification', $business->status);
    }

    public function test_an_unknown_token_is_refused(): void
    {
        $this->business();

        $this->getJson('/api/verify-email?token='.str_repeat('a', 64))->assertStatus(404);
    }

    public function test_a_missing_token_is_rejected_as_a_bad_request(): void
    {
        $this->getJson('/api/verify-email')->assertStatus(400);
    }

    public function test_a_second_use_of_the_same_token_is_refused_and_changes_nothing(): void
    {
        $business = $this->business();
        $token = $this->issueToken($business);

        $this->getJson('/api/verify-email?token='.$token)->assertStatus(200);

        $business->refresh();
        $verifiedAt = $business->email_verified_at;

        // The token was nulled on the first call, so the replay cannot find it.
        $this->getJson('/api/verify-email?token='.$token)->assertStatus(404);

        $business->refresh();
        $this->assertEquals($verifiedAt, $business->email_verified_at);
    }

    public function test_a_browser_hitting_a_good_token_is_redirected_to_the_verification_page(): void
    {
        $business = $this->business();
        $token = $this->issueToken($business);

        // No Accept: application/json — this is the click from an inbox.
        $this->get('/api/verify-email?token='.$token)
            ->assertRedirect(config('app.frontend_url').'/verify-email?verified=1');
    }

    public function test_creating_a_business_reports_it_as_unverified_with_the_address_it_mailed(): void
    {
        $this->postJson('/api/users/signup', [
            'firstname' => 'Amina',
            'lastname' => 'Nabirye',
            'email' => 'amina@example.com',
            'phone' => '+256700000001',
            'password' => 'password123',
            'username' => '@amina',
        ])->assertStatus(201);

        $token = $this->postJson('/api/users/login', [
            'email' => 'amina@example.com',
            'password' => 'password123',
        ])->assertStatus(200)->json('data.token');

        $this->withHeaders(['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json'])
            ->postJson('/api/dashboard/business', [
                'name' => 'Amina Retail',
                'business_category_id' => BusinessCategory::factory()->create()->id,
                'country_id' => Country::factory()->create()->id,
                'email' => 'sales@amina-retail.test',
            ])
            ->assertStatus(201)
            ->assertJsonPath('email_verified', false)
            ->assertJsonPath('verification_email', 'sales@amina-retail.test');
    }

    public function test_me_reports_the_flags_the_banner_reads(): void
    {
        $business = $this->business();
        $user = $this->owner($business);
        Sanctum::actingAs($user);

        $this->getJson('/api/users/me')
            ->assertStatus(200)
            ->assertJsonPath('email_verified', false)
            ->assertJsonPath('business_status', 'pending_verification');

        $business->forceFill(['email_verified_at' => now(), 'status' => 'active'])->save();

        $this->getJson('/api/users/me')
            ->assertStatus(200)
            ->assertJsonPath('email_verified', true)
            ->assertJsonPath('business_status', 'active');
    }

    public function test_me_reports_unverified_when_the_caller_has_no_business(): void
    {
        // A platform admin, or an owner who has not finished onboarding. There is
        // nothing to verify, so the banner must not appear for them.
        Sanctum::actingAs(User::factory()->create(['business_id' => null]));

        $this->getJson('/api/users/me')
            ->assertStatus(200)
            ->assertJsonPath('email_verified', false);
    }

    public function test_resend_requires_authentication(): void
    {
        $this->postJson('/api/verify-email/resend')->assertStatus(401);
    }

    public function test_resend_is_a_no_op_when_the_email_is_already_verified(): void
    {
        Mail::fake();

        $business = $this->business(['email_verified_at' => now(), 'status' => 'active']);
        $user = $this->owner($business);
        $this->clearResendLimiter($user);
        Sanctum::actingAs($user);

        $this->postJson('/api/verify-email/resend')
            ->assertStatus(200)
            ->assertJsonPath('message', 'Email is already verified');

        Mail::assertNothingSent();
    }

    public function test_resend_within_the_cooldown_sends_no_second_email(): void
    {
        Mail::fake();

        $business = $this->business();
        $user = $this->owner($business);
        $this->clearResendLimiter($user);

        // The first send arms the cooldown.
        $this->issueToken($business);
        Mail::assertSent(BusinessVerification::class, 1);

        Sanctum::actingAs($user);

        // The response is a flat 200 either way, on purpose — it must not tell a caller
        // how recently the address was mailed.
        $this->postJson('/api/verify-email/resend')
            ->assertStatus(200)
            ->assertJsonPath('message', 'Verification email sent');

        Mail::assertSent(BusinessVerification::class, 1);
    }

    public function test_resend_after_the_cooldown_sends_again(): void
    {
        Mail::fake();

        $business = $this->business();
        $user = $this->owner($business);
        $this->clearResendLimiter($user);

        $this->issueToken($business);

        $business->forceFill([
            'email_verification_sent_at' => now()->subSeconds(
                BusinessVerificationMailService::RESEND_COOLDOWN_SECONDS + 5
            ),
        ])->save();

        Sanctum::actingAs($user);

        $this->postJson('/api/verify-email/resend')->assertStatus(200);

        Mail::assertSent(BusinessVerification::class, 2);
    }

    public function test_resend_is_rate_limited_per_user(): void
    {
        Mail::fake();

        $business = $this->business();
        $user = $this->owner($business);
        $this->clearResendLimiter($user);
        Sanctum::actingAs($user);

        // The named limiter allows six an hour; the seventh is refused. The per-business
        // cooldown means only the first of these actually mails, but every request still
        // counts against the limiter.
        for ($i = 0; $i < 6; $i++) {
            $this->postJson('/api/verify-email/resend')->assertStatus(200);
        }

        $this->postJson('/api/verify-email/resend')->assertStatus(429);
    }
}
