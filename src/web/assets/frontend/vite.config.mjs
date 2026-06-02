import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { defineConfig, loadEnv } from 'vite';
import { getFormieBrowserViteDevAliases } from '@verbb/formie-browser/vite-dev';

const __dirname = dirname(fileURLToPath(import.meta.url));

const parseServerPort = (value, fallback) => {
    const port = Number.parseInt(value || '', 10);

    return Number.isInteger(port) ? port : fallback;
};

export default defineConfig(({ mode, command }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const useFormieBrowserSource =
        command === 'serve' && mode === 'development';
    const devServerPublicUrl = (env.FORMIE_FRONTEND_DEV_SERVER_PUBLIC || 'http://localhost:3902/').replace(/\/$/, '');
    const devServerHost = env.FORMIE_FRONTEND_DEV_SERVER_HOST || 'localhost';
    const devServerPort = parseServerPort(env.FORMIE_FRONTEND_DEV_SERVER_PORT, 3902);
    const previewServerPort = parseServerPort(env.FORMIE_FRONTEND_PREVIEW_PORT, 4392);

    return {
        base: './',
        server: {
            cors: true,
            host: devServerHost,
            port: devServerPort,
            strictPort: true,
            // Ensure CSS-rewritten asset URLs (for example url() inside imported CSS)
            // point at the same public dev origin that PHP uses for the Vite entry.
            origin: devServerPublicUrl,
        },
        preview: {
            cors: true,
            host: devServerHost,
            port: previewServerPort,
            strictPort: true,
        },
        resolve: {
            preserveSymlinks: true,
            alias: useFormieBrowserSource ? getFormieBrowserViteDevAliases() : [],
        },
        optimizeDeps: useFormieBrowserSource
            ? { exclude: ['@verbb/formie-browser'] }
            : undefined,
        build: {
            outDir: 'dist',
            emptyOutDir: true,
            sourcemap: false,
            rollupOptions: {
                input: {
                    formie: resolve(__dirname, 'src/js/formie.ts'),
                },
                output: {
                    entryFileNames: 'js/[name].js',
                    chunkFileNames: 'js/chunks/[name]-[hash].js',
                    assetFileNames: (assetInfo) => {
                        const assetName = assetInfo.names?.[0] || assetInfo.name || '';

                        if (assetName === 'formie.css') {
                            return 'css/formie.css';
                        }

                        if (assetName.endsWith('.css')) {
                            return 'css/[name]-[hash][extname]';
                        }

                        return 'assets/[name]-[hash][extname]';
                    },
                },
            },
        },
    };
});
