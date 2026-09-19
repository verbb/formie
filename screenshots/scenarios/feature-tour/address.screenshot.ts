import { defineScreenshotScenario } from '@verbb/craft-screenshots/api';

import { seedFormieAddressForm } from '../../support/fixtures';

let editRoute = '/admin/formie/forms';

export default defineScreenshotScenario({
    id: 'formie-feature-tour-address',
    output: 'feature-tour/formie-address.png',
    route: () => editRoute,
    viewport: { width: 1100, height: 760, deviceScaleFactor: 2 },
    async setup(context) {
        editRoute = (await seedFormieAddressForm(context)).editRoute;
    },
    waitFor: [
        { type: 'loadState', state: 'networkidle' },
        { type: 'selector', selector: '.fui-field-block:not(.fui-submit-block) > .fui-edit-overlay', state: 'visible', timeout: 30000 },
    ],
    steps: [
        { type: 'click', selector: '.fui-field-block:not(.fui-submit-block) > .fui-edit-overlay' },
        { type: 'wait', waitFor: { type: 'selector', selector: '.fui-edit-field-modal', state: 'visible', timeout: 30000 } },
        { type: 'wait', waitFor: { type: 'timeout', ms: 250 } },
    ],
    target: { type: 'selector', selector: '.fui-edit-field-modal .fui-modal-wrap' },
    caption: 'The real Formie Address field settings in Craft 5.',
    intent: 'Show the current field settings rather than recreating the production modal as artwork.',
});
