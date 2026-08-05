<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Unsubscribe</title>
    <style>
        :root { color-scheme: light; }
        body {
            margin: 0; min-height: 100vh; display: grid; place-items: center;
            font-family: Georgia, 'Times New Roman', serif;
            background: linear-gradient(160deg, #e8eef5 0%, #f7f4ef 55%, #eef2f7 100%);
            color: #1f2937;
        }
        .card {
            width: min(440px, 92vw); background: rgba(255,255,255,.92);
            border: 1px solid rgba(31,41,55,.08); padding: 2rem; border-radius: 4px;
            box-shadow: 0 12px 40px rgba(15,23,42,.08);
        }
        h1 { font-size: 1.5rem; margin: 0 0 .75rem; font-weight: 600; }
        p { margin: 0 0 1.25rem; line-height: 1.5; color: #4b5563; }
        .email { font-family: ui-monospace, monospace; font-size: .9rem; }
        button {
            appearance: none; border: 0; background: #1f3a5f; color: #fff;
            padding: .7rem 1.1rem; border-radius: 3px; cursor: pointer; font: inherit;
        }
        .ok { color: #166534; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Unsubscribe</h1>
        @if(session('success') || $already)
            <p class="ok">{{ session('success') ?: 'You are already unsubscribed.' }}</p>
            <p class="email">{{ $subscriber->email }}</p>
        @else
            <p>Stop receiving marketing emails for <span class="email">{{ $subscriber->email }}</span>?</p>
            <form method="POST" action="{{ route('email.unsubscribe.store', $subscriber->unsubscribe_token) }}">
                @csrf
                <button type="submit">Confirm unsubscribe</button>
            </form>
        @endif
    </div>
</body>
</html>
