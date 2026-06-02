import { defineScreenshotScenario } from '@verbb/docs-screenshots/api';
import { seedContactFormFixture, type ContactFormFixture } from '../.screenshots/formie/fixtures';

let fixture: ContactFormFixture | null = null;

export default defineScreenshotScenario({
    id: 'forms-email-notification-conditions',
    output: '_screenshots/forms/email-notification-conditions.png',
    route: () => fixture?.editRoute ?? '/admin/formie/forms/new',
    viewport: {
        width: 1200,
        height: 820,
        deviceScaleFactor: 2,
    },
    async setup(context) {
        fixture = await seedContactFormFixture(context, {
            includeNotifications: true,
        });

        // We save the old token-based condition values on purpose here. The UI
        // no longer presents them in the exact same way, but this remains a
        // compact, deterministic way to seed useful sample rules for the docs.
        await context.runCraftScript(`
use verbb\\formie\\elements\\Form;
use verbb\\formie\\Formie;

$form = Form::find()->id(${fixture.formId})->status(null)->one();

if (!$form) {
    throw new RuntimeException('Unable to find seeded form for conditions screenshot.');
}

$notification = Formie::$plugin->getNotifications()->getFormNotificationByHandle($form, 'adminNotification');

if (!$notification) {
    throw new RuntimeException('Unable to find admin notification for conditions screenshot.');
}

$notification->enableConditions = true;
$notification->conditions = [
    'sendRule' => 'send',
    'conditionRule' => 'all',
    'conditions' => [
        ['field' => '{field:yourName:firstName}', 'condition' => '=', 'value' => 'Peter'],
        ['field' => '{field:yourName:lastName}', 'condition' => 'contains', 'value' => 'herm'],
        ['field' => '{field:emailAddress}', 'condition' => 'endsWith', 'value' => 'wallaby.com.au'],
    ],
];

Formie::$plugin->getNotifications()->saveNotification($notification, false);
`, { label: 'configure-notification-conditions' });
    },
    waitFor: [
        { type: 'selector', selector: '.formie-form-builder.formie-form-builder--ready' },
    ],
    steps: [
        { type: 'click', selector: 'button:has-text("Email Notifications")' },
        { type: 'wait', waitFor: { type: 'text', text: 'Admin Notification', selector: '.formie-form-builder' } },
        { type: 'click', selector: 'button:has-text("Admin Notification")' },
        { type: 'wait', waitFor: { type: 'selector', selector: '[role="dialog"]' } },
        { type: 'click', selector: '[role="dialog"] button:has-text("Conditions")' },
        { type: 'wait', waitFor: { type: 'text', text: 'Enable Conditions', selector: '[role="dialog"]' } },
        { type: 'wait', waitFor: { type: 'selector', selector: '[role="dialog"] [data-slot="table-body"] [data-slot="table-row"]' } },
        {
            type: 'locatorEvaluate',
            selector: '[role="dialog"] [data-slot="table-body"] [data-slot="table-row"]',
            expression: `
                (() => {
                    // The modern field picker UI is harder to seed into the
                    // exact human-readable labels we want for docs. We keep the
                    // real rule rows, but rewrite the visible trigger text so
                    // the screenshot reads like the older docs examples.
                    const fieldLabels = [
                        'Your Name: First Name',
                        'Your Name: Last Name',
                        'Email Address',
                    ];

                    elements.forEach((row, index) => {
                        if (!(row instanceof HTMLElement)) {
                            return;
                        }

                        const label = fieldLabels[index];

                        if (!label) {
                            return;
                        }

                        const firstCell = row.querySelector(':scope > [data-slot="table-cell"]');
                        const labelSpan = firstCell instanceof HTMLElement
                            ? firstCell.querySelector('button[data-slot="popover-trigger"][data-size="sm"] > span.truncate')
                            : null;

                        if (labelSpan instanceof HTMLElement) {
                            labelSpan.textContent = label;
                            labelSpan.style.whiteSpace = 'nowrap';
                        }
                    });
                })();
            `,
        },
        { type: 'wait', waitFor: { type: 'timeout', ms: 150 } },
    ],
    target: {
        type: 'selector',
        selector: '[role="dialog"]',
    },
    caption: 'Notification conditions editor for the seeded contact form.',
    intent: 'Capture the notification conditions UI with sample rules already configured.',
});
