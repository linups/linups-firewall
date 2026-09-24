<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; font-size: 14px; color: #222;">
    <h2 style="margin: 0 0 16px;">Page not found (404)</h2>

    <table cellpadding="4" style="border-collapse: collapse;">
        <tr><td><strong>URL</strong></td><td>{{ $url }}</td></tr>
        <tr><td><strong>Time</strong></td><td>{{ $time->format('Y-m-d H:i:s T') }}</td></tr>
        <tr><td><strong>Method</strong></td><td>{{ $method }}</td></tr>
        <tr><td><strong>IP</strong></td><td>{{ $ip }}</td></tr>
        <tr><td><strong>User-Agent</strong></td><td>{{ $userAgent }}</td></tr>
        <tr><td><strong>Referer</strong></td><td>{{ $referer ?? '-' }}</td></tr>
    </table>

    <p style="margin: 24px 0;">
        <a href="{{ $banUrl }}" style="background: #c0392b; color: #fff; padding: 10px 16px; text-decoration: none; border-radius: 4px;">Add url to ban list</a>
    </p>

    <h3 style="margin: 24px 0 8px;">Request headers</h3>
    <table cellpadding="4" style="border-collapse: collapse; font-size: 12px;">
        @foreach ($headers as $name => $value)
            <tr><td><strong>{{ $name }}</strong></td><td>{{ $value }}</td></tr>
        @endforeach
    </table>
</body>
</html>