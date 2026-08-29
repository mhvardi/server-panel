<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Tahoma, Arial, sans-serif; background-color: #f3f4f6; color: #1f2937; padding: 20px; direction: rtl; text-align: right; }
        .container { background-color: #ffffff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); max-width: 600px; margin: 0 auto; }
        h2 { color: #2563eb; margin-bottom: 20px; border-bottom: 2px solid #e5e7eb; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #d1d5db; padding: 10px; text-align: right; }
        th { background-color: #f9fafb; font-weight: bold; }
        .status-success { color: #10b981; font-weight: bold; }
        .status-error { color: #ef4444; font-weight: bold; }
        .footer { margin-top: 20px; font-size: 12px; color: #6b7280; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <h2>گزارش روزانه بکاپ سرور (Server Panel)</h2>
        <p>کاربر گرامی، خلاصه وضعیت بکاپ سرویس‌های شما در 24 ساعت گذشته به شرح زیر است:</p>
        
        <table>
            <thead>
                <tr>
                    <th>نام سرویس</th>
                    <th>وضعیت آخرین بکاپ</th>
                    <th>زمان اجرا</th>
                    <th>حجم (MB)</th>
                    <th>آپلود FTP</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($reportData as $data)
                    <tr>
                        <td>{{ $data['name'] }}</td>
                        <td class="{{ $data['status'] === 'موفق' ? 'status-success' : 'status-error' }}">
                            {{ $data['status'] }}
                        </td>
                        <td dir="ltr" style="text-align: right;">{{ $data['time'] }}</td>
                        <td>{{ $data['size'] }}</td>
                        <td>{{ $data['ftp'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="footer">
            <p>این ایمیل به صورت خودکار توسط سیستم Server Panel ارسال شده است.</p>
            <p>{{ now()->format('Y-m-d H:i:s') }}</p>
        </div>
    </div>
</body>
</html>
