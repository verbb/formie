import { defineScreenshotScenario } from '@verbb/craft-screenshots/api';

import { seedFormieIntegrationForm } from '../../../support/fixtures';

let editRoute = '/admin/formie/forms';

export default defineScreenshotScenario({
    id: 'formie-docs-legacy-integration-settings',
    output: 'docs/legacy/integration-settings.png',
    route: () => editRoute,
    viewport: { width: 1180, height: 820, deviceScaleFactor: 2 },
    async setup(context) {
        editRoute = (await seedFormieIntegrationForm(context)).editRoute;
    },
    waitFor: [
        { type: 'loadState', state: 'networkidle' },
        { type: 'selector', selector: '[role="tab"]', state: 'visible', timeout: 30000 },
    ],
    steps: [
        { type: 'click', selector: '[role="tab"]:has-text("Integrations")' },
        { type: 'wait', waitFor: { type: 'selector', selector: '.fui-integrations-pane', state: 'visible', timeout: 30000 } },
        { type: 'wait', waitFor: { type: 'selector', selector: '.fui-form-integrations-wrapper select[name$="[listId]"]', state: 'visible', timeout: 30000 } },
        { type: 'wait', waitFor: { type: 'text', text: 'Field Mapping', selector: '.fui-form-integrations-wrapper', timeout: 30000 } },
        { type: 'wait', waitFor: { type: 'timeout', ms: 500 } },
    ],
    target: { type: 'selector', selector: '.fui-integrations-pane' },
    caption: 'A real Mailchimp list and field mapping configured in Formie’s Craft 5 builder.',
    intent: 'Retain the legacy configured-integration subject with local deterministic provider metadata and no external API request.',
});
