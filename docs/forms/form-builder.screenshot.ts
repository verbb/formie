import fs from 'node:fs/promises';
import path from 'node:path';

import { defineScreenshotScenario } from '@verbb/docs-screenshots/api';
import {
    createCpFocusedRegionPreset,
    createFormieBuilderFrameStep,
} from '../.screenshots/formie/presets';

let formEditRoute = '/admin/formie/forms/new';

// This is the "hero" builder screenshot used in the docs, so we keep the
// target broad enough to show the builder canvas plus field palette, while
// letting the Formie preset layer handle the ugly CP cleanup work.
const preset = createCpFocusedRegionPreset({
    selector: '.formie-form-builder',
    viewport: {
        width: 1024,
        height: 700,
        deviceScaleFactor: 2,
    },
    padding: {
        top: 24,
        right: 24,
        bottom: 24,
        left: 24,
    },
});

export default defineScreenshotScenario({
    id: 'forms-form-builder-create',
    output: '_screenshots/forms/form-builder-create.png',
    route: () => formEditRoute,
    viewport: preset.viewport,
    async setup(context) {
        // We import a real Formie stencil rather than constructing the form in
        // the scenario itself. That keeps the screenshot deterministic and also
        // mirrors the kind of layout users actually see in production.
        const commandEnv = {
            NO_COLOR: '1',
            CLICOLOR: '0',
            CRAFT_NO_COLOR: '1',
        };
        const stencilPath = path.join(context.pluginRoot, 'src/migrations/stencils/contact-form.json');
        const importPath = path.join(context.tempRoot, 'contact-form.import.json');
        const stencil = JSON.parse(await fs.readFile(stencilPath, 'utf8')) as Record<string, unknown>;
        const importPayload = {
            title: 'Contact Form',
            handle: 'docsScreenshotContactForm',
            ...stencil,
            notifications: [],
        };

        await fs.writeFile(importPath, `${JSON.stringify(importPayload, null, 4)}\n`);

        const importOutput = await context.runCraft(['formie/forms/import', importPath, '--create=1'], { env: commandEnv });
        const handleMatch = importOutput.match(/Form\s+([A-Za-z0-9_]+)\s+has be created\./);

        if (!handleMatch) {
            throw new Error(`Unable to determine imported form handle from output: ${importOutput}`);
        }

        const formHandle = handleMatch[1];
        const listOutput = await context.runCraft(['formie/forms/list'], { env: commandEnv });
        const normalizedListOutput = listOutput.replace(/\u001b\[[0-9;]*m/g, '');
        const formMatch = normalizedListOutput.match(new RegExp(`\\[(\\d+)\\]\\s+${formHandle}\\b`));

        if (!formMatch) {
            throw new Error(`Unable to determine imported form ID for handle ${formHandle}.`);
        }

        formEditRoute = `/admin/formie/forms/edit/${formMatch[1]}`;
    },
    waitFor: [
        { type: 'selector', selector: '.formie-form-builder.formie-form-builder--ready' },
        { type: 'text', text: 'Existing Fields', selector: '.formie-form-builder h4' },
    ],
    preSteps: [
        ...preset.steps,
        // Builder screens are wide enough that we intentionally let the
        // Formie helper fit them into the screenshot viewport instead of
        // encoding that math inline in every builder scenario.
        createFormieBuilderFrameStep({ fitToViewport: true }),
        { type: 'wait', waitFor: { type: 'timeout', ms: 250 } },
    ],
    steps: [],
    target: preset.target,
    caption: 'Formie form builder focused on the seeded contact form layout.',
    intent: 'Capture the builder canvas and field palette without the wider Craft control panel chrome.',
});
