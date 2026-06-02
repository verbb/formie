import { defineScreenshotScenario } from '@verbb/docs-screenshots/api';
import { seedSyncedFieldFixture } from '../.screenshots/formie/fixtures';
import { createFormieScrollResetStep } from '../.screenshots/formie/presets';

let editRoute = '/admin/formie/forms/new';

export default defineScreenshotScenario({
    id: 'forms-synced-field',
    output: '_screenshots/forms/synced-field.png',
    route: () => editRoute,
    viewport: {
        width: 1000,
        height: 560,
        deviceScaleFactor: 2,
    },
    async setup(context) {
        const fixture = await seedSyncedFieldFixture(context);
        editRoute = fixture.editRoute;
    },
    waitFor: [
        { type: 'selector', selector: '.formie-form-builder.formie-form-builder--ready' },
        { type: 'text', text: 'Synced', selector: '.formie-form-builder' },
    ],
    steps: [
        // This screen is simple enough that all it really needs is a hard reset
        // of any inherited scroll position before the anchored crop is measured.
        createFormieScrollResetStep(),
        { type: 'wait', waitFor: { type: 'timeout', ms: 250 } },
    ],
    target: {
        // We intentionally crop relative to the builder so the screenshot only
        // shows the synced badge and field presentation, not the rest of the
        // Formie UI.
        type: 'anchoredClip',
        selector: '.formie-form-builder',
        x: 10,
        y: 170,
        width: 585,
        height: 105,
    },
    caption: 'Synced field preview showing the shared field badge in the builder.',
    intent: 'Capture a single synced field so the badge and shared-field concept are clear at a glance.',
});
