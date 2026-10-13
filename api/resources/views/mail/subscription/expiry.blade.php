<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Expiring</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #f39c12; color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
        .button { display: inline-block; background: #f39c12; color: white; padding: 14px 28px; text-decoration: none; border-radius: 6px; font-weight: 600; margin: 20px 0; }
        .footer { text-align: center; color: #888; font-size: 12px; margin-top: 30px; }
        .alert-box { background: #fff3cd; border: 1px solid #ffeeba; color: #856404; padding: 15px; border-radius: 6px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Subscription Expiring Soon</h1>
        <p>{{ $business->name }}</p>
    </div>
    <div class="content">
        <div class="alert-box">
            <strong>Action Required:</strong> Your {{ $planName }} subscription expires on {{ $endsAt }}.
        </div>
        <p>Your current subscription will expire soon. To continue using {{ config('app.name') }} without interruption, please renew your subscription.</p>
        @if ($renewUrl)
        <p style="text-align: center;">
            <a href="{{ $renewUrl }}" class="button">Renew Subscription</a>
        </p>
        @endif
        <p>If you have any questions about your subscription or need assistance with renewal, please contact our support team.</p>
    </div>
    <div class="footer">
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
    </div>
</body>
</html>