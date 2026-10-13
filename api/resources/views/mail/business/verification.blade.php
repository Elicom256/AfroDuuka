<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify your email address</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #1a1a2e; color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
        .button { display: inline-block; background: #1a1a2e; color: white; padding: 14px 28px; text-decoration: none; border-radius: 6px; font-weight: 600; margin: 20px 0; }
        .button:hover { background: #16213e; }
        .footer { text-align: center; color: #888; font-size: 12px; margin-top: 30px; }
        .business-name { font-size: 24px; font-weight: 700; margin-bottom: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="business-name">{{ config('app.name') }}</div>
        <p>Business Email Verification</p>
    </div>
    <div class="content">
        <h2>Welcome to {{ config('app.name') }}, {{ $business->name }}!</h2>
        <p>Thank you for registering your business. To complete your registration and activate your account, please verify your email address by clicking the button below:</p>
        <p style="text-align: center;">
            <a href="{{ $verificationUrl }}" class="button">Verify Email Address</a>
        </p>
        <p>Or copy and paste this link into your browser:</p>
        <p style="word-break: break-all; color: #666; font-size: 14px;">{{ $verificationUrl }}</p>
        <p>This link will expire in 24 hours. If you didn't create an account with {{ config('app.name') }}, you can safely ignore this email.</p>
        <hr style="border: none; border-top: 1px solid #ddd; margin: 30px 0;">
        <p style="color: #888; font-size: 13px;">If the button above doesn't work, please copy the link and paste it into your browser.</p>
    </div>
    <div class="footer">
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        <p>If you have questions, contact our support team.</p>
    </div>
</body>
</html>