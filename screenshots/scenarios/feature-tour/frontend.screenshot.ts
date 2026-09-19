import { writeFile } from 'node:fs/promises';
import { join } from 'node:path';

import { defineScreenshotScenario } from '@verbb/craft-screenshots/api';

import { seedFormieContactForm } from '../../support/fixtures';

// craft-screenshots: sample-frontend
export default defineScreenshotScenario({
    id: 'formie-feature-tour-frontend',
    output: 'feature-tour/formie-frontend.png',
    route: '/screenshot-formie',
    viewport: { width: 1000, height: 760, deviceScaleFactor: 2 },
    expectedOutput: { width: 1536, height: 800 },
    async setup(context) {
        await seedFormieContactForm(context);

        // The template supplies only a neutral page canvas; Formie renders the complete form and its own default theme.
        await writeFile(join(context.installDir, 'templates/screenshot-formie.twig'), `<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Contact Form</title>
    <style>
        * { box-sizing: border-box; }
        html, body { margin: 0; background: #fff; }
        body { padding: 24px; }
        .capture { width: 720px; }
    </style>
</head>
<body>
    <main class="capture">
        {{ craft.formie.renderForm('screenshotContactForm') }}
    </main>
</body>
</html>
`);
    },
    waitFor: [
        { type: 'loadState', state: 'networkidle' },
        { type: 'selector', selector: '.fui-form', state: 'visible', timeout: 30000 },
        { type: 'text', text: 'Contact us', timeout: 30000 },
    ],
    steps: [
        {
            type: 'evaluate',
            expression: `
                document.querySelectorAll('.fui-instructions').forEach((instructions) => {
                    if (instructions.textContent?.trim() === 'Please enter your full name.' && instructions instanceof HTMLElement) {
                        instructions.style.display = 'none';
                    }
                });
            `,
        },
        { type: 'wait', waitFor: { type: 'timeout', ms: 200 } },
    ],
    target: {
        type: 'selector',
        selector: '.fui-form',
        padding: {
            top: 24,
            right: 24,
            bottom: 24,
            left: 24,
        },
    },
    caption: 'A real Formie contact form rendered with its default front-end template.',
    intent: 'Show the complete default front-end form at a focused, readable width with balanced space on every side.',
});
