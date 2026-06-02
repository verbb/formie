import { defineScreenshotScenario } from '@verbb/docs-screenshots/api';
import { seedContactFormFixture, type ContactFormFixture } from '../.screenshots/formie/fixtures';
import { createFormieScrollResetStep } from '../.screenshots/formie/presets';

let fixture: ContactFormFixture | null = null;

export default defineScreenshotScenario({
    id: 'forms-email-notification-content',
    output: '_screenshots/forms/email-notification-content.png',
    route: () => fixture?.editRoute ?? '/admin/formie/forms/new',
    viewport: {
        width: 1200,
        height: 800,
        deviceScaleFactor: 2,
    },
    async setup(context) {
        fixture = await seedContactFormFixture(context, {
            includeNotifications: true,
        });
    },
    waitFor: [
        { type: 'selector', selector: '.formie-form-builder.formie-form-builder--ready' },
    ],
    steps: [
        { type: 'click', selector: 'button:has-text("Email Notifications")' },
        { type: 'wait', waitFor: { type: 'text', text: 'User Notification', selector: '.formie-form-builder' } },
        { type: 'click', selector: 'button:has-text("User Notification")' },
        { type: 'wait', waitFor: { type: 'selector', selector: '[role="dialog"]' } },
        { type: 'wait', waitFor: { type: 'text', text: 'Email Content', selector: '[role="dialog"]' } },
        createFormieScrollResetStep(),
        {
            type: 'locatorEvaluate',
            selector: '[role="dialog"] [data-slot="tabs-content"] [data-slot="field"]',
            expression: `
                (() => {
                    // This screenshot is about the notification content itself,
                    // not the administrative fields above it. Hiding the first
                    // four fields keeps the modal focused on Subject + Content
                    // without requiring awkward fixture data just to satisfy the
                    // newer field-reference UI.
                    elements.slice(0, 4).forEach((field) => {
                        if (field instanceof HTMLElement) {
                            field.style.display = 'none';
                        }
                    });
                })();
            `,
        },
        { type: 'wait', waitFor: { type: 'timeout', ms: 250 } },
    ],
    target: {
        type: 'selector',
        selector: '[role="dialog"]',
    },
    caption: 'Edit Notification content modal for the seeded contact form.',
    intent: 'Capture the notification editor content tab with real subject and body content.',
});
