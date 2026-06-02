import { resolve } from 'node:path';
import { defineConfig } from 'vite';

export default defineConfig({
    build: {
        outDir: 'dist',
        emptyOutDir: true,
        sourcemap: false,
        lib: {
            entry: resolve(__dirname, 'src/index.ts'),
            formats: ['es'],
            fileName: 'index',
        },
        rollupOptions: {
            external: ['react', 'react-dom', '@verbb/formie-browser', '@verbb/formie-core'],
        },
    },
});
