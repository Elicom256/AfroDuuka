<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $headline }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f5f7;font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:#1f2933;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5f7;padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0"
                   style="max-width:600px;width:100%;background:#ffffff;border-radius:10px;overflow:hidden;border:1px solid #e4e7eb;">

                <tr>
                    <td style="padding:20px 32px;border-bottom:1px solid #e4e7eb;">
                        <span style="font-size:15px;font-weight:700;color:#0b7285;letter-spacing:.02em;">
                            DuukaFlow
                        </span>
                    </td>
                </tr>

                <tr>
                    <td style="padding:32px;">
                        <h1 style="margin:0 0 12px;font-size:21px;line-height:1.3;font-weight:700;">
                            {{ $headline }}
                        </h1>

                        @if ($body)
                            <p style="margin:0 0 20px;font-size:15px;line-height:1.6;color:#3e4c59;">
                                {{ $body }}
                            </p>
                        @endif

                        @if (count($details))
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                   style="margin:0 0 24px;border-collapse:collapse;">
                                @foreach ($details as $row)
                                    <tr>
                                        <td style="padding:8px 0;font-size:14px;color:#7b8794;width:40%;vertical-align:top;">
                                            {{ $row['label'] }}
                                        </td>
                                        <td style="padding:8px 0;font-size:14px;color:#1f2933;font-weight:600;">
                                            {{ $row['value'] }}
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                        @endif

                        @if ($actionUrl)
                            <p style="margin:0 0 8px;">
                                <a href="{{ $actionUrl }}"
                                   style="display:inline-block;padding:12px 22px;background:#0b7285;color:#ffffff;text-decoration:none;border-radius:6px;font-size:15px;font-weight:600;">
                                    {{ $actionLabel ?? 'Open DuukaFlow' }}
                                </a>
                            </p>
                        @endif
                    </td>
                </tr>

                <tr>
                    <td style="padding:20px 32px;background:#fafbfc;border-top:1px solid #e4e7eb;font-size:12px;line-height:1.6;color:#7b8794;">
                        <p style="margin:0 0 8px;">
                            You are receiving this because you have a DuukaFlow business account.
                        </p>
                        <p style="margin:0;">
                            &copy; {{ date('Y') }} DuukaFlow
                        </p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>
</body>
</html>
