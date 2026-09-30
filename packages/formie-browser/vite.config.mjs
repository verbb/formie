import fs from 'node:fs/promises';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { defineConfig } from 'vite';

const configDir = dirname(fileURLToPath(import.meta.url));

const copyFormieCss = () => ({
    name: 'copy-formie-css',
    async closeBundle() {
        await fs.cp(resolve(configDir, 'src/css'), resolve(configDir, 'dist/css'), { recursive: true });
    },
});

export default defineConfig({
    build: {
        outDir: 'dist',
        emptyOutDir: true,
        cssMinify: true,
        sourcemap: false,
        cssCodeSplit: true,
        lib: {
            entry: resolve(configDir, 'src/index.ts'),
            formats: ['es'],
            fileName: 'index',
        },
        rollupOptions: {
            output: {
                chunkFileNames: 'chunks/[name]-[hash].js',
                assetFileNames: 'assets/[name]-[hash][extname]',
            },
        },
    },
    plugins: [
        copyFormieCss(),
    ],
});
