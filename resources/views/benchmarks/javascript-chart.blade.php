<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>JavaScript readiness fixture</title>
    <style>
        @page { size: A4; margin: 14mm; }
        @font-face { font-family: InterFallback; src: url('{{ $font_data_uri }}') format('woff2'); }
        body { color: #112047; font-family: InterFallback, Arial, sans-serif; margin: 0; }
        .shell { background: #f4f7fe; border-radius: 24px; padding: 32px; }
        canvas { background: white; border: 1px solid #dce3f2; border-radius: 16px; display: block; height: 320px; margin-top: 20px; width: 100%; }
        #delayed { background: #d9fff7; border-radius: 12px; color: #17695e; margin-top: 18px; padding: 15px; }
        .pending { color: #9a4b00; }
    </style>
</head>
<body>
<section class="shell">
    <img src="{{ $logo_data_uri }}" width="160" alt="Northstar Research">
    <h1>JavaScript readiness</h1>
    <p>This deterministic chart appears only after JavaScript executes and the asynchronous readiness contract resolves.</p>
    <canvas id="chart" width="1080" height="640" data-feature="javascript-canvas"></canvas>
    <div id="delayed" class="pending" data-feature="delayed-content">Waiting for deterministic client-side content…</div>
</section>
<script>
window.pdfReady = false;
const values = [31, 47, 28, 69, 56, 81, 64, 92, 73, 88, 67, 95];
const font = new FontFace('InterAsync', "url('{{ $font_data_uri }}')");
Promise.all([font.load(), new Promise(resolve => setTimeout(resolve, 250))]).then(([loaded]) => {
    document.fonts.add(loaded);
    const canvas = document.getElementById('chart');
    const ctx = canvas.getContext('2d');
    ctx.fillStyle = '#ffffff'; ctx.fillRect(0, 0, canvas.width, canvas.height);
    ctx.strokeStyle = '#dce3f2'; ctx.lineWidth = 2;
    for (let y = 80; y < 600; y += 100) { ctx.beginPath(); ctx.moveTo(70, y); ctx.lineTo(1030, y); ctx.stroke(); }
    ctx.strokeStyle = '#4169e1'; ctx.lineWidth = 12; ctx.lineJoin = 'round'; ctx.beginPath();
    values.forEach((value, index) => { const x = 80 + index * 84; const y = 590 - value * 5; index === 0 ? ctx.moveTo(x, y) : ctx.lineTo(x, y); });
    ctx.stroke(); ctx.fillStyle = '#112047'; ctx.font = '32px InterAsync'; ctx.fillText('Deterministic observations', 70, 60);
    document.getElementById('delayed').className = '';
    document.getElementById('delayed').textContent = 'Ready: delayed content, async font, and canvas chart completed.';
    window.pdfReady = true;
});
</script>
</body>
</html>
