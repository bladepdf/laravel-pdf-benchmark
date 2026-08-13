import puppeteer from 'puppeteer';
import http from 'node:http';
import net from 'node:net';

const chromePort = 9223;
const publicPort = 9222;

const browser = await puppeteer.launch({
    headless: true,
    args: [
        '--no-sandbox',
        '--disable-setuid-sandbox',
        `--remote-debugging-port=${chromePort}`,
    ],
});

const proxy = http.createServer(async (request, response) => {
    try {
        const upstream = await fetch(`http://127.0.0.1:${chromePort}${request.url}`);
        let body = await upstream.text();

        if (request.url === '/json/version') {
            const publicHost = request.headers.host ?? `chromium-persistent:${publicPort}`;
            body = body.replaceAll(`ws://127.0.0.1:${chromePort}`, `ws://${publicHost}`);
        }

        response.writeHead(upstream.status, {
            'content-type': upstream.headers.get('content-type') ?? 'application/json',
            'content-length': Buffer.byteLength(body),
        });
        response.end(body);
    } catch (error) {
        response.writeHead(502, { 'content-type': 'text/plain' });
        response.end(error instanceof Error ? error.message : 'DevTools proxy failure');
    }
});

proxy.on('upgrade', (request, client, head) => {
    const upstream = net.connect(chromePort, '127.0.0.1', () => {
        const headers = [];
        for (let index = 0; index < request.rawHeaders.length; index += 2) {
            const name = request.rawHeaders[index];
            const value = name.toLowerCase() === 'host'
                ? `127.0.0.1:${chromePort}`
                : request.rawHeaders[index + 1];
            headers.push(`${name}: ${value}`);
        }

        upstream.write(`${request.method} ${request.url} HTTP/${request.httpVersion}\r\n${headers.join('\r\n')}\r\n\r\n`);
        if (head.length > 0) {
            upstream.write(head);
        }
        client.pipe(upstream).pipe(client);
    });

    upstream.on('error', () => client.destroy());
    client.on('error', () => upstream.destroy());
});

await new Promise((resolve, reject) => {
    proxy.once('error', reject);
    proxy.listen(publicPort, '0.0.0.0', resolve);
});

const stop = async () => {
    proxy.close();
    await browser.close();
    process.exit(0);
};

process.on('SIGINT', stop);
process.on('SIGTERM', stop);

await new Promise(() => {});
