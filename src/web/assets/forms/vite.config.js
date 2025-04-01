import path from 'path';

// Vite Plugins
import VuePlugin from '@vitejs/plugin-vue';
import EslintPlugin from 'vite-plugin-eslint';

// Rollup Plugins
// import AnalyzePlugin from 'rollup-plugin-analyzer';

export default ({ command }) => ({
    // When building update the destination base
    base: command === 'serve' ? '' : '/dist/',

    build: {
        outDir: './dist',
        emptyOutDir: true,
        manifest: 'manifest.json',
        sourcemap: true,
        rollupOptions: {
            input: {
                'formie-integration-settings': 'src/js/formie-integration-settings.js',
                'formie-form-new': 'src/js/formie-form-new.js',
                'formie-form': 'src/js/formie-form.js',
            },
            output: {
                sourcemapExcludeSources: true,
            },
        },
    },

    server: {
        origin: 'http://localhost:4000',

        hmr: {
            // Using the default `wss` doesn't work on https
            protocol: 'ws',
        },
    },

    plugins: [
        // Keep JS looking good with eslint
        // https://github.com/gxmari007/vite-plugin-eslint
        EslintPlugin({
            cache: false,
            fix: true,
            include: './src/**/*.{js,vue}',
            exclude: './src/js/vendor/**/*.{js,vue}',
        }),

        // Vue 3 support
        // https://github.com/vitejs/vite/tree/main/packages/plugin-vue
        VuePlugin(),

        // Analyze bundle size
        // https://github.com/doesdev/rollup-plugin-analyzer
        // AnalyzePlugin({
        //     summaryOnly: true,
        //     limit: 15,
        // }),
    ],

    resolve: {
        alias: {
            // Allow us to use `@/` in JS, CSS and Twig for ease of development.
            '@': path.resolve('./src'),

            // Allow us to use `@utils/` in JS for misc utilities.
            '@utils': path.resolve('./src/js/utils'),

            // Allow us to use `@components/` in Vue components.
            '@components': path.resolve('./src/js/components'),

            // Allow us to use `@mixins/` in Vue components.
            '@mixins': path.resolve('./src/js/mixins'),

            // Allow us to use `@formkit/` in Vue components.
            '@formkit-components': path.resolve('./src/js/formkit'),

            // Allow us to use `@vendor/` in Vue components.
            '@vendor': path.resolve('./src/js/vendor'),

            // Vue 3 doesn't support the template compiler out of the box
            'vue': 'vue/dist/vue.esm-bundler.js',
        },
    },

    // Add in any components to optimise them early.
    optimizeDeps: {
        include: [
            'lodash-es',
            'vue',
        ],
    },
});
