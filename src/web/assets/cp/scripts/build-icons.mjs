import path from 'path';
import { fileURLToPath } from 'url';

import fs from 'fs-extra';
import { optimize as optimizeSvg } from 'svgo';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const cpRoot = path.resolve(__dirname, '..');
const sourceIconsDir = path.join(cpRoot, 'src', 'icons');
const distIconsDir = path.join(cpRoot, 'dist', 'icons');

const copyAndOptimizeIcons = async () => {
    if (!await fs.pathExists(sourceIconsDir)) {
        console.warn(`[build-icons] Source directory not found: ${sourceIconsDir}`);
        return;
    }

    await fs.ensureDir(distIconsDir);
    await fs.emptyDir(distIconsDir);

    const files = await fs.readdir(sourceIconsDir, { recursive: true });

    for (const relativeFile of files) {
        const sourcePath = path.join(sourceIconsDir, relativeFile);
        const stat = await fs.stat(sourcePath);

        if (stat.isDirectory()) {
            continue;
        }

        const targetPath = path.join(distIconsDir, relativeFile);
        await fs.ensureDir(path.dirname(targetPath));

        if (sourcePath.endsWith('.svg')) {
            const svgContent = await fs.readFile(sourcePath, 'utf8');
            const optimized = optimizeSvg(svgContent, {
                path: sourcePath,
                multipass: true,
            });

            if ('data' in optimized) {
                await fs.writeFile(targetPath, optimized.data, 'utf8');
                continue;
            }
        }

        await fs.copy(sourcePath, targetPath);
    }

    console.log(`[build-icons] Icons copied and optimized into ${distIconsDir}`);
};

copyAndOptimizeIcons().catch((error) => {
    console.error('[build-icons] Failed to build icons:', error);
    process.exitCode = 1;
});
