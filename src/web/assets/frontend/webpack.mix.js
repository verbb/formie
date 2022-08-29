const mix = require('laravel-mix');
const autoprefixer = require('autoprefixer');
const eslint = require('eslint-webpack-plugin');
const polyfill = require('laravel-mix-polyfill');
const clean = require('laravel-mix-clean');
const postcssCustomProperties = require('postcss-custom-properties');

// CSS
mix.sass('./src/scss/formie-base.scss', 'css');
mix.sass('./src/scss/formie-theme.scss', 'css');
mix.sass('./src/scss/fields/phone-country.scss', 'css/fields');
mix.sass('./src/scss/fields/tags.scss', 'css/fields');

// JS
mix.js('./src/js/formie.js', 'js');
mix.js('./src/js/fields/file-upload.js', 'js/fields');
mix.js('./src/js/fields/phone-country.js', 'js/fields');
mix.js('./src/js/fields/repeater.js', 'js/fields');
mix.js('./src/js/fields/table.js', 'js/fields');
mix.js('./src/js/fields/tags.js', 'js/fields');
mix.js('./src/js/fields/checkbox-radio.js', 'js/fields');
mix.js('./src/js/fields/text-limit.js', 'js/fields');
mix.js('./src/js/fields/rich-text.js', 'js/fields');
mix.js('./src/js/fields/conditions.js', 'js/fields');
mix.js('./src/js/fields/date-picker.js', 'js/fields');
mix.js('./src/js/fields/hidden.js', 'js/fields');
mix.js('./src/js/fields/summary.js', 'js/fields');
mix.js('./src/js/fields/signature.js', 'js/fields');
mix.js('./src/js/fields/calculations.js', 'js/fields');

// Integrations
mix.js('./src/js/address-providers/algolia-places.js', 'js/address-providers');
mix.js('./src/js/address-providers/google-address.js', 'js/address-providers');
mix.js('./src/js/address-providers/address-finder.js', 'js/address-providers');
mix.js('./src/js/address-providers/loqate.js', 'js/address-providers');
mix.js('./src/js/captchas/recaptcha-v2-checkbox.js', 'js/captchas');
mix.js('./src/js/captchas/recaptcha-v2-invisible.js', 'js/captchas');
mix.js('./src/js/captchas/recaptcha-v3.js', 'js/captchas');
mix.js('./src/js/captchas/recaptcha-enterprise.js', 'js/captchas');
mix.js('./src/js/captchas/javascript.js', 'js/captchas');
mix.js('./src/js/captchas/hcaptcha.js', 'js/captchas');
mix.js('./src/js/payments/stripe.js', 'js/payments');
mix.js('./src/js/payments/paypal.js', 'js/payments');
mix.js('./src/js/payments/payway.js', 'js/payments');

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

mix.setPublicPath('dist');

mix.clean();

mix.sourceMaps();

mix.webpackConfig({
    stats: {
        children: true
    },
    plugins: [
        new eslint({
            fix: true,
        }),
    ],
    output: {
        chunkLoadingGlobal: 'formieConfigChunkLoadingGlobal',
    },
});

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
