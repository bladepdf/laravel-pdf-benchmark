<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Laravel local asset fixture</title>
    @if($vite_css_source)
        <link rel="stylesheet" href="{{ $vite_css_source }}">
    @else
        <style>{!! $vite_css_inline !!}</style>
    @endif
    <style>
        @page { size: A4; margin: 18mm; }
        @font-face { font-family: 'Inter Storage'; src: url('{{ $font_source }}') format('woff2'); }
        body { color: #112047; font-family: 'Inter Storage', Arial, sans-serif; }
        .grid { display: grid; gap: 20px; grid-template-columns: 1fr 1fr; margin-top: 28px; }
        code { background: #eef2fb; border-radius: 4px; padding: 2px 5px; }
        img { max-width: 230px; }
    </style>
</head>
<body>
<section class="local-card" data-feature="local-assets" data-missing-assets="{{ implode(',', $missing_assets) }}">
    <img src="{{ $logo_source }}" alt="Northstar Research local PNG">
    <h1>Laravel local assets</h1>
    <p>Mode: <strong>{{ $benchmark['asset_mode'] }}</strong>. This unchanged view references a <code>public_path()</code> PNG, Vite-built CSS, and a <code>storage_path()</code> WOFF2 font.</p>
    @if($missing_assets)<p><strong>Missing fixture assets:</strong> {{ implode(', ', $missing_assets) }}</p>@endif
    <div class="grid">
        <div><strong>PNG</strong><br><small>{{ public_path('images/benchmark-logo.png') }}</small></div>
        <div><strong>Font</strong><br><small>{{ storage_path('app/fonts/inter.woff2') }}</small></div>
    </div>
</section>
</body>
</html>
