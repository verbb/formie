import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { defineConfig, loadEnv } from 'vite';
import { getFormieBrowserViteDevAliases } from '@verbb/formie-browser/vite-dev';
import { formieCssLayers } from './css-layers.mjs';

const __dirname = dirname(fileURLToPath(import.meta.url));

const parseServerPort = (value, fallback) => {
    const port = Number.parseInt(value || '', 10);

    return Number.isInteger(port) ? port : fallback;
};

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const useCssLayers = mode === 'layered';
    const devServerPublicUrl = (env.FORMIE_BROWSER_DEV_SERVER_PUBLIC || 'http://localhost:3902/').replace(/\/$/, '');
    const devServerHost = env.FORMIE_BROWSER_DEV_SERVER_HOST || 'localhost';
    const devServerPort = parseServerPort(env.FORMIE_BROWSER_DEV_SERVER_PORT, 3902);
    const previewServerPort = parseServerPort(env.FORMIE_FRONTEND_PREVIEW_PORT, 4392);

    return {
        base: './',
        plugins: [{
            name: 'formie-css-mode',
            configureServer(server) {
                server.middlewares.use('/__formie/css-mode', (_request, response) => {
                    response.setHeader('Content-Type', 'text/plain');
                    response.end(useCssLayers ? 'layered' : 'unlayered');
                });
            },
        }],
        css: {
            postcss: { plugins: useCssLayers ? [] : [formieCssLayers()] },
        },
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
            // Compile inline field CSS too; the npm build already embeds it in JavaScript.
            alias: getFormieBrowserViteDevAliases(),
        },
        optimizeDeps: { exclude: ['@verbb/formie-browser'] },
        build: {
            outDir: useCssLayers ? 'dist/layered' : 'dist',
            emptyOutDir: true,
            sourcemap: false,
            rollupOptions: {
                input: {
                    formie: resolve(__dirname, 'src/js/formie.ts'),
                    'formie-styles': resolve(__dirname, 'src/css/formie.css'),
                    'formie-base': resolve(__dirname, 'src/css/formie-base.css'),
                    'formie-theme': resolve(__dirname, 'src/css/formie-theme.css'),
                },
                output: {
                    entryFileNames: 'js/[name].js',
                    chunkFileNames: 'js/chunks/[name]-[hash].js',
                    assetFileNames: (assetInfo) => {
                        const assetName = assetInfo.names?.[0] || assetInfo.name || '';

                        if (assetName === 'formie-styles.css') {
                            return 'css/formie.css';
                        }

                        if (assetName === 'formie-base.css' || assetName === 'formie-theme.css') {
                            return 'css/[name][extname]';
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
