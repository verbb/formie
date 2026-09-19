import { defineScreenshotScenario } from '@verbb/craft-screenshots/api';
import { createCpFullScreenPreset } from '@verbb/craft-screenshots/presets';

const preset = createCpFullScreenPreset({
    viewport: { width: 1080, height: 620, deviceScaleFactor: 2 },
});

export default defineScreenshotScenario({
    id: 'formie-feature-tour-templates',
    output: 'feature-tour/formie-templates.png',
    route: '/admin/formie/settings/email-templates/new',
    viewport: preset.viewport,
    waitFor: [
        { type: 'loadState', state: 'networkidle' },
        { type: 'text', text: 'Email Template', timeout: 30000 },
        { type: 'selector', selector: '#main-content', state: 'visible', timeout: 30000 },
    ],
    steps: [
        ...preset.steps,
        { type: 'fill', selector: '#name', value: 'Default Email' },
        { type: 'fill', selector: '#handle', value: 'defaultEmail' },
        { type: 'fill', selector: '#template', value: '_formie/email' },
        { type: 'wait', waitFor: { type: 'timeout', ms: 200 } },
    ],
    target: preset.target,
    caption: 'The real Formie email-template editor in Craft 5.',
    intent: 'Show Formie’s current template controls rather than reusing the old production screenshot.',
});
