<?php

namespace App\Jobs;

use App\Models\Business;
use App\Models\Expense;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\WhatsAppConfig;
use App\Models\WhatsAppMessageLog;
use App\Models\WhatsAppTemplate;
use App\Services\WhatsApp\WhatsAppProviderFactory;
use App\Services\WhatsApp\WhatsAppTemplateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class GenerateMonthlyBusinessReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $businesses = Business::all();

        foreach ($businesses as $business) {
            $businessId = $business->id;

            $totalSales = Sale::where('business_id', $businessId)
                ->whereRaw('created_at >= ? AND created_at < ?', [
                    Carbon::now()->startOfMonth()->subMonth()->format('Y-m-d'),
                    Carbon::now()->startOfMonth()->format('Y-m-d'),
                ])
                ->sum('total_amount');

            $totalPurchases = Purchase::where('business_id', $businessId)
                ->whereRaw('created_at >= ? AND created_at < ?', [
                    Carbon::now()->startOfMonth()->subMonth()->format('Y-m-d'),
                    Carbon::now()->startOfMonth()->format('Y-m-d'),
                ])
                ->sum('total_amount');

            $totalExpenses = Expense::where('business_id', $businessId)
                ->whereRaw('created_at >= ? AND created_at < ?', [
                    Carbon::now()->startOfMonth()->subMonth()->format('Y-m-d'),
                    Carbon::now()->startOfMonth()->format('Y-m-d'),
                ])
                ->sum('amount');

            $profitLoss = (is_numeric($totalSales) ? $totalSales : 0) - (is_numeric($totalPurchases) ? $totalPurchases : 0) - (is_numeric($totalExpenses) ? $totalExpenses : 0);

            $numberOfSales = Sale::where('business_id', $businessId)
                ->whereRaw('created_at >= ? AND created_at < ?', [
                    Carbon::now()->startOfMonth()->subMonth()->format('Y-m-d'),
                    Carbon::now()->startOfMonth()->format('Y-m-d'),
                ])
                ->count();

            $numberOfPurchases = Purchase::where('business_id', $businessId)
                ->whereRaw('created_at >= ? AND created_at < ?', [
                    Carbon::now()->startOfMonth()->subMonth()->format('Y-m-d'),
                    Carbon::now()->startOfMonth()->format('Y-m-d'),
                ])
                ->count();

            $recipientPhone = $business->phone;

            // This used to be `$business->phone ?? '+256731794401'`, which sent a
            // business's monthly revenue, expenses and profit/loss to a hardcoded
            // personal number whenever the business had no phone on file. A developer's
            // phone receiving customers' financials is not a defect worth carrying for
            // one more sprint, and it is invisible: the log says "sent" and the row
            // records a recipient nobody chose.
            //
            // No recipient means no send. There is no default that is not somebody's
            // personal number.
            if (empty($recipientPhone)) {
                Log::warning('Monthly business report skipped: business has no phone on file', [
                    'business_id' => $businessId,
                ]);

                continue;
            }

            $config = WhatsAppConfig::where('business_id', $businessId)->first();

            $templateKey = 'report.monthly';
            $template = WhatsAppTemplate::where('business_id', $businessId)
                ->where('name', $templateKey)
                ->where('status', 'approved')
                ->latest('created_at')
                ->first();

            $templateString = $template?->body ?? 'Hello {{business_name}}, your {{month_name}} performance report is ready. Open DuukaFlow to view the full figures.';

            // A template's `variables` column holds the variable names it expects,
            // not values, so it must never be used as the render data.
            $templateData = [
                'business_name' => $business->name,
                'month_name' => Carbon::now()->subMonth()->monthName,
                'total_sales' => is_numeric($totalSales) ? number_format($totalSales, 2) : '0.00',
                'total_purchases' => is_numeric($totalPurchases) ? number_format($totalPurchases, 2) : '0.00',
                'profit_loss' => is_numeric($profitLoss) ? number_format($profitLoss, 2) : '0.00',
                'number_of_sales' => is_numeric($numberOfSales) ? $numberOfSales : 0,
                'number_of_purchases' => is_numeric($numberOfPurchases) ? $numberOfPurchases : 0,
            ];

            $message = (new WhatsAppTemplateService)->render($templateString, $templateData);

            $provider = WhatsAppProviderFactory::create([
                'provider' => $config?->provider ?? 'demo',
                // Was `?? '+256731794401'`. The same hardcoded number, this time as the
                // sending identity, so the report would have appeared to come from a
                // stranger's handset as well as going to one.
                'business_phone' => $config?->business_phone,
                'access_token' => $config?->access_token,
                'phone_number_id' => $config?->phone_number_id,
            ]);

            $result = $provider->sendMessage([
                'to' => $recipientPhone,
                'message' => $message,
                'template_key' => $templateKey,
                'business_id' => $businessId,
            ]);

            // Not `$result['success']`. The real provider reports a refusal by returning
            // an `error` key and has no `success` key at all, so the old read raised an
            // undefined-key warning on every genuine Meta failure and fell through to
            // 'failed' — correct by accident, and one rename away from silently inverted.
            $sent = ($result['error'] ?? null) === null;

            WhatsAppMessageLog::create([
                'business_id' => $businessId,
                'template_id' => $template?->id,
                'recipient' => $recipientPhone,
                'channel' => 'whatsapp',
                'message_body' => $message,
                'variables' => $templateData,
                'status' => $sent ? 'sent' : 'failed',
                'provider_response' => $result,
                'sent_at' => $sent ? now() : null,
                'dedupe_key' => 'report:monthly:business-'.$businessId.':'.Carbon::now()->subMonth()->format('Y-m'),
            ]);

            Log::info('Monthly business report generated', [
                'business_id' => $businessId,
                'total_sales' => $totalSales,
                'total_purchases' => $totalPurchases,
                'profit_loss' => $profitLoss,
                'status' => $sent ? 'sent' : 'failed',
            ]);
        }
    }
}
