<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Northstar long report</title>
    <style>
        @font-face { font-family: InterFixture; src: url('{{ $font_data_uri }}') format('woff2'); }
        @page { size: A4; margin: 13mm 14mm 15mm; }
        * { box-sizing: border-box; }
        body { color: #17213d; font-family: InterFixture, DejaVu Sans, sans-serif; font-size: 9pt; margin: 0; }
        .chapter { break-after: page; min-height: 257mm; page-break-after: always; position: relative; }
        .chapter:last-child { break-after: auto; page-break-after: auto; }
        header { align-items: center; border-bottom: 2px solid #2557d6; display: flex; justify-content: space-between; margin-bottom: 10px; padding-bottom: 8px; }
        header img { width: 120px; }
        h1 { font-size: 18pt; margin: 0 0 5px; }
        .intro { color: #53617d; margin: 0 0 10px; }
        table { border-collapse: collapse; width: 100%; }
        thead { display: table-header-group; }
        tr { break-inside: avoid; page-break-inside: avoid; }
        th { background: #112047; color: white; padding: 5px 7px; text-align: left; }
        td { border-bottom: 1px solid #dce2ef; padding: 4px 7px; }
        .number { text-align: right; }
        .bars { align-items: end; display: flex; gap: 4px; height: 44px; margin: 8px 0 12px; }
        .bar { background: #4169e1; flex: 1; }
        footer { bottom: 0; color: #6d7891; display: flex; font-size: 8pt; justify-content: space-between; left: 0; position: absolute; right: 0; }
    </style>
</head>
<body>
@foreach($chapters as $chapterIndex => $chapter)
<section class="chapter" data-feature="chapter-page">
    <header><img src="{{ $logo_data_uri }}" alt="Northstar Research"><span>ANNUAL OBSERVATORY REPORT · {{ $benchmark['date'] }}</span></header>
    <h1>{{ $chapter['title'] }}</h1>
    <p class="intro">This page contains deterministic data, a repeated header, explicit break rules, an inline chart, and a long table with rows protected from splitting.</p>
    <div class="bars" data-feature="chart">
        @foreach(array_slice($chapter['rows'], 0, 12) as $row)
            <span class="bar" style="height: {{ 8 + ($row['value'] % 36) }}px"></span>
        @endforeach
    </div>
    <table data-feature="long-table">
        <thead><tr><th>Series</th><th class="number">Observation</th><th class="number">Change</th></tr></thead>
        <tbody>
        @foreach($chapter['rows'] as $row)
            <tr><td>{{ $row['label'] }}</td><td class="number">{{ number_format($row['value']) }}</td><td class="number">{{ $row['change'] }}</td></tr>
        @endforeach
        </tbody>
    </table>
    <footer><span>Northstar Research · seed {{ $benchmark['seed'] }}</span><span>Page {{ $chapterIndex + 1 }} of 10</span></footer>
</section>
@endforeach
</body>
</html>
