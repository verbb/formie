import { defineScreenshotScenario } from '@verbb/craft-screenshots/api';

import { seedFormieSyncedField } from '../../support/fixtures';

let editRoute = '/admin/formie/forms';

export default defineScreenshotScenario({
    id: 'formie-feature-tour-synced-field',
    output: 'feature-tour/formie-synced-field.png',
    route: () => editRoute,
    viewport: { width: 1000, height: 620, deviceScaleFactor: 2 },
    async setup(context) {
        editRoute = (await seedFormieSyncedField(context)).editRoute;
    },
    waitFor: [
        { type: 'loadState', state: 'networkidle' },
        { type: 'selector', selector: '.fui-field-block .fui-field-synced', state: 'visible', timeout: 30000 },
    ],
    steps: [
        { type: 'wait', waitFor: { type: 'timeout', ms: 200 } },
    ],
    target: {
        type: 'selector',
        selector: '.fui-field-block:has(.fui-field-synced)',
    },
    caption: 'A real synced Email Address field in Formie’s Craft 5 builder.',
    intent: 'Show Formie’s own synced-field state in the current builder.',
});
