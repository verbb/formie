import fsPromises from 'node:fs/promises';
import path from 'node:path';
import { defineConfig, loadEnv } from 'vite';

import { copyAndOptimizeIcons } from './scripts/build-icons.mjs';

// Vite Plugins
import ReactPlugin from '@vitejs/plugin-react';
import TailwindPlugin from '@tailwindcss/vite';
import TailwindShadowDOM from 'vite-plugin-tailwind-shadowdom';

const cpBundleDirectories = {
    'formie-widgets': 'widgets',
    'formie-forms-index': 'forms',
    'formie-submissions': 'submissions',
    'formie-sent-notifications': 'sent-notifications',
    'formie-plugin-settings': 'plugin-settings',
    'formie-defaults': 'defaults',
    'formie-field-palette': 'field-palette',
    'formie-form-group-settings': 'form-group-settings',
    'formie-reports': 'reports',
};

const widgetVendorFiles = [
    'Chart.bundle.min.js',
    'moment-with-locales.min.js',
    'chartjs-adapter-moment.min.js',
    'deepmerge.min.js',
];

const getCpBundleDirectory = (bundleName) => {
    return cpBundleDirectories[bundleName] ?? null;
};

const copyWidgetsVendorFiles = () => ({
    name: 'copy-widgets-vendor-files',
    apply: 'build',
    async closeBundle() {
        const sourceDir = path.resolve('./src/widgets/js/vendor');
        const destinationDir = path.resolve('./dist/widgets/js/vendor');

        await fsPromises.mkdir(destinationDir, { recursive: true });

        await Promise.all(widgetVendorFiles.map((fileName) => {
            return fsPromises.copyFile(path.join(sourceDir, fileName), path.join(destinationDir, fileName));
        }));
    },
});

const copyIntegrationIcons = () => ({
    name: 'copy-integration-icons',
    apply: 'build',
    async closeBundle() {
        await copyAndOptimizeIcons();
    },
});

const parseServerPort = (value, fallback) => {
    const port = Number.parseInt(value || '', 10);

    return Number.isInteger(port) ? port : fallback;
};

const createManualChunkName = (id) => {
    if (!id.includes('node_modules')) {
        return null;
    }

    if (id.includes('/node_modules/@verbb/formie-browser/')) {
        return 'formie-browser';
    }

    // Kit React facades + WC components + register must share one chunk.
    // Splitting them produced reciprocal import / dynamicImport cycles:
    // - static: PHP ManifestHelper hangs (no visited set)
    // - dynamic: browser module graph deadlocks; CP spinner never clears
    if (
        (
            id.includes('plugin-kit-web')
            && (
                id.includes('/components/')
                || id.includes('/register')
                || id.includes('/plugin-kit')
            )
        )
        || id.includes('/node_modules/@verbb/plugin-kit-react/')
    ) {
        return 'plugin-kit';
    }

    if (id.includes('/node_modules/@verbb/plugin-kit-forms/')) {
        return 'plugin-kit-forms';
    }

    if (id.includes('/node_modules/@verbb/plugin-kit-core/')) {
        return 'plugin-kit-core';
    }

    if (id.includes('/node_modules/lit/')) {
        return 'lit';
    }

    if (
        id.includes('/node_modules/@dnd-kit/') ||
        id.includes('/node_modules/@preact/signals-core/')
    ) {
        return 'dndkit';
    }

    if (
        id.includes('/node_modules/react/') ||
        id.includes('/node_modules/react-dom/')
    ) {
        return 'react-vendor';
    }

    if (
        id.includes('/node_modules/lodash-es/') ||
        id.includes('/node_modules/jexl/') ||
        id.includes('/node_modules/flat/')
    ) {
        return 'data-utils';
    }

    return null;
};

