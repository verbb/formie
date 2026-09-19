import { defineScreenshotScenario } from '@verbb/craft-screenshots/api';

import { seedFormiePaymentForm } from '../../support/fixtures';

let editRoute = '/admin/formie/forms';

export default defineScreenshotScenario({
    id: 'formie-feature-tour-payments',
    output: 'feature-tour/formie-payments.png',
    route: () => editRoute,
    viewport: { width: 1000, height: 620, deviceScaleFactor: 2 },
    async setup(context) {
        editRoute = (await seedFormiePaymentForm(context)).editRoute;
    },
    waitFor: [
        { type: 'loadState', state: 'networkidle' },
        { type: 'text', text: 'Payment', timeout: 30000 },
        { type: 'text', text: 'Card number', timeout: 30000 },
    ],
    steps: [
        { type: 'wait', waitFor: { type: 'timeout', ms: 200 } },
    ],
    target: {
        type: 'selector',
        selector: '.fui-field-block:not(.fui-submit-block)',
    },
    caption: 'A real Payment field in Formie’s Craft 5 builder.',
    intent: 'Show the native Payment field preview cropped exactly to its white field container.',
});
