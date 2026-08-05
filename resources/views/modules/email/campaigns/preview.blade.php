<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Preview — {{ $campaign->name }}</title>
    <style>
        body { margin: 0; background: #f3f4f6; font-family: system-ui, sans-serif; }
        .bar { background: #111827; color: #fff; padding: 12px 20px; font-size: 14px; }
        .bar small { opacity: .75; display: block; margin-top: 4px; }
        .frame { max-width: 720px; margin: 24px auto; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,.08); padding: 8px; }
    </style>
</head>
<body>
    <div class="bar">
        Preview · {{ $previewSubject }}
        <small>
            @if($sampleSubscriber)
                Personalized with {{ $sampleSubscriber->email }}
            @else
                Sample merge tags (no subscriber on list yet)
            @endif
            · tracking links not rewritten in preview
        </small>
    </div>
    <div class="frame">
        {!! $previewHtml !!}
    </div>
</body>
</html>
