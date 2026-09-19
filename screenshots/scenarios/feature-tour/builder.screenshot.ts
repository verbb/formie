import { defineScreenshotScenario } from '@verbb/craft-screenshots/api';
import { createCpFocusedRegionPreset } from '@verbb/craft-screenshots/presets';

import { seedFormieContactForm } from '../../support/fixtures';

const preset = createCpFocusedRegionPreset({
    viewport: { width: 1152, height: 820, deviceScaleFactor: 2 },
});
let editRoute = '/admin/formie/forms';

export default defineScreenshotScenario({
    id: 'formie-feature-tour-builder',
    output: 'feature-tour/formie-form-builder.png',
    route: () => editRoute,
    viewport: preset.viewport,
    expectedOutput: { width: 2304, height: 1420 },
    async setup(context) {
        const fixture = await seedFormieContactForm(context);
        editRoute = fixture.editRoute;
    },
    waitFor: [
        { type: 'loadState', state: 'networkidle' },
        { type: 'text', text: 'Contact Form', timeout: 30000 },
        { type: 'selector', selector: '#main-content', state: 'visible', timeout: 30000 },
    ],
    steps: preset.steps,
    target: {
        type: 'clip',
        x: 0,
        y: 44,
        width: 1152,
        height: 710,
    },
    caption: 'A real Contact Form open in Formie’s Craft 5 form builder.',
    intent: 'Recreate the production form-builder subject with Formie’s own contact-form stencil and current Craft 5 interface.',
});
