import { copyFile, mkdir } from 'node:fs/promises';
import { dirname, resolve } from 'node:path';

const copies = [
    ['node_modules/@fontsource-variable/inter/files/inter-latin-wght-normal.woff2', 'storage/app/fonts/inter.woff2'],
    ['node_modules/@fontsource-variable/inter/LICENSE', 'storage/app/fonts/OFL-Inter.txt'],
];

for (const [source, target] of copies) {
    await mkdir(dirname(resolve(target)), { recursive: true });
    await copyFile(resolve(source), resolve(target));
}

console.log('Prepared deterministic local font assets.');
