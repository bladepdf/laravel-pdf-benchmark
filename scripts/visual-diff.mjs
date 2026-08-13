import { mkdir, readFile, writeFile } from 'node:fs/promises';
import { dirname } from 'node:path';
import pixelmatch from 'pixelmatch';
import { PNG } from 'pngjs';

const [referencePath, targetPath, diffPath, overlayPath, cropsJson = '[]'] = process.argv.slice(2);
if (!referencePath || !targetPath || !diffPath || !overlayPath) {
    throw new Error('Usage: visual-diff.mjs reference.png target.png diff.png overlay.png [crops-json]');
}

const [reference, target] = await Promise.all([
    PNG.sync.read(await readFile(referencePath)),
    PNG.sync.read(await readFile(targetPath)),
]);
const width = Math.max(reference.width, target.width);
const height = Math.max(reference.height, target.height);

function padded(source) {
    const output = new PNG({ width, height, fill: true });
    output.data.fill(255);
    PNG.bitblt(source, output, 0, 0, source.width, source.height, 0, 0);
    return output;
}

const referencePadded = padded(reference);
const targetPadded = padded(target);
const diff = new PNG({ width, height });
const differentPixels = pixelmatch(referencePadded.data, targetPadded.data, diff.data, width, height, {
    threshold: 0.1,
    includeAA: false,
    alpha: 0.55,
    diffColor: [222, 49, 81],
});

const overlay = new PNG({ width, height });
for (let index = 0; index < overlay.data.length; index += 4) {
    overlay.data[index] = Math.round(referencePadded.data[index] * 0.5 + targetPadded.data[index] * 0.5);
    overlay.data[index + 1] = Math.round(referencePadded.data[index + 1] * 0.5 + targetPadded.data[index + 1] * 0.5);
    overlay.data[index + 2] = Math.round(referencePadded.data[index + 2] * 0.5 + targetPadded.data[index + 2] * 0.5);
    overlay.data[index + 3] = 255;
}

await mkdir(dirname(diffPath), { recursive: true });
await Promise.all([
    writeFile(diffPath, PNG.sync.write(diff)),
    writeFile(overlayPath, PNG.sync.write(overlay)),
]);

const crops = JSON.parse(cropsJson);
const features = {};
for (const feature of crops) {
    const [nx, ny, nw, nh] = feature.crop;
    const x = Math.max(0, Math.floor(nx * width));
    const y = Math.max(0, Math.floor(ny * height));
    const cropWidth = Math.max(1, Math.min(width - x, Math.floor(nw * width)));
    const cropHeight = Math.max(1, Math.min(height - y, Math.floor(nh * height)));
    const referenceCrop = new PNG({ width: cropWidth, height: cropHeight });
    const targetCrop = new PNG({ width: cropWidth, height: cropHeight });
    PNG.bitblt(referencePadded, referenceCrop, x, y, cropWidth, cropHeight, 0, 0);
    PNG.bitblt(targetPadded, targetCrop, x, y, cropWidth, cropHeight, 0, 0);
    const featureDiff = new PNG({ width: cropWidth, height: cropHeight });
    const count = pixelmatch(referenceCrop.data, targetCrop.data, featureDiff.data, cropWidth, cropHeight, { threshold: 0.1, includeAA: false });
    features[feature.slug] = {
        different_pixels: count,
        total_pixels: cropWidth * cropHeight,
        difference_ratio: count / (cropWidth * cropHeight),
        crop_pixels: [x, y, cropWidth, cropHeight],
    };
}

console.log(JSON.stringify({
    reference_dimensions: [reference.width, reference.height],
    target_dimensions: [target.width, target.height],
    dimensions_match: reference.width === target.width && reference.height === target.height,
    different_pixels: differentPixels,
    total_pixels: width * height,
    difference_ratio: differentPixels / (width * height),
    antialiasing: { threshold: 0.1, include_aa: false },
    features,
}));
