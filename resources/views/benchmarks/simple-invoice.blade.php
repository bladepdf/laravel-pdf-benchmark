<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Simple invoice {{ $invoice['number'] }}</title>
    <style>
        @page { size: A4; margin: 14mm; }
        * { box-sizing: border-box; }
        body { color: #24304b; font-family: DejaVu Sans, Arial, sans-serif; font-size: 10pt; line-height: 1.45; margin: 0; }
        .page { min-height: 245mm; position: relative; }
        .page-break { page-break-after: always; }
        .header, .meta { width: 100%; }
        .header td { vertical-align: middle; }
        .logo { height: 38px; width: 127px; }
        h1 { color: #112047; font-size: 25pt; margin: 0; text-align: right; }
        .muted { color: #68738d; }
        .addresses { margin: 24px 0; width: 100%; }
        .addresses td { border: 1px solid #d7ddea; padding: 12px; vertical-align: top; width: 50%; }
        table.lines { border-collapse: collapse; width: 100%; }
        .lines th { background: #112047; color: white; font-size: 8pt; padding: 8px; text-align: left; }
        .lines td { border-bottom: 1px solid #d7ddea; padding: 8px; }
        .number { text-align: right; }
        .summary { border-collapse: collapse; margin: 20px 0 0 auto; width: 240px; }
        .summary td { padding: 6px; }
        .summary .total { border-top: 2px solid #112047; font-size: 13pt; font-weight: bold; }
        .footer { bottom: 0; border-top: 1px solid #d7ddea; color: #68738d; font-size: 8pt; left: 0; padding-top: 8px; position: absolute; right: 0; }
    </style>
</head>
<body>
@php($subtotal = collect($items)->sum('total'))
@foreach([array_slice($items, 0, 9), array_slice($items, 9)] as $page => $pageItems)
    <section class="page {{ $page === 0 ? 'page-break' : '' }}" data-feature="manual-page-break">
        <table class="header"><tr>
            <td><img class="logo" src="{{ $logo_data_uri }}" alt="Northstar Research"></td>
            <td><h1>INVOICE</h1></td>
        </tr></table>
        <p class="muted">{{ $invoice['number'] }} · issued {{ $invoice['issued'] }} · page {{ $page + 1 }} of 2</p>

        @if($page === 0)
        <table class="addresses"><tr>
            <td><strong>From</strong><br>{{ $company['name'] }}<br>{{ $company['email'] }}</td>
            <td><strong>Bill to</strong><br>{{ $customer['name'] }}<br>{{ $customer['address'] }}</td>
        </tr></table>
        @else
        <p><strong>Invoice continuation</strong><br><span class="muted">Remaining deterministic line items.</span></p>
        @endif

        <table class="lines" data-feature="table-layout">
            <thead><tr><th>Description</th><th class="number">Qty</th><th class="number">Unit</th><th class="number">Amount</th></tr></thead>
            <tbody>
            @foreach($pageItems as $item)
                <tr><td>{{ $item['description'] }}</td><td class="number">{{ $item['quantity'] }}</td><td class="number">${{ number_format($item['unit'], 2) }}</td><td class="number">${{ number_format($item['total'], 2) }}</td></tr>
            @endforeach
            </tbody>
        </table>

        @if($page === 1)
        <table class="summary">
            <tr><td>Subtotal</td><td class="number">${{ number_format($subtotal, 2) }}</td></tr>
            <tr><td>Tax (8%)</td><td class="number">${{ number_format($subtotal * .08, 2) }}</td></tr>
            <tr class="total"><td>Total</td><td class="number">${{ number_format($subtotal * 1.08, 2) }}</td></tr>
        </table>
        @endif
        <footer class="footer">Northstar Research · deterministic benchmark fixture · seed {{ $benchmark['seed'] }}</footer>
    </section>
@endforeach
</body>
</html>
