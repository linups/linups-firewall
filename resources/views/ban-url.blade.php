<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Add URL to ban list</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; color: #222; margin: 0; padding: 40px 16px; }
        .box { max-width: 560px; margin: 0 auto; background: #fff; padding: 24px; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,.15); }
        h1 { font-size: 20px; margin: 0 0 16px; }
        label { display: block; font-weight: bold; margin-bottom: 6px; }
        input[type=text] { width: 100%; box-sizing: border-box; padding: 8px; font-size: 14px; border: 1px solid #bbb; border-radius: 4px; }
        button { margin-top: 16px; background: #c0392b; color: #fff; border: 0; padding: 10px 16px; font-size: 14px; border-radius: 4px; cursor: pointer; }
        .hint { font-size: 12px; color: #666; margin-top: 6px; }
        .status { background: #e7f6e7; border: 1px solid #9c9; padding: 8px; margin-bottom: 16px; border-radius: 4px; }
        .error { color: #c0392b; font-size: 13px; margin-top: 6px; }
    </style>
</head>
<body>
    <div class="box">
        <h1>Add URL to ban list</h1>

        @if (session('linups-firewall.status'))
            <div class="status">{{ session('linups-firewall.status') }}</div>
        @endif

        <form method="POST" action="{{ request()->fullUrl() }}">
            @csrf
            <label for="keyword">Keyword</label>
            <input type="text" id="keyword" name="keyword" value="{{ $keyword }}" required minlength="3" maxlength="255">
            <div class="hint">Any request whose URL contains this text (case-insensitive) will be banned.</div>
            @error('keyword')
                <div class="error">{{ $message }}</div>
            @enderror
            <button type="submit">Save</button>
        </form>
    </div>
</body>
</html>