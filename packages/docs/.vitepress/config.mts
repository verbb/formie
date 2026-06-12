import { fileURLToPath } from 'node:url';
import tailwindcss from '@tailwindcss/vite';
import { defineConfig } from 'vitepress';

const formieBrowserSource = fileURLToPath(new URL('../../formie-browser/src', import.meta.url));

export default defineConfig({
    title: 'Formie Frontend',
    description: 'Core package documentation for theming, modules, and framework adapters.',
    base: '/formie/',
    cleanUrls: true,
    appearance: false,
    lastUpdated: true,
    vite: {
        server: {
            port: 5280,
            strictPort: true,
        },
        preview: {
            port: 4280,
            strictPort: true,
        },
        ssr: {
            noExternal: ['@verbb/vitepress-theme'],
        },
        resolve: {
            alias: [
                {
                    find: 'mark.js/src/vanilla.js',
                    replacement: fileURLToPath(new URL('../../../node_modules/mark.js/dist/mark.es6.js', import.meta.url)),
                },
                {
                    find: '@verbb/vitepress-theme',
                    replacement: fileURLToPath(new URL('../../../../verbb-vitepress-theme/src/index.ts', import.meta.url)),
                },
                {
                    find: '@verbb/formie-browser',
                    replacement: `${formieBrowserSource}/index.ts`,
                },
                {
                    find: /^#theme\/(.+\.css)(\?.*)?$/,
                    replacement: `${formieBrowserSource}/css/theme/$1$2`,
                },
                {
                    find: /^#theme-base\/(.*)$/,
                    replacement: `${formieBrowserSource}/css/theme-base/$1`,
                },
                {
                    find: /^#icons\/(.*)$/,
                    replacement: `${formieBrowserSource}/icons/$1`,
                },
                {
                    find: /^#contracts\/(.*)$/,
                    replacement: `${formieBrowserSource}/js/contracts/$1`,
                },
                {
                    find: /^#compatibility\/(.*)$/,
                    replacement: `${formieBrowserSource}/js/compatibility/$1`,
                },
                {
                    find: /^#core\/(.*)$/,
                    replacement: `${formieBrowserSource}/js/core/$1`,
                },
                {
                    find: /^#events\/(.*)$/,
                    replacement: `${formieBrowserSource}/js/events/$1`,
                },
                {
                    find: /^#modules\/(.*)$/,
                    replacement: `${formieBrowserSource}/js/modules/$1`,
                },
                {
                    find: /^#theme\/(.*)$/,
                    replacement: `${formieBrowserSource}/js/theme/$1`,
                },
                {
                    find: /^#submit\/(.*)$/,
                    replacement: `${formieBrowserSource}/js/submit/$1`,
                },
                {
                    find: /^#transport\/(.*)$/,
                    replacement: `${formieBrowserSource}/js/transport/$1`,
                },
                {
                    find: /^#utils\/(.*)$/,
                    replacement: `${formieBrowserSource}/js/utils/$1`,
                },
                {
                    find: /^#validation\/(.*)$/,
                    replacement: `${formieBrowserSource}/js/validation/$1`,
                },
            ],
        },
        plugins: [
            tailwindcss(),
        ],
    },
    head: [
        ['link', { rel: 'icon', type: 'image/svg+xml', href: '/formie/icon.svg' }],
    ],
    themeConfig: {
        siteTitle: 'Formie Frontend',
        logo: '/icon.svg',
        docsTheme: {
            homeLink: '/formie/',
            primary: '#e64d4c',
        },
        nav: [
            { text: 'Browser', link: '/browser/' },
            { text: 'React', link: '/react/' },
            { text: 'Vue', link: '/vue/' },
            { text: 'Web Components', link: '/web-components/' },
        ],
        sidebar: {
            '/browser/': [
                {
                    text: 'Getting Started',
                    items: [
                        { text: 'Overview', link: '/browser/' },
                        { text: 'JavaScript events', link: '/browser/behavior/javascript-events' },
                        { text: 'Submission handling', link: '/browser/behavior/submission-handling' },
                    ],
                },
                {
                    text: 'Advanced',
                    items: [
                        { text: 'Custom client', link: '/browser/behavior/custom-client' },
                        { text: 'Manual initialization', link: '/browser/behavior/manual-initialization' },
                        { text: 'Migrating from Formie Plugin', link: '/browser/behavior/migrating-from-formie-plugin' },
                    ],
                },
                {
                    text: 'Validation',
                    items: [
                        { text: 'Overview', link: '/browser/validation/' },
                        { text: 'Built-in rules', link: '/browser/validation/built-in-rules' },
                        { text: 'Build a custom validator', link: '/browser/validation/build-a-custom-validator' },
                    ],
                },
                {
                    text: 'UI reference',
                    items: [
                        {
                            text: 'Fields',
                            collapsed: true,
                            items: [
                                { text: 'Agree', link: '/browser/ui-reference/fields/agree' },
                                { text: 'Address', link: '/browser/ui-reference/fields/address' },
                                { text: 'Calculations', link: '/browser/ui-reference/fields/calculations' },
                                { text: 'Categories', link: '/browser/ui-reference/fields/categories' },
                                { text: 'Checkboxes', link: '/browser/ui-reference/fields/checkboxes' },
                                { text: 'Date', link: '/browser/ui-reference/fields/date' },
                                { text: 'Entries', link: '/browser/ui-reference/fields/entries' },
                                { text: 'File Upload', link: '/browser/ui-reference/fields/file-upload' },
                                { text: 'Hidden', link: '/browser/ui-reference/fields/hidden' },
                                { text: 'Multi Line Text', link: '/browser/ui-reference/fields/multi-line-text' },
                                { text: 'Payment', link: '/browser/ui-reference/fields/payment' },
                                { text: 'Phone', link: '/browser/ui-reference/fields/phone' },
                                { text: 'Radio', link: '/browser/ui-reference/fields/radio' },
                                { text: 'Recipients', link: '/browser/ui-reference/fields/recipients' },
                                { text: 'Repeater', link: '/browser/ui-reference/fields/repeater' },
                                { text: 'Signature', link: '/browser/ui-reference/fields/signature' },
                                { text: 'Single Line Text', link: '/browser/ui-reference/fields/single-line-text' },
                                { text: 'Summary', link: '/browser/ui-reference/fields/summary' },
                                { text: 'Table', link: '/browser/ui-reference/fields/table' },
                                { text: 'Tags', link: '/browser/ui-reference/fields/tags' },
                            ],
                        },
                        {
                            text: 'Components',
                            collapsed: true,
                            items: [
                                { text: 'Buttons', link: '/browser/ui-reference/components/buttons' },
                                { text: 'Field', link: '/browser/ui-reference/components/field' },
                                { text: 'Form', link: '/browser/ui-reference/components/form' },
                                { text: 'Loading', link: '/browser/ui-reference/components/loading' },
                                { text: 'Messages', link: '/browser/ui-reference/components/messages' },
                                { text: 'Page Navigation', link: '/browser/ui-reference/components/page-navigation' },
                                { text: 'Progress', link: '/browser/ui-reference/components/progress' },
                            ],
                        },
                        { text: 'CSS variables', link: '/browser/ui-reference/css-variables' },
                    ],
                },
                {
                    text: 'Modules',
                    items: [
                        { text: 'Overview', link: '/browser/modules/' },
                        { text: 'Build a custom module', link: '/browser/modules/build-a-custom-module' },
                        {
                            text: 'Field modules',
                            collapsed: true,
                            items: [
                                { text: 'Calculations', link: '/browser/modules/field/calculations' },
                                { text: 'Conditions', link: '/browser/modules/field/conditions' },
                                { text: 'Date picker', link: '/browser/modules/field/date-picker' },
                                { text: 'File upload', link: '/browser/modules/field/file-upload' },
                                { text: 'Upload manager', link: '/browser/modules/field/upload-manager' },
                                { text: 'Phone country', link: '/browser/modules/field/phone-country' },
                                { text: 'Repeater', link: '/browser/modules/field/repeater' },
                                { text: 'Rich text', link: '/browser/modules/field/rich-text' },
                                { text: 'Signature', link: '/browser/modules/field/signature' },
                                { text: 'Summary', link: '/browser/modules/field/summary' },
                                { text: 'Table', link: '/browser/modules/field/table' },
                            ],
                        },
                        {
                            text: 'Address modules',
                            collapsed: true,
                            items: [
                                { text: 'Address Finder', link: '/browser/modules/address/address-finder' },
                                { text: 'Google address', link: '/browser/modules/address/google-address' },
                                { text: 'Loqate', link: '/browser/modules/address/loqate' },
                                { text: 'PlaceKit', link: '/browser/modules/address/place-kit' },
                            ],
                        },
                        {
                            text: 'Captcha modules',
                            collapsed: true,
                            items: [
                                { text: 'CAPTCHA.eu', link: '/browser/modules/captcha/captcha-eu' },
                                { text: 'Friendly Captcha', link: '/browser/modules/captcha/friendly-captcha' },
                                { text: 'hCaptcha', link: '/browser/modules/captcha/hcaptcha' },
                                { text: 'reCAPTCHA', link: '/browser/modules/captcha/recaptcha' },
                                { text: 'Snaptcha', link: '/browser/modules/captcha/snaptcha' },
                                { text: 'Turnstile', link: '/browser/modules/captcha/turnstile' },
                            ],
                        },
                        {
                            text: 'Payment modules',
                            collapsed: true,
                            items: [
                                { text: 'Bpoint', link: '/browser/modules/payment/bpoint' },
                                { text: 'Eway', link: '/browser/modules/payment/eway' },
                                { text: 'GoCardless', link: '/browser/modules/payment/go-cardless' },
                                { text: 'Mollie', link: '/browser/modules/payment/mollie' },
                                { text: 'Moneris', link: '/browser/modules/payment/moneris' },
                                { text: 'Opayo', link: '/browser/modules/payment/opayo' },
                                { text: 'Paddle', link: '/browser/modules/payment/paddle' },
                                { text: 'PayPal', link: '/browser/modules/payment/paypal' },
                                { text: 'PayWay', link: '/browser/modules/payment/payway' },
                                { text: 'Square', link: '/browser/modules/payment/square' },
                                { text: 'Stripe', link: '/browser/modules/payment/stripe' },
                            ],
                        },
                    ],
                },
            ],
            '/react/': [
                {
                    text: 'Getting Started',
                    items: [
                        { text: 'Overview', link: '/react/' },
                        { text: 'Installation', link: '/react/getting-started/installation' },
                    ],
                },
                {
                    text: 'Server-rendered',
                    items: [
                        { text: 'Overview', link: '/react/server-rendered/overview' },
                        { text: 'Styling', link: '/react/server-rendered/styling' },
                    ],
                },
                {
                    text: 'Client-rendered',
                    items: [
                        { text: 'Overview', link: '/react/client-rendered/overview' },
                        { text: 'Component customization', link: '/react/client-rendered/component-customization' },
                    ],
                },
            ],
            '/vue/': [
                {
                    text: 'Getting Started',
                    items: [
                        { text: 'Overview', link: '/vue/' },
                        { text: 'Installation', link: '/vue/getting-started/installation' },
                    ],
                },
                {
                    text: 'Server-rendered',
                    items: [
                        { text: 'Overview', link: '/vue/server-rendered/overview' },
                        { text: 'Styling', link: '/vue/server-rendered/styling' },
                    ],
                },
                {
                    text: 'Client-rendered',
                    items: [
                        { text: 'Overview', link: '/vue/client-rendered/overview' },
                        { text: 'Component customization', link: '/vue/client-rendered/component-customization' },
                    ],
                },
            ],
            '/web-components/': [
                {
                    text: 'Getting Started',
                    items: [
                        { text: 'Overview', link: '/web-components/' },
                        { text: 'Installation', link: '/web-components/getting-started/installation' },
                    ],
                },
                {
                    text: 'HTML Mode',
                    items: [
                        { text: 'Overview', link: '/web-components/html-mode/overview' },
                        { text: 'Styling', link: '/web-components/html-mode/styling' },
                    ],
                },
                {
                    text: 'Component Mode',
                    items: [
                        { text: 'Overview', link: '/web-components/component-mode/overview' },
                        { text: 'Component customization', link: '/web-components/component-mode/component-customization' },
                    ],
                },
            ],
        },
        socialLinks: [
            { icon: 'github', link: 'https://github.com/verbb/formie' },
        ],
        outline: [2, 3],
        lastUpdatedText: 'Last updated',
        search: {
            provider: 'local',
        },
    },
});
