import path from 'path';

// Vite Plugins
import VuePlugin from '@vitejs/plugin-vue';
import EslintPlugin from 'vite-plugin-eslint';

// Rollup Plugins
import { nodeResolve } from '@rollup/plugin-node-resolve';
import AnalyzePlugin from 'rollup-plugin-analyzer';

// Custom (for the moment)
import ImageminCopy from './src/vite-plugins/imagemin-copy';
import StaticCopy from './src/vite-plugins/static-copy';

export default ({ command }) => ({
    build: {
        outDir: './dist',
        emptyOutDir: true,
        manifest: false,
        sourcemap: true,
        rollupOptions: {
            input: {
                'formie-cp': 'src/js/formie-cp.js',
                'formie-widgets': 'src/js/formie-widgets.js',
            },
            output: {
                entryFileNames: 'js/[name].js',
                chunkFileNames: 'js/[name].js',
                assetFileNames: 'css/[name].[ext]',
            },
        },
    },

    plugins: [
        // Custom plugins (for the moment)
        ImageminCopy,

        // Keep JS looking good with eslint
        // https://github.com/gxmari007/vite-plugin-eslint
        EslintPlugin({
            cache: false,
            fix: true,
            include: './src/web/assets/**/*.{js,vue}',
            exclude: './src/web/assets/field/src/js/vendor/**/*.{js,vue}',
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
                path.resolve('./node_modules'),
            ],
        }),
    ],

    resolve: {
        alias: {
            // Vue 3 doesn't support the template compiler out of the box
            'vue': 'vue/dist/vue.esm-bundler.js',
        },
    },

    // Add in any components to optimise them early.
    optimizeDeps: {
        include: [
            'vue',
        ],
    },
});
