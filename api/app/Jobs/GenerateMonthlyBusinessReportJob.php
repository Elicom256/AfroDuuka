<?php

namespace App\Jobs;

use App\Services\WhatsApp\WhatsAppNotificationService;
use App\Services\WhatsApp\WhatsAppProviderFactory;
use App\Services\WhatsApp\WhatsAppTemplateService;
use App\Models\WhatsAppConfig;
use App\Models\WhatsAppTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class GenerateMonthlyBusinessReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $notificationService = new WhatsAppNotificationService();
        $templateService = new WhatsAppTemplateService();

        $config = WhatsAppConfig::where('provider', 'demo')
            ->orWhere('is_active', true)
            ->first();

        if (! $config) {
            Log::info('No active WhatsApp config found for monthly report job');
            return;
        }

        $provider = WhatsAppProviderFactory::create([
            'provider' => $config->provider,
            'business_phone' => $config->business_phone,
            'access_token' => $config->access_token,
            'phone_number_id' => $config->phone_number_id,
        ]);

        // Determine the previous completed month
        $now = now();
        $firstOfCurrentMonth = $now->startOfMonth;
        $lastDayOfPreviousMonth = $firstOfCurrentMonth->subDay();
        $firstDayOfPreviousMonth = $lastDayOfPreviousMonth->startOfMonth;
        $monthName = $firstDayOfPreviousMonth->monthName;

        // Calculate metrics from existing data (simplified - would use analytics services)
        $businesses = \App\Models\Business::where('is_active', true)->get();

        foreach ($businesses as $business) {
            $dedupeKey = 'notification:report:monthly:business-' . $business->id . '-' . $firstDayOfPreviousMonth->format('Y-m');

            $existingLog = WhatsAppMessageLog::where('business_id', $business->id)
                ->where('dedupe_key', $dedupeKey)
                ->where('status', 'sent')
                ->first();

            if ($existingLog) {
                Log::info('Skipping duplicate monthly report', [
                    'business_id' => $business->id,
                    'dedupe_key' => $dedupeKey,
                ]);
                continue;
            }

            // In a real implementation, would query actual sales/purchase data
            // Here we use placeholder values based on existing analytics infrastructure
            $totalSales = 0; // Would: $business->sales()->sum('total') in relevant period
            $totalPurchases = 0; // Would: $business->purchases()->sum('total') in relevant period
            $profit = 0; // Would: calculate from sales and purchases
            $numberOfSales = 0; // Would: $business->sales()->count() in relevant period
            $numberOfPurchases = 0; // Would: $business->purchases()->count() in relevant period

            $payload = [
                'business_id' => $business->id,
                'branch_id' => null,
                'type' => 'report.monthly',
                'template_key' => 'report.monthly',
                'recipient_phone' => $business->phone ?? '+256731794401',
                'template_data' => [
                    'business_name' => $business->name,
                    'month_name' => $monthName,
                    'total_sales' => $totalSales,
                    'total_purchases' => $totalPurchases,
                    'profit' => $profit,
                ],
            ];

            $normalized = $notificationService->buildPayload($payload);

            $provider->sendMessage([
                'to' => $normalized['recipient_phone'],
                'message' => (string) ($templateService->render(
                    $config->message_template ?? 'Hello {{business_name}}, your monthly report for {{month_name}}: sales {{total_sales}}, profit {{profit}}.',
                    $normalized['template_data'] ?? []
                )),
                'template_key' => $normalized['template_key'],
                'business_id' => $business->id,
            ]);

            WhatsAppMessageLog::create([
                'business_id' => $business->id,
                'template_id' => null,
                'recipient' => $normalized['recipient_phone'],
                'channel' => 'whatsapp',
                'message_body' => $normalized['template_data'] ?? [],
                'status' => 'sent',
                'provider_response' => ['provider' => $provider->getName()],
                'sent_at' => now(),
                'dedupe_key' => $normalized['dedupe_key'],
            ]);

            Log::info('Monthly business report sent', [
                'business_id' => $business->id,
                'dedupe_key' => $normalized['dedupe_key'],
                'month' => $monthName,
            ]);
        }
    }
}