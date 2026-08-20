<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWhatsAppConfigRequest;
use App\Http\Requests\UpdateWhatsAppConfigRequest;
use App\Models\WhatsAppConfig;
use App\Models\WhatsAppMessageLog;
use App\Models\WhatsAppTemplate;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Http\Request;
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

    public function index()
    {
        $config = WhatsAppConfig::where('business_id', Auth::user()?->business_id)->first();

        return response()->json([
            'message' => 'WhatsApp config fetched',
            'data' => $config ?? [
                'business_phone' => '+256731794401',
                'provider' => 'demo',
                'is_active' => true,
                'status' => 'demo_mode',
            ],
        ]);
    }

    public function store(StoreWhatsAppConfigRequest $request)
    {
        $payload = $request->validated();
        $payload['business_id'] = $payload['business_id'] ?? Auth::user()?->business_id;

        $config = WhatsAppConfig::updateOrCreate(
            ['business_id' => $payload['business_id']],
            $payload
        );

        return response()->json([
            'message' => 'WhatsApp configuration saved',
            'data' => $config,
        ], 201);
    }

    public function update(UpdateWhatsAppConfigRequest $request, WhatsAppConfig $whatsAppConfig)
    {
        $whatsAppConfig->update($request->validated());

        return response()->json([
            'message' => 'WhatsApp configuration updated',
            'data' => $whatsAppConfig,
        ]);
    }

    public function testMessage(Request $request)
    {
        $recipient = $request->input('recipient', '+256731794401');
        $message = $request->input('message', 'Demo WhatsApp message from DuukaFlow.');

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
