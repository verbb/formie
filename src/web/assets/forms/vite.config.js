import path from 'path';

// Vite Plugins
import VuePlugin from '@vitejs/plugin-vue';
import EslintPlugin from 'vite-plugin-eslint';

// Rollup Plugins
import { nodeResolve } from '@rollup/plugin-node-resolve';
// import AnalyzePlugin from 'rollup-plugin-analyzer';

export default ({ command }) => ({
    // When building update the destination base
    base: command === 'serve' ? '' : '/dist/',

    build: {
        outDir: './dist',
        emptyOutDir: true,
        manifest: true,
        sourcemap: true,
        rollupOptions: {
            input: {
                'formie-integration-settings': 'src/js/formie-integration-settings.js',
                'formie-form-new': 'src/js/formie-form-new.js',
                'formie-form': 'src/js/formie-form.js',
            },
        },
    },

    server: {
        origin: 'http://localhost:4000',
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
        VuePlugin({
            isProduction: true,
        }),

        // Analyze bundle size
        // https://github.com/doesdev/rollup-plugin-analyzer
        // AnalyzePlugin({
        //     summaryOnly: true,
        //     limit: 15,
        // }),

        // Ensure Vite can find the modules it needs
        // https://github.com/rollup/plugins/tree/master/packages/node-resolve
        nodeResolve({
            moduleDirectories: [
                path.resolve('../../../../node_modules'),
            ],
        }),
    ],

    resolve: {
        alias: {
            // Allow us to use `@/` in JS, CSS and Twig for ease of development.
            '@': path.resolve('./src'),

            // Allow us to use `@utils/` in JS for misc utilities.
            '@utils': path.resolve('./src/js/utils'),

            // Allow us to use `@components/` in Vue components.
            '@components': path.resolve('./src/js/components'),

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
