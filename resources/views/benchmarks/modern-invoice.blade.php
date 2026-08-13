<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Modern invoice {{ $invoice['number'] }}</title>
    <style>{!! $tailwind_css !!}</style>
    <style>
        @font-face { font-family: InterFixture; src: url('{{ $font_data_uri }}') format('woff2'); font-weight: 100 900; }
        @page { size: A4; margin: 0; }
        :root { --ink: #112047; --accent: #4169e1; --aqua: #4ed2c2; --paper: #f5f7fc; }
        * { box-sizing: border-box; }
        body { background: white; color: var(--ink); font-family: InterFixture, Arial, sans-serif; margin: 0; }
        .modern-page { min-height: 297mm; overflow: hidden; padding: 18mm; position: relative; }
        .modern-page:first-child { break-after: page; page-break-after: always; }
        .hero { align-items: center; background: linear-gradient(125deg, var(--ink), var(--accent) 62%, var(--aqua)); border-radius: 24px; color: white; display: flex; justify-content: space-between; padding: 28px; }
        .hero-logo { background: white; border-radius: 16px; padding: 8px; width: 150px; }
        .metrics { display: grid; gap: 12px; grid-template-columns: repeat(3, minmax(0, 1fr)); margin: 22px 0; }
        .metric { background: var(--paper); border: 1px solid #dfe6f6; border-radius: 18px; padding: 18px; }
        .metric strong { display: block; font-size: 20px; margin-top: 8px; }
        .layout { display: grid; gap: 18px; grid-template-columns: 1.5fr .7fr; }
        .card { border: 1px solid #dfe6f6; border-radius: 20px; padding: 20px; }
        .line { align-items: center; border-bottom: 1px solid #e8ecf6; display: flex; gap: 12px; justify-content: space-between; padding: 11px 0; }
        .badge { background: linear-gradient(90deg, #dfe7ff, #d8fff8); border-radius: 999px; color: #244ab3; display: inline-flex; font-size: 11px; font-weight: 700; padding: 6px 11px; }
        .orb { background: radial-gradient(circle at 32% 28%, #fff 0 3%, #4ed2c2 4% 19%, #4169e1 20% 58%, #112047 59%); border-radius: 50%; height: 92px; margin: 24px auto; width: 92px; }
        .footer { bottom: 14mm; color: #71809e; display: flex; font-size: 10px; justify-content: space-between; left: 18mm; position: absolute; right: 18mm; }
    </style>
</head>
<body>
@php($total = collect($items)->sum('total') * 1.08)
@foreach([array_slice($items, 0, 9), array_slice($items, 9)] as $page => $pageItems)
<section class="modern-page" data-feature="modern-layout">
    <header class="hero" data-feature="gradient-flexbox">
        <div><span class="badge">{{ $page === 0 ? 'NEW INVOICE' : 'CONTINUED' }}</span><h1 class="mt-4 text-4xl font-semibold tracking-tight">{{ $invoice['number'] }}</h1><p class="mt-2 opacity-80">{{ $customer['name'] }}</p></div>
        <img class="hero-logo" src="{{ $logo_data_uri }}" alt="Northstar Research">
    </header>
    <div class="metrics" data-feature="css-grid">
        <div class="metric"><span>Issued</span><strong>{{ $invoice['issued'] }}</strong></div>
        <div class="metric"><span>Due</span><strong>{{ $invoice['due'] }}</strong></div>
        <div class="metric"><span>Total</span><strong>${{ number_format($total, 2) }}</strong></div>
    </div>
    <div class="layout">
        <main class="card">
            <div class="flex items-center justify-between"><h2 class="text-xl font-semibold">Services</h2><span class="badge">Page {{ $page + 1 }} / 2</span></div>
            @foreach($pageItems as $item)
            <div class="line"><div><strong>{{ $item['description'] }}</strong><div class="text-xs text-slate-500">Quantity {{ $item['quantity'] }}</div></div><strong>${{ number_format($item['total'], 2) }}</strong></div>
            @endforeach
        </main>
        <aside class="card"><svg viewBox="0 0 120 120" aria-label="Orbit icon" data-feature="inline-svg"><circle cx="60" cy="60" r="8" fill="#4169e1"/><ellipse cx="60" cy="60" rx="52" ry="20" fill="none" stroke="#4ed2c2" stroke-width="4" transform="rotate(-25 60 60)"/><ellipse cx="60" cy="60" rx="20" ry="52" fill="none" stroke="#4169e1" stroke-width="4" transform="rotate(30 60 60)"/></svg><div class="orb"></div><p class="text-center text-sm text-slate-500">A modern production-style Blade view using browser CSS features.</p></aside>
    </div>
    <footer class="footer"><span>Northstar Research</span><span>Seed {{ $benchmark['seed'] }}</span></footer>
</section>
@endforeach
</body>
</html>
