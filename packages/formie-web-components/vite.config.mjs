import { resolve } from 'node:path';
import { defineConfig } from 'vite';

export default defineConfig({
    build: {
        outDir: 'dist',
        emptyOutDir: true,
        sourcemap: true,
        lib: {
            entry: resolve(__dirname, 'src/index.ts'),
            formats: ['es'],
            fileName: 'index',
        },
        rollupOptions: {
            external: ['@verbb/formie-browser', '@verbb/formie-core'],
        },
    },
});
