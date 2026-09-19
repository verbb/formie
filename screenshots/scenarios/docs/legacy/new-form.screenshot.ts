import { defineScreenshotScenario } from '@verbb/craft-screenshots/api';

export default defineScreenshotScenario({
    id: 'formie-docs-legacy-new-form',
    output: 'docs/legacy/new-form.png',
    route: '/admin/formie/forms/new',
    viewport: { width: 1024, height: 720, deviceScaleFactor: 2 },
    waitFor: [
        { type: 'loadState', state: 'networkidle' },
        { type: 'text', text: 'Create your form', timeout: 30000 },
        { type: 'selector', selector: '#fui-new-form', state: 'visible', timeout: 30000 },
    ],
    steps: [
        { type: 'fill', selector: '#title', value: 'Project Enquiry' },
        { type: 'wait', waitFor: { type: 'timeout', ms: 250 } },
    ],
    target: { type: 'selector', selector: '#fui-new-form .fui-start-wrap' },
    caption: 'Formie’s current new-form screen with a representative form name.',
    intent: 'Retain the legacy form-creation subject without adding it to the marketing feature page.',
});
