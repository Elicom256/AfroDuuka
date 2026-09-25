<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWhatsAppConfigRequest;
use App\Http\Requests\TestWhatsAppMessageRequest;
use App\Http\Requests\UpdateWhatsAppConfigRequest;
use App\Models\WhatsAppConfig;
use App\Models\WhatsAppMessageLog;
use App\Models\WhatsAppTemplate;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Support\Facades\Auth;

/**
 * Business-facing WhatsApp settings and mock message delivery endpoints.
 * This is intentionally demo-mode until a paid provider account is later enabled.
 */
class WhatsAppConfigController extends Controller
{
    public function __construct(private readonly WhatsAppService $whatsAppService)
    {
    }

    /**
     * Explicit response shape. Returning the model leaked access_token and
     * webhook_verify_token to any authenticated user, so the secrets are replaced
     * with a mask and never leave the server.
     */
    private function present(WhatsAppConfig $config): array
    {
        return [
            'id' => $config->id,
            'business_id' => $config->business_id,
            'provider' => $config->provider,
            'business_phone' => $config->business_phone,
            'phone_number_id' => $config->phone_number_id,
            'is_active' => $config->is_active,
            'message_template' => $config->message_template,
            'welcome_message' => $config->welcome_message,
            'access_token' => $config->access_token ? WhatsAppConfig::SECRET_MASK : null,
            'webhook_verify_token' => $config->webhook_verify_token ? WhatsAppConfig::SECRET_MASK : null,
            'last_webhook_at' => $config->last_webhook_at?->toDateTimeString(),
        ];
    }

    /**
     * A masked secret coming back from the settings form means "unchanged", not
     * "set the token to ********". Without this, simply saving the settings page
     * would overwrite the stored credential.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function withoutMaskedSecrets(array $validated): array
    {
        foreach (['access_token', 'webhook_verify_token'] as $field) {
            if (($validated[$field] ?? null) === WhatsAppConfig::SECRET_MASK) {
                unset($validated[$field]);
            }
        }

        return $validated;
    }

    public function index()
    {
        $config = WhatsAppConfig::where('business_id', Auth::user()?->business_id)->first();

        return response()->json([
            'message' => 'WhatsApp config fetched',
            'data' => $config ? $this->present($config) : [
                'business_phone' => '+256731794401',
                'provider' => 'demo',
                'is_active' => true,
                'status' => 'demo_mode',
            ],
        ]);
    }

    public function store(StoreWhatsAppConfigRequest $request)
    {
        // The business is always the authenticated user's own. Accepting a client-supplied
        // business_id let any authenticated tenant create a config for another business.
        $payload = $this->withoutMaskedSecrets($request->validated());
        $payload['business_id'] = Auth::user()?->business_id;

        $config = WhatsAppConfig::updateOrCreate(
            ['business_id' => $payload['business_id']],
            $payload
        );

        return response()->json([
            'message' => 'WhatsApp configuration saved',
            'data' => $this->present($config),
        ], 201);
    }

    public function update(UpdateWhatsAppConfigRequest $request, WhatsAppConfig $whatsAppConfig)
    {
        // Without this check any authenticated tenant could PATCH another business's
        // WhatsApp config, including its provider credentials.
        if ((int) $whatsAppConfig->business_id !== (int) Auth::user()?->business_id) {
            abort(403, 'You are not allowed to update this WhatsApp configuration.');
        }

        $whatsAppConfig->update($this->withoutMaskedSecrets($request->validated()));

        return response()->json([
            'message' => 'WhatsApp configuration updated',
            'data' => $this->present($whatsAppConfig->refresh()),
        ]);
    }

    public function testMessage(TestWhatsAppMessageRequest $request)
    {
        $recipient = $request->validated('recipient');
        $message = $request->validated('message');

        // Queue the outbound demo notification so the system behaves like a production send pipeline,
        // even though the underlying API is not paid for in this environment.
        $response = $this->whatsAppService->queueDemoMessage($recipient, $message);

        return response()->json([
            'message' => 'Demo WhatsApp test queued',
            'data' => $response,
        ]);
    }

    public function templates()
    {
        $templates = WhatsAppTemplate::where('business_id', Auth::user()?->business_id)->get();

        return response()->json([
            'message' => 'WhatsApp templates fetched',
            'data' => $templates->isNotEmpty() ? $templates : $this->whatsAppService->ensureTemplatesForBusiness(),
        ]);
    }

    public function logs()
    {
        $logs = WhatsAppMessageLog::where('business_id', Auth::user()?->business_id)
            ->latest('created_at')
            ->limit(20)
            ->get();

        return response()->json([
            'message' => 'WhatsApp logs fetched',
            'data' => $logs,
        ]);
    }
}
