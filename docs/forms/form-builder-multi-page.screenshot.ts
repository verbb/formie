import { defineScreenshotScenario } from '@verbb/docs-screenshots/api';
import {
    createCpFocusedRegionPreset,
    createFormieBuilderFrameStep,
} from '../.screenshots/formie/presets';
import { seedMultiPageFormFixture } from '../.screenshots/formie/fixtures';

let editRoute = '/admin/formie/forms/new';

// This screenshot is intentionally tighter than the main builder shot. The aim
// is to teach "Formie supports multiple pages" at a glance, not to show the
// entire builder chrome.
const preset = createCpFocusedRegionPreset({
    selector: '.formie-form-builder',
    viewport: {
        width: 1150,
        height: 640,
        deviceScaleFactor: 2,
    },
});

export default defineScreenshotScenario({
    id: 'forms-form-builder-multi-page',
    output: '_screenshots/forms/form-builder-multi-page.png',
    route: () => editRoute,
    viewport: preset.viewport,
    async setup(context) {
        const fixture = await seedMultiPageFormFixture(context);
        editRoute = fixture.editRoute;
    },
    waitFor: [
        { type: 'selector', selector: '.formie-form-builder.formie-form-builder--ready' },
        { type: 'text', text: 'Personal Info', selector: '.formie-form-builder' },
        { type: 'text', text: 'Select your subject', selector: '.formie-form-builder' },
    ],
    preSteps: [
        ...preset.steps,
        // Multi-page framing wants the tabs scroller normalized and some of the
        // builder furniture removed so the page tabs read clearly in a compact
        // crop.
        createFormieBuilderFrameStep({
            hideExistingFieldsSidebar: true,
            hideNewPageButton: true,
            normalizeSettingsButton: true,
            resetTabsScroller: true,
        }),
        { type: 'wait', waitFor: { type: 'timeout', ms: 250 } },
    ],
    target: {
        // Anchoring the crop to the builder makes this screenshot far more
        // resilient than a raw viewport-relative clip while still letting us
        // fine-tune the composition by eye.
        type: 'anchoredClip',
        selector: '.formie-form-builder',
        x: 5,
        y: 110,
        width: 698,
        height: 150,
    },
    caption: 'Multi-page form builder showing several page tabs and the first page field layout.',
    intent: 'Capture a compact multi-page form example for the builder docs.',
});
