import { defineScreenshotScenario } from '@verbb/craft-screenshots/api';

import { seedFormieContactForm } from '../../support/fixtures';

let editRoute = '/admin/formie/forms';

export default defineScreenshotScenario({
    id: 'formie-feature-tour-notification-conditions',
    output: 'feature-tour/formie-notification-conditions.png',
    route: () => editRoute,
    viewport: { width: 1100, height: 760, deviceScaleFactor: 2 },
    async setup(context) {
        const fixture = await seedFormieContactForm(context);
        editRoute = fixture.editRoute;

        await context.runCraftScript(`
use verbb\\formie\\elements\\Form;
use verbb\\formie\\Formie;

$form = Form::find()->id(${fixture.formId})->status(null)->one();
$notification = $form ? Formie::$plugin->getNotifications()->getFormNotificationByHandle($form, 'adminNotification') : null;
if (!$notification) {
    throw new RuntimeException('Unable to find the Formie notification fixture.');
}

$notification->enableConditions = true;
$notification->conditions = [
    'sendRule' => 'send',
    'conditionRule' => 'all',
    'conditions' => [
        ['field' => '{field:emailAddress}', 'condition' => 'endsWith', 'value' => 'example.com'],
    ],
];
Formie::$plugin->getNotifications()->saveNotification($notification, false);
`, { label: 'seed-formie-notification-conditions' });
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
        { type: 'click', selector: '.fui-edit-notification-modal .fui-tab-item:has-text("Conditions")' },
        { type: 'wait', waitFor: { type: 'text', text: 'Enable Conditions', selector: '.fui-edit-notification-modal' } },
        { type: 'wait', waitFor: { type: 'timeout', ms: 250 } },
    ],
    target: { type: 'selector', selector: '.fui-edit-notification-modal .fui-modal-wrap' },
    caption: 'Formie’s real conditional-notification editor with a seeded email-address rule.',
    intent: 'Show the actual Formie conditions interface and persisted rule data in Craft 5.',
});
