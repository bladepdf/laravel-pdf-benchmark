import { mkdir, writeFile } from 'node:fs/promises';
import { spawn } from 'node:child_process';

function run(command, args) {
    return new Promise((resolve) => {
        const started = performance.now();
        const child = spawn(command, args, { stdio: ['ignore', 'pipe', 'pipe'] });
        let stdout = ''; let stderr = '';
        child.stdout.on('data', chunk => stdout += chunk);
        child.stderr.on('data', chunk => stderr += chunk);
        child.on('close', code => resolve({ code, duration_seconds: (performance.now() - started) / 1000, stdout: stdout.slice(-10000), stderr: stderr.slice(-10000) }));
    });
}

const network = process.env.BENCHMARK_OPS_NETWORK === 'enabled';
if (!network) {
    throw new Error('Set BENCHMARK_OPS_NETWORK=enabled to acknowledge the clean-room network-dependent build.');
}

const image = 'laravel-pdf-benchmark:clean-room';
const project = 'laravel-pdf-benchmark-ops';
process.env.BENCHMARK_APP_IMAGE = image;
process.env.COMPOSE_PROJECT_NAME = project;

const preclean = await run('docker', ['compose', '-p', project, 'down', '--volumes', '--remove-orphans']);
const build = await run('docker', ['build', '--platform', 'linux/amd64', '--no-cache', '--pull', '-t', image, '.']);
const size = build.code === 0 ? await run('docker', ['image', 'inspect', image, '--format', '{{.Size}}']) : null;
const start = build.code === 0 ? await run('docker', ['compose', '-p', project, 'up', '-d', '--wait', 'gotenberg', 'chromium-persistent']) : null;
const firstPdf = {};

if (start?.code === 0) {
    for (const renderer of ['dompdf', 'browsershot', 'gotenberg']) {
        const suffix = new Date().toISOString().replaceAll(/[^0-9]/g, '').toLowerCase().slice(0, 14);
        firstPdf[renderer] = await run('docker', [
            'compose', '-p', project, 'run', '--rm',
            '-e', 'BENCHMARK_HOST_LABEL=clean-room-installation',
            '-e', 'BENCHMARK_REGION=local-clean-room',
            '-e', 'BENCHMARK_CLOUDFLARE_PLAN=not-run',
            '-e', 'BENCHMARK_BLADEPDF_PLAN=not-run',
            '-e', 'BENCHMARK_BLADEPDF_CONCURRENCY=1',
            '-e', 'BENCHMARK_COOLDOWN_SECONDS=0',
            'app', 'php', 'artisan', 'benchmark:run', '--profile=smoke', `--renderers=${renderer}`,
            '--templates=simple-invoice', `--run-id=ops-${renderer}-${suffix}`, '--allow-dirty',
        ]);
    }
}

const stop = await run('docker', ['compose', '-p', project, 'down', '--volumes', '--remove-orphans']);

await mkdir('results/work', { recursive: true });
await writeFile('results/work/ops-install.json', JSON.stringify({
    schema_version: 1,
    measured_at: new Date().toISOString(),
    cache_state: 'clean Docker build cache for application image',
    network_state: 'enabled',
    preclean,
    build,
    image_size_bytes: size?.code === 0 ? Number(size.stdout.trim()) : null,
    start,
    first_pdf_by_renderer: firstPdf,
    stop,
}, null, 2) + '\n');

const firstPdfFailure = Object.values(firstPdf).find(result => result.code !== 0)?.code ?? 0;
process.exit(preclean.code || build.code || start?.code || firstPdfFailure || stop.code || 0);