export default defineConfig(async ({ command, mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const devServerPublicUrl = (env.FORMIE_CP_DEV_SERVER_PUBLIC || 'http://localhost:3900/').replace(/\/$/, '');
    const devServerHost = env.FORMIE_CP_DEV_SERVER_HOST || 'localhost';
    const devServerPort = parseServerPort(env.FORMIE_CP_DEV_SERVER_PORT, 3900);
    const previewServerPort = parseServerPort(env.FORMIE_CP_PREVIEW_PORT, 4390);
    const hmrProtocol = env.FORMIE_CP_DEV_SERVER_HMR_PROTOCOL || 'ws';

    // Kit packages come from npm. Do not force-alias `@tiptap/*` / `@codemirror/*` —
    // plugin-kit-react nests matching TipTap versions, and aliasing an older core
    // breaks `@tiptap/react` (e.g. missing `isNodeViewSelected`).
    const optimizeDepsInclude = [
        'lodash-es',
        'react',
        'react-dom',
        'lit',
        '@verbb/plugin-kit-core',
        '@verbb/plugin-kit-forms',
        '@verbb/plugin-kit-react',
        'jexl',
    ];

    return {
        // CP production bundles are published under Craft's cpresources path, so
        // build with a relative base to keep lazy chunks resolving beside the
        // published entrypoint instead of hard-coding `/dist/...`.
        base: '',

        esbuild: {
            jsx: 'automatic',
        },

        build: {
            outDir: './dist',
            emptyOutDir: true,
            manifest: 'manifest.json',
            sourcemap: false,
            chunkSizeWarningLimit: 3500,
            rollupOptions: {
                input: {
                    'formie-form-builder': path.resolve('./src/form-builder/formie-form-builder.js'),
                    'formie-forms-index': path.resolve('./src/forms/js/formie-forms-index.js'),
                    'formie-new-form': path.resolve('./src/new-form/formie-new-form.js'),
                    'formie-integration-connect': path.resolve('./src/integration-connect/formie-integration-connect.js'),
                    'formie-widgets': path.resolve('./src/widgets/js/formie-widgets.js'),
                    'formie-submissions': path.resolve('./src/submissions/js/formie-submissions.js'),
                    'formie-sent-notifications': path.resolve('./src/sent-notifications/js/formie-sent-notifications.js'),
                    'formie-plugin-settings': path.resolve('./src/plugin-settings/js/formie-plugin-settings.js'),
                    'formie-defaults': path.resolve('./src/defaults/formie-defaults.js'),
                    'formie-field-palette': path.resolve('./src/field-palette/formie-field-palette.js'),
                    'formie-form-group-settings': path.resolve('./src/form-group-settings/formie-form-group-settings.js'),
                    'formie-reports': path.resolve('./src/reports/formie-reports.js'),
                },
                output: {
                    entryFileNames: (chunkInfo) => {
                        const bundleDirectory = getCpBundleDirectory(chunkInfo.name);

                        if (bundleDirectory) {
                            return `${bundleDirectory}/js/[name].js`;
                        }

                        return 'assets/[name]-[hash].js';
                    },
                    chunkFileNames: 'assets/[name]-[hash].js',
                    assetFileNames: (assetInfo) => {
                        const assetFileName = assetInfo.names?.[0] ?? assetInfo.name ?? '';
                        const assetBaseName = path.basename(assetFileName, path.extname(assetFileName));
                        const bundleDirectory = getCpBundleDirectory(assetBaseName);

                        if (bundleDirectory) {
                            return `${bundleDirectory}/css/[name][extname]`;
                        }

                        return 'assets/[name]-[hash][extname]';
                    },
                    sourcemapExcludeSources: true,
                    manualChunks: createManualChunkName,
                },
            },
        },

        // Optional plugin-local HMR only — Craft must set FORMIE_USE_VITE_DEV_SERVER=true.
        // Kit comes from npm — bump @verbb/plugin-kit-* and reinstall to pick up kit changes.
        server: {
            origin: devServerPublicUrl,
            host: devServerHost,
            port: devServerPort,
            strictPort: true,
            cors: true,
            hmr: {
                // The CP can run behind local HTTPS proxies, so keep HMR explicit.
                protocol: hmrProtocol,
            },
        },

        preview: {
            host: devServerHost,
            port: previewServerPort,
            strictPort: true,
            cors: true,
        },

        plugins: [
            // React support
            // https://github.com/vitejs/vite-plugin-react
            ReactPlugin({
                jsxRuntime: 'automatic',
            }),

            // Copy legacy vendor files expected by the widgets asset bundle.
            copyWidgetsVendorFiles(),

            // Integration sidebar icons are static SVGs served from dist/icons/.
            copyIntegrationIcons(),

            // Tailwind CSS
            // https://github.com/tailwindlabs/tailwindcss-vite
            TailwindPlugin(),

            // Fix Tailwind in ShadowRoot
            // https://github.com/Alletkla/vite-plugin-tailwind-shadowdom
            TailwindShadowDOM(),
        ],

        resolve: {
            alias: [
                { find: '@form-builder', replacement: path.resolve('./src/form-builder') },
                { find: '@new-form', replacement: path.resolve('./src/new-form') },
                { find: '@integration-connect', replacement: path.resolve('./src/integration-connect') },
                { find: '@defaults', replacement: path.resolve('./src/defaults') },
                { find: '@field-palette', replacement: path.resolve('./src/field-palette') },
                { find: '@form-group-settings', replacement: path.resolve('./src/form-group-settings') },
                { find: '@reports', replacement: path.resolve('./src/reports') },
                { find: '@utils', replacement: path.resolve('./src/utils') },

                // React 19 already provides useSyncExternalStore. Some package dependencies
                // still import the legacy shim entry, which Vite resolves to CJS files during dev.
                { find: /^use-sync-external-store\/shim(?:\/index\.js)?$/, replacement: path.resolve('./src/shims/use-sync-external-store-shim.js') },
                { find: /^use-sync-external-store\/shim\/with-selector(?:\.js)?$/, replacement: path.resolve('./src/shims/use-sync-external-store-with-selector.js') },
            ],
            // Keep React / Lit / kit icons on one singleton across the CP graph.
            dedupe: [
                'react',
                'react-dom',
                'zustand',
                'lit',
                '@lit/react',
                '@lit/reactive-element',
                'lit-element',
                'lit-html',
                '@verbb/plugin-kit-icons',
                '@dnd-kit/abstract',
                '@dnd-kit/dom',
                '@dnd-kit/react',
            ],
        },

        optimizeDeps: {
            include: optimizeDepsInclude,
        },

        test: {
            environment: 'node',
            include: ['src/**/*.test.js'],
        },
    };
});
