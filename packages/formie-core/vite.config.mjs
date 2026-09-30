import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { defineConfig } from 'vite';

const configDir = dirname(fileURLToPath(import.meta.url));

export default defineConfig({
    build: {
        outDir: 'dist',
        emptyOutDir: true,
        sourcemap: false,
        lib: {
            entry: resolve(configDir, 'src/index.ts'),
            formats: ['es'],
            fileName: 'index',
        },
    },
});
