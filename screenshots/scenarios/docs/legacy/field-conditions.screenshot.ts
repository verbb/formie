import { defineScreenshotScenario } from '@verbb/craft-screenshots/api';

import { seedFormieConditionalFieldForm } from '../../../support/fixtures';

let editRoute = '/admin/formie/forms';

export default defineScreenshotScenario({
    id: 'formie-docs-legacy-field-conditions',
    output: 'docs/legacy/field-conditions.png',
    route: () => editRoute,
    viewport: { width: 1100, height: 760, deviceScaleFactor: 2 },
    async setup(context) {
        editRoute = (await seedFormieConditionalFieldForm(context)).editRoute;
    },
    waitFor: [
        { type: 'loadState', state: 'networkidle' },
        { type: 'selector', selector: '.fui-field-block:not(.fui-submit-block) > .fui-edit-overlay', state: 'visible', timeout: 30000 },
    ],
    steps: [
        { type: 'click', selector: ':nth-match(.fui-field-block:not(.fui-submit-block) > .fui-edit-overlay, 1)' },
        { type: 'wait', waitFor: { type: 'selector', selector: '.fui-edit-field-modal', state: 'visible', timeout: 30000 } },
        { type: 'click', selector: '.fui-edit-field-modal .fui-tab-item:has-text("Conditions")' },
        { type: 'wait', waitFor: { type: 'text', text: 'Enable Conditions', selector: '.fui-edit-field-modal' } },
        { type: 'wait', waitFor: { type: 'timeout', ms: 250 } },
    ],
    target: { type: 'selector', selector: '.fui-edit-field-modal .fui-modal-wrap' },
    caption: 'Formie’s field-visibility condition editor with a persisted email-domain rule.',
    intent: 'Retain the legacy field-conditions subject using genuine Formie rule state.',
});
