import { defineScreenshotScenario } from '@verbb/craft-screenshots/api';

import { seedFormieContactForm } from '../../support/fixtures';

let editRoute = '/admin/formie/forms';

export default defineScreenshotScenario({
    id: 'formie-feature-tour-email-notification',
    output: 'feature-tour/formie-email-notification.png',
    route: () => editRoute,
    viewport: { width: 1100, height: 760, deviceScaleFactor: 2 },
    async setup(context) {
        editRoute = (await seedFormieContactForm(context)).editRoute;
    },
    waitFor: [
        { type: 'loadState', state: 'networkidle' },
        { type: 'selector', selector: '[role="tab"]', state: 'visible', timeout: 30000 },
    ],
    steps: [
        { type: 'click', selector: '[role="tab"]:has-text("Email Notifications")' },
        { type: 'wait', waitFor: { type: 'selector', selector: '.fui-notification-row:not(.hidden)', state: 'visible', timeout: 30000 } },
        { type: 'click', selector: '.fui-notification-row:not(.hidden):has-text("Admin Notification") a.flex' },
        { type: 'wait', waitFor: { type: 'selector', selector: '.fui-edit-notification-modal', state: 'visible', timeout: 30000 } },
        { type: 'wait', waitFor: { type: 'text', text: 'Email Content', selector: '.fui-edit-notification-modal' } },
        {
            type: 'evaluate',
            expression: `
                (() => {
                    const hiddenLabels = new Set([
                        'Enabled',
                        'Name',
                        'Recipients',
                        'Recipient Emails',
                        'CC',
                        'BCC',
                        'From Name',
                        'From Email',
                        'Reply To',
                    ]);
                    const modal = document.querySelector('.fui-edit-notification-modal');

                    for (const label of modal?.querySelectorAll('label') ?? []) {
                        const text = label.textContent?.replace(/\\s+/g, ' ').trim().replace(/\\s*\\*$/, '');

                        if (text && hiddenLabels.has(text)) {
                            const field = label.closest('.formkit-outer, .field');
                            field?.style.setProperty('display', 'none', 'important');
                        }
                    }

                    const body = modal?.querySelector('.fui-modal-body');
                    if (body) body.scrollTop = 0;
                })();
            `,
        },
        { type: 'wait', waitFor: { type: 'timeout', ms: 250 } },
    ],
    target: { type: 'selector', selector: '.fui-edit-notification-modal .fui-modal-wrap' },
    caption: 'A real Formie email notification open in the Craft 5 notification editor.',
    intent: 'Translate the production notification subject to Formie’s current editor with actual notification data.',
});
