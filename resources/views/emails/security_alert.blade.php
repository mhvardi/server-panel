<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Tahoma, Arial, sans-serif; background-color: #0f172a; color: #e2e8f0; padding: 24px; direction: rtl; text-align: right; }
        .container { background-color: #1e293b; padding: 24px; border-radius: 16px; border: 1px solid #334155; max-width: 600px; margin: 0 auto; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.5); }
        .header { border-bottom: 1px solid #334155; padding-bottom: 16px; margin-bottom: 20px; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 9999px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .badge-critical { background-color: rgba(244,63,94,0.2); color: #fb7185; border: 1px solid rgba(244,63,94,0.4); }
        .badge-warning { background-color: rgba(245,158,11,0.2); color: #fbbf24; border: 1px solid rgba(245,158,11,0.4); }
        .badge-info { background-color: rgba(99,102,241,0.2); color: #818cf8; border: 1px solid rgba(99,102,241,0.4); }
        .title { font-size: 18px; font-weight: bold; color: #ffffff; margin-top: 10px; }
        .desc-box { background-color: #0f172a; border-radius: 12px; padding: 16px; margin: 20px 0; border: 1px solid #1e293b; }
        .label { font-size: 12px; color: #94a3b8; margin-bottom: 4px; }
        .value { font-size: 14px; color: #f1f5f9; line-height: 1.6; }
        .meta-table { width: 100%; border-collapse: collapse; margin-top: 16px; font-size: 12px; }
        .meta-table td { padding: 8px 0; border-bottom: 1px solid #334155; color: #cbd5e1; }
        .meta-table td.key { color: #94a3b8; width: 35%; }
        .footer { margin-top: 24px; font-size: 11px; color: #64748b; text-align: center; border-top: 1px solid #334155; padding-top: 16px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <span class="badge {{ $event->severity === 'critical' ? 'badge-critical' : ($event->severity === 'warning' ? 'badge-warning' : 'badge-info') }}">
                سطح اهمیت: {{ strtoupper($event->severity) }}
            </span>
            <div class="title">{{ $event->title }}</div>
        </div>

        <div class="desc-box">
            <div class="label">شرح رویداد و تهدید شناسایی‌شده:</div>
            <div class="value">{{ $event->description ?: 'توضیحات تکمیلی ثبت نشده است.' }}</div>
        </div>

        <table class="meta-table">
            <tr>
                <td class="key">دسته‌بندی امنیتی:</td>
                <td><strong>{{ $event->type }}</strong></td>
            </tr>
            @if($event->source_ip)
            <tr>
                <td class="key">آدرس آی‌پی منبع:</td>
                <td dir="ltr" style="text-align: right; font-family: monospace; color: #fbbf24;"><strong>{{ $event->source_ip }}</strong></td>
            </tr>
            @endif
            <tr>
                <td class="key">زمان ثبت رویداد:</td>
                <td dir="ltr" style="text-align: right;">{{ $event->created_at->format('Y-m-d H:i:s') }}</td>
            </tr>
        </table>

        @if($additionalDetails)
            <div style="margin-top: 16px; font-size: 12px; color: #94a3b8;">
                {{ $additionalDetails }}
            </div>
        @endif

        <div class="footer">
            <p>این ایمیل امنیتی به صورت فوری توسط سامانه نظارت و هسته فایروال Server Panel ارسال گردیده است.</p>
        </div>
    </div>
</body>
</html>
