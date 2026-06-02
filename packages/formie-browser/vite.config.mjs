import fs from 'node:fs/promises';
import { resolve } from 'node:path';
import { defineConfig } from 'vite';

const copyFormieCss = () => ({
    name: 'copy-formie-css',
    async closeBundle() {
        await fs.cp(resolve(__dirname, 'src/css'), resolve(__dirname, 'dist/css'), { recursive: true });
    },
});

export default defineConfig({
    build: {
        outDir: 'dist',
        emptyOutDir: true,
        minify: false,
        cssMinify: true,
        sourcemap: false,
        cssCodeSplit: true,
        lib: {
            entry: resolve(__dirname, 'src/index.ts'),
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
