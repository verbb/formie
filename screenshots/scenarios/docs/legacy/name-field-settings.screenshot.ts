import { defineScreenshotScenario } from '@verbb/craft-screenshots/api';

import { seedFormieContactForm } from '../../../support/fixtures';

let editRoute = '/admin/formie/forms';

export default defineScreenshotScenario({
    id: 'formie-docs-legacy-name-field-settings',
    output: 'docs/legacy/name-field-settings.png',
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
        { type: 'wait', waitFor: { type: 'timeout', ms: 250 } },
    ],
    target: { type: 'selector', selector: '.fui-edit-field-modal .fui-modal-wrap' },
    caption: 'The real Formie Name field settings in the Craft 5 builder.',
    intent: 'Retain the legacy Name-field configuration subject with current Formie controls and data.',
});
