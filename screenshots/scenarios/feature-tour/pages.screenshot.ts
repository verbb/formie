import { defineScreenshotScenario } from '@verbb/craft-screenshots/api';
import { createCpFocusedRegionPreset } from '@verbb/craft-screenshots/presets';

import { seedFormieMultiPageForm } from '../../support/fixtures';

const preset = createCpFocusedRegionPreset({
    selector: '.fui-fields-wrapper',
    viewport: { width: 1054, height: 650, deviceScaleFactor: 2 },
    padding: { top: 0, right: 0, bottom: 0, left: 0 },
});
let editRoute = '/admin/formie/forms';

export default defineScreenshotScenario({
    id: 'formie-feature-tour-pages',
    output: 'feature-tour/formie-pages.png',
    route: () => editRoute,
    viewport: preset.viewport,
    expectedOutput: { width: 1612, height: 508 },
    async setup(context) {
        editRoute = (await seedFormieMultiPageForm(context)).editRoute;
    },
    waitFor: [
        { type: 'loadState', state: 'networkidle' },
        { type: 'text', text: 'Contact details', timeout: 30000 },
        { type: 'text', text: 'User profile', timeout: 30000 },
    ],
    steps: [
        ...preset.steps,
        {
            type: 'evaluate',
            expression: `
                const style = document.createElement('style');
                style.textContent = [
                    '.fui-sidebar-wrapper { display: none !important; }',
                    '.fui-fields-pane, .fui-fields-wrapper { height: 254px !important; }',
                    '.fui-fields-wrapper { width: 806px !important; max-width: 806px !important; margin-inline: auto !important; }',
                    '.fui-fields-inner-wrapper { overflow: hidden !important; }',
                    '.fui-submit-block { display: none !important; }',
                ].join('\\n');
                document.head.appendChild(style);
            `,
        },
    ],
    target: preset.target,
    caption: 'A real four-page form in Formie’s Craft 5 form builder.',
    intent: 'Translate the production multi-page subject to the current Formie builder using a saved Formie form.',
});
