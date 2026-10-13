<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monthly Performance Report</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #27ae60; color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
        .button { display: inline-block; background: #27ae60; color: white; padding: 14px 28px; text-decoration: none; border-radius: 6px; font-weight: 600; margin: 20px 0; }
        .footer { text-align: center; color: #888; font-size: 12px; margin-top: 30px; }
        .highlight { background: #e8f8f5; border-left: 4px solid #27ae60; padding: 15px; margin: 20px 0; border-radius: 0 6px 6px 0; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Monthly Performance Report</h1>
        <p>{{ $business->name }} - {{ $period }}</p>
    </div>
    <div class="content">
        <p>Your monthly performance report for <strong>{{ $period }}</strong> is ready.</p>
        <div class="highlight">
            <p style="margin: 0;"><strong>Report Period:</strong> {{ $period }}</p>
        </div>
        <p>View your detailed report online to see sales trends, top products, inventory status, and more.</p>
        <p style="text-align: center;">
            <a href="{{ $reportUrl }}" class="button">View Report</a>
        </p>
        <p>A PDF version of this report is attached to this email for your records.</p>
    </div>
    <div class="footer">
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
    </div>
</body>
</html>