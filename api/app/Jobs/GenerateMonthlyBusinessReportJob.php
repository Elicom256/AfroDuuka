<?php

namespace App\Jobs;

use App\Models\Business;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Expense;
use App\Models\WhatsAppConfig;
use App\Models\WhatsAppMessageLog;
use App\Services\WhatsApp\WhatsAppNotificationService;
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

            $recipientPhone = $business->phone ?? '+256731794401';

            $config = WhatsAppConfig::where('business_id', $businessId)->first();

            $templateKey = 'report.monthly';
            $template = WhatsAppTemplate::where('business_id', $businessId)
                ->where('category', 'report')
                ->where('status', 'approved')
                ->latest('created_at')
                ->first();

            $templateString = $template?->body ?? 'Monthly Report: Sales {{total_sales}}, Purchases {{total_purchases}}, Profit/Loss {{profit_loss}}';
            $templateData = $template?->variables ?? [
                'business_name' => $business->name,
                'month_name' => Carbon::now()->subMonth()->monthName,
                'total_sales' => is_numeric($totalSales) ? number_format($totalSales, 2) : '0.00',
                'total_purchases' => is_numeric($totalPurchases) ? number_format($totalPurchases, 2) : '0.00',
                'profit_loss' => is_numeric($profitLoss) ? number_format($profitLoss, 2) : '0.00',
                'number_of_sales' => is_numeric($numberOfSales) ? $numberOfSales : 0,
                'number_of_purchases' => is_numeric($numberOfPurchases) ? $numberOfPurchases : 0,
            ];

            $message = (new WhatsAppTemplateService())->render($templateString, $templateData);

            $provider = \App\Services\WhatsApp\WhatsAppProviderFactory::create([
                'provider' => $config?->provider ?? 'demo',
                'business_phone' => $config?->business_phone ?? '+256731794401',
                'access_token' => $config?->access_token,
                'phone_number_id' => $config?->phone_number_id,
            ]);

            $result = $provider->sendMessage([
                'to' => $recipientPhone,
                'message' => $message,
                'template_key' => $templateKey,
                'business_id' => $businessId,
            ]);

            WhatsAppMessageLog::create([
                'business_id' => $businessId,
                'template_id' => $template?->id,
                'recipient' => $recipientPhone,
                'channel' => 'whatsapp',
                'message_body' => $message,
                'variables' => $templateData,
                'status' => $result['success'] ? 'sent' : 'failed',
                'provider_response' => $result,
                'sent_at' => $result['success'] ? now() : null,
                'dedupe_key' => 'report:monthly:business-' . $businessId . ':' . Carbon::now()->subMonth()->format('Y-m'),
            ]);

            Log::info('Monthly business report generated', [
                'business_id' => $businessId,
                'total_sales' => $totalSales,
                'total_purchases' => $totalPurchases,
                'profit_loss' => $profitLoss,
                'status' => $result['success'] ? 'sent' : 'failed',
            ]);
        }
    }
}