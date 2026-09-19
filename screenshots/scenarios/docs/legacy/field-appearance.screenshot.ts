import { defineScreenshotScenario } from '@verbb/craft-screenshots/api';

import { seedFormieContactForm } from '../../../support/fixtures';

let editRoute = '/admin/formie/forms';

export default defineScreenshotScenario({
    id: 'formie-docs-legacy-field-appearance',
    output: 'docs/legacy/field-appearance.png',
    route: () => editRoute,
    viewport: { width: 1100, height: 760, deviceScaleFactor: 2 },
    async setup(context) {
        editRoute = (await seedFormieContactForm(context)).editRoute;
    },
    waitFor: [
        { type: 'loadState', state: 'networkidle' },
        { type: 'selector', selector: '.fui-field-block:not(.fui-submit-block) > .fui-edit-overlay', state: 'visible', timeout: 30000 },
    ],
    steps: [
        { type: 'click', selector: ':nth-match(.fui-field-block:not(.fui-submit-block) > .fui-edit-overlay, 1)' },
        { type: 'wait', waitFor: { type: 'selector', selector: '.fui-edit-field-modal', state: 'visible', timeout: 30000 } },
        { type: 'click', selector: '.fui-edit-field-modal .fui-tab-item:has-text("Appearance")' },
        { type: 'wait', waitFor: { type: 'timeout', ms: 250 } },
    ],
    target: { type: 'selector', selector: '.fui-edit-field-modal .fui-modal-wrap' },
    caption: 'Formie’s real field Appearance settings in Craft 5.',
    intent: 'Retain the legacy field-appearance subject using the current builder modal.',
});
