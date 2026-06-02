import { defineScreenshotScenario } from '@verbb/docs-screenshots/api';
import { seedAddressFieldFixture } from '../.screenshots/formie/fixtures';
import { createFormieScrollResetStep } from '../.screenshots/formie/presets';

let editRoute = '/admin/formie/forms/new';

export default defineScreenshotScenario({
    id: 'fields-address-settings',
    output: '_screenshots/fields/address-settings.png',
    route: () => editRoute,
    viewport: {
        width: 1500,
        height: 920,
        deviceScaleFactor: 2,
    },
    async setup(context) {
        const fixture = await seedAddressFieldFixture(context);
        editRoute = fixture.editRoute;
    },
    waitFor: [
        { type: 'selector', selector: '.formie-form-builder.formie-form-builder--ready' },
    ],
    steps: [
        { type: 'click', selector: '.formie-form-builder [data-dropdown-trigger]' },
        { type: 'wait', waitFor: { type: 'selector', selector: '[role="menuitem"]' } },
        { type: 'click', selector: 'role=menuitem[name="Edit"]' },
        { type: 'wait', waitFor: { type: 'selector', selector: '[role="dialog"]' } },
        { type: 'wait', waitFor: { type: 'text', text: 'Edit Field', selector: '[role="dialog"]' } },
        createFormieScrollResetStep(),
        { type: 'wait', waitFor: { type: 'timeout', ms: 250 } },
    ],
    target: {
        type: 'selector',
        selector: '[role="dialog"]',
    },
    caption: 'Address field settings modal.',
    intent: 'Capture the address field editor showing enabled and disabled sub-fields.',
});
