const mix = require('laravel-mix');
const path = require('path');

// Mix plugins
const autoprefixer = require('autoprefixer');
const eslint = require('laravel-mix-eslint-config');
const polyfill = require('laravel-mix-polyfill');
const imagemin = require('laravel-mix-imagemin');

const assetsPath = './src/web/assets';

const postcssCustomProperties = require('postcss-custom-properties');

// Set the public path
mix.setPublicPath(assetsPath);


//
// Forms
//

// Setup and configure Sass
mix.sass(assetsPath + '/forms/src/scss/style.scss', assetsPath + '/forms/dist/css');

// Setup and configure JS
mix.js(assetsPath + '/forms/src/js/main.js', assetsPath + '/forms/dist/js');

// Directly copy over some folders
mix.copy(assetsPath + '/forms/src/fonts', assetsPath + '/forms/dist/fonts');



//
// Front-End
//

// Setup and configure Sass
mix.sass(assetsPath + '/frontend/src/scss/formie-base.scss', assetsPath + '/frontend/dist/css');
mix.sass(assetsPath + '/frontend/src/scss/formie-theme.scss', assetsPath + '/frontend/dist/css');
mix.sass(assetsPath + '/frontend/src/scss/fields/phone-country.scss', assetsPath + '/frontend/dist/css/fields');
mix.sass(assetsPath + '/frontend/src/scss/fields/tags.scss', assetsPath + '/frontend/dist/css/fields');

// Setup and configure JS
mix.js(assetsPath + '/frontend/src/js/formie.js', assetsPath + '/frontend/dist/js');
mix.js(assetsPath + '/frontend/src/js/fields/file-upload.js', assetsPath + '/frontend/dist/js/fields');
mix.js(assetsPath + '/frontend/src/js/fields/phone-country.js', assetsPath + '/frontend/dist/js/fields');
mix.js(assetsPath + '/frontend/src/js/fields/repeater.js', assetsPath + '/frontend/dist/js/fields');
mix.js(assetsPath + '/frontend/src/js/fields/table.js', assetsPath + '/frontend/dist/js/fields');
mix.js(assetsPath + '/frontend/src/js/fields/tags.js', assetsPath + '/frontend/dist/js/fields');
mix.js(assetsPath + '/frontend/src/js/fields/checkbox-radio.js', assetsPath + '/frontend/dist/js/fields');
mix.js(assetsPath + '/frontend/src/js/fields/text-limit.js', assetsPath + '/frontend/dist/js/fields');
mix.js(assetsPath + '/frontend/src/js/fields/rich-text.js', assetsPath + '/frontend/dist/js/fields');
mix.js(assetsPath + '/frontend/src/js/fields/conditions.js', assetsPath + '/frontend/dist/js/fields');
mix.js(assetsPath + '/frontend/src/js/fields/date-picker.js', assetsPath + '/frontend/dist/js/fields');
mix.js(assetsPath + '/frontend/src/js/fields/hidden.js', assetsPath + '/frontend/dist/js/fields');
mix.js(assetsPath + '/frontend/src/js/fields/summary.js', assetsPath + '/frontend/dist/js/fields');
mix.js(assetsPath + '/frontend/src/js/fields/signature.js', assetsPath + '/frontend/dist/js/fields');
mix.js(assetsPath + '/frontend/src/js/fields/calculations.js', assetsPath + '/frontend/dist/js/fields');


//
// Integrations
//

