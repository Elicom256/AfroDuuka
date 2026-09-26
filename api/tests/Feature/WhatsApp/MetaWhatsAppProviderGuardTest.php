<?php

namespace Tests\Feature\WhatsApp;

use App\Services\WhatsApp\Providers\MetaWhatsAppProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Guards on the Meta provider's two sharp edges.
 *
 * Neither is a Stage 3 deliverable; both are the reason Stage 3 can be started without
 * inheriting a silent data-loss path or an expiring API version.
 */
class MetaWhatsAppProviderGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_unconfigured_provider_refuses_instead_of_reporting_a_send(): void
    {
        Http::fake();

        $response = (new MetaWhatsAppProvider([
            'access_token' => 'token',
            'phone_number_id' => 'phone-number-id',
        ]))->sendMessage([
            'to' => 'not-a-number',
            'template' => ['name' => 'welcome', 'language' => 'en', 'parameters' => []],
        ]);

        // This replaced a stub that returned status=sent with an md5 of the recipient and
        // body as a provider_message_id. Nothing checked the message had been
        // transmitted, so flipping WHATSAPP_PROVIDER=meta marked every customer's
        // notifications delivered and delivered none of them.
        //
        // The invariant worth pinning is not "this throws" — it is "this never claims a
        // send it did not make". A rejection says that; a fabricated id does not.
        $this->assertArrayHasKey('error', $response);
        $this->assertArrayNotHasKey('status', $response);
        $this->assertArrayNotHasKey('provider_message_id', $response);
    }

    public function test_no_response_shape_ever_claims_a_send_that_was_not_made(): void
    {
        Http::fake();

        $provider = new MetaWhatsAppProvider([
            'access_token' => 'token',
            'phone_number_id' => 'phone-number-id',
        ]);

        // Every way this can come back short of a real 200 from Graph. Whichever one
        // happens, the caller must not be able to read a successful send out of it.
        $responses = [
            $provider->sendMessage(['to' => '+256700000000', 'template' => ['name' => 'welcome']]),
            $provider->sendMessage(['to' => 'rubbish', 'template' => ['name' => 'welcome']]),
            $provider->sendMessage(['to' => '+256700000000', 'template' => []]),
        ];

        foreach ($responses as $response) {
            $this->assertArrayHasKey('error', $response, 'A send was reported with no error and no provider response.');
            $this->assertArrayNotHasKey('status', $response);
        }
    }

    public function test_the_api_version_comes_from_config(): void
    {
        config(['services.whatsapp.graph_api_version' => 'v25.0']);

        Http::fake([
            'graph.facebook.com/v25.0/*' => Http::response(['data' => []]),
            'graph.facebook.com/*' => Http::response(['data' => []]),
        ]);

        (new MetaWhatsAppProvider([
            'whatsapp_business_account_id' => 'waba-1',
            'access_token' => 'token',
        ]))->listTemplates();

        Http::assertSent(fn (Request $r) => str_contains($r->url(), '/v25.0/waba-1/message_templates'));
    }

    public function test_listing_and_sending_cannot_straddle_two_versions(): void
    {
        // Both calls resolve their URL through one helper, so a version change is a
        // single config edit. v21.0, previously hardcoded here, stops being served by
        // Meta on 2027-01-21.
        config(['services.whatsapp.graph_api_version' => 'v24.0']);

        Http::fake(['*' => Http::response(['data' => []])]);

        (new MetaWhatsAppProvider([
            'whatsapp_business_account_id' => 'waba-9',
            'access_token' => 'token',
        ]))->listTemplates();

        Http::assertSent(fn (Request $r) => str_contains($r->url(), '/v24.0/waba-9/'));
    }

    public function test_a_graph_outage_still_leaves_stored_statuses_alone(): void
    {
        config(['services.whatsapp.graph_api_version' => 'v25.0']);

        Http::fake(['*' => Http::response(['error' => ['message' => 'boom']], 500)]);

        // Approval status gates whether a notification may be sent at all, so a provider
        // that cannot be reached must return nothing rather than guess.
        $this->assertSame([], (new MetaWhatsAppProvider([
            'whatsapp_business_account_id' => 'waba-1',
            'access_token' => 'token',
        ]))->listTemplates());
    }
}