// Setup and configure JS
mix.js(assetsPath + '/addressproviders/src/js/algolia-places.js', assetsPath + '/addressproviders/dist/js');
mix.js(assetsPath + '/addressproviders/src/js/google-address.js', assetsPath + '/addressproviders/dist/js');
mix.js(assetsPath + '/addressproviders/src/js/address-finder.js', assetsPath + '/addressproviders/dist/js');
mix.js(assetsPath + '/addressproviders/src/js/loqate.js', assetsPath + '/addressproviders/dist/js');
mix.js(assetsPath + '/captchas/src/js/recaptcha-v2-checkbox.js', assetsPath + '/captchas/dist/js');
mix.js(assetsPath + '/captchas/src/js/recaptcha-v2-invisible.js', assetsPath + '/captchas/dist/js');
mix.js(assetsPath + '/captchas/src/js/recaptcha-v3.js', assetsPath + '/captchas/dist/js');
mix.js(assetsPath + '/captchas/src/js/recaptcha-enterprise.js', assetsPath + '/captchas/dist/js');
mix.js(assetsPath + '/captchas/src/js/javascript.js', assetsPath + '/captchas/dist/js');
mix.js(assetsPath + '/captchas/src/js/hcaptcha.js', assetsPath + '/captchas/dist/js');


//
// General CP
//

// Setup and configure JS
mix.js(assetsPath + '/cp/src/js/formie-cp.js', assetsPath + '/cp/dist/js');
mix.js(assetsPath + '/cp/src/js/integration-settings.js', assetsPath + '/cp/dist/js');

// Setup and configure Sass
mix.sass(assetsPath + '/cp/src/scss/formie-cp.scss', assetsPath + '/cp/dist/css');


//
// Widgets
//

// Setup and configure JS
mix.js(assetsPath + '/widgets/src/js/formie-widgets.js', assetsPath + '/widgets/dist/js');

// Setup and configure Sass
mix.sass(assetsPath + '/widgets/src/scss/formie-widgets.scss', assetsPath + '/widgets/dist/css');



// Optimise images and SVGs
mix.imagemin([
    { from: assetsPath + '/forms/src/img', to: 'forms/dist/img' },
    { from: assetsPath + '/addressproviders/src/img', to: 'addressproviders/dist/img' },
    { from: assetsPath + '/captchas/src/img', to: 'captchas/dist/img' },
    { from: assetsPath + '/elements/src/img', to: 'elements/dist/img' },
    { from: assetsPath + '/emailmarketing/src/img', to: 'emailmarketing/dist/img' },
    { from: assetsPath + '/crm/src/img', to: 'crm/dist/img' },
    { from: assetsPath + '/webhooks/src/img', to: 'webhooks/dist/img' },
    { from: assetsPath + '/miscellaneous/src/img', to: 'miscellaneous/dist/img' },
], {}, {
    gifsicle: { interlaced: true },
    mozjpeg: { progressive: true, arithmetic: false },
    optipng: { optimizationLevel: 3 }, // Lower number = speedier/reduced compression
    svgo: {
        plugins: [
            { convertColors: { currentColor: false } },
            { removeDimensions: false },
            { removeViewBox: false },
            { cleanupIDs: false },
        ],
    },
});


// Setup additional CSS-related options including Tailwind and any other PostCSS items
mix.options({
    // Disable processing css urls for speed
    processCssUrls: false,
    postCss: [
        // PostCSS plugins
        autoprefixer(),
        postcssCustomProperties(),
    ],
});

// Setup JS-linting
mix.eslint({
    exclude: [
        'node_modules',
        path.resolve(__dirname, assetsPath + '/forms/src/js/vendor'),
    ],
    options: {
        fix: true,
        cache: false,
    },
});

// Setup some aliases
mix.webpackConfig({
    resolve: {
        alias: {
            // Form Utils
            '@utils': path.resolve(__dirname, assetsPath + '/forms/src/js/utils'),

            // Local vendor modules
            '@vuedraggable': path.resolve(__dirname, assetsPath + '/forms/src/js/vendor/vuedraggable'),
            '@accessible-tabs': path.resolve(__dirname, assetsPath + '/forms/src/js/vendor/vue-accessible-tabs'),
        }
    },
    externals: {
        vue: 'Vue',
    }
});

// Always allow versioning of assets
mix.version();

if (mix.inProduction()) {
    // Add polyfills
    mix.polyfill({
        enabled: true,
        useBuiltIns: 'usage', // Only add a polyfill when a feature is used
        targets: false, // "false" makes the config use .browserslistrc file
        corejs: 3,
        debug: false, // "true" to check which polyfills are being used
    });
}
