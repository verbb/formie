import fs from 'node:fs/promises';
import path from 'node:path';

import type { ScreenshotSetupContext } from '@verbb/docs-screenshots/types';

// Fixture helpers are the main plugin-specific extension point for docs
// screenshots. The shared package does not need to know anything about Formie's
// factories, notifications, or builder schema, so all that knowledge stays
// here.
export type ContactFormFixture = {
    formId: number;
    editRoute: string;
    sentNotificationsRoute: string;
    notifications: Record<string, {
        id: number;
        handle: string;
        name: string;
    }>;
    sentNotificationIds: number[];
};

type ContactFormOptions = {
    includeNotifications?: boolean;
    includeSentNotifications?: boolean;
};

const CONTACT_FORM_HANDLE = 'docsScreenshotContactForm';

function toHandle(value: string, fallback: string): string {
    const normalized = value
        .replace(/[^A-Za-z0-9]+/g, ' ')
        .trim()
        .split(/\s+/)
        .map((part, index) => {
            const lower = part.toLowerCase();
            return index === 0 ? lower : `${lower.charAt(0).toUpperCase()}${lower.slice(1)}`;
        })
        .join('');

    return normalized || fallback;
}

export async function seedContactFormFixture(context: ScreenshotSetupContext, options: ContactFormOptions = {}): Promise<ContactFormFixture> {
    const {
        includeNotifications = true,
        includeSentNotifications = false,
    } = options;

    // We seed from the real migration stencil so the docs examples stay aligned
    // with Formie's own canonical contact-form structure.
    const stencilPath = path.join(context.pluginRoot, 'src/migrations/stencils/contact-form.json');
    const stencil = JSON.parse(await fs.readFile(stencilPath, 'utf8')) as Record<string, any>;
    const notifications = Array.isArray(stencil.notifications) ? stencil.notifications : [];

    const importPayload = {
        title: 'Contact Form',
        handle: CONTACT_FORM_HANDLE,
        ...stencil,
        notifications: includeNotifications ? notifications.map((notification, index) => {
            const fallbackHandle = `notification${index + 1}`;

            return {
                ...notification,
                handle: typeof notification.handle === 'string'
                    ? notification.handle
                    : toHandle(String(notification.name ?? fallbackHandle), fallbackHandle),
            };
        }) : [],
    };

    const importPath = path.join(context.tempRoot, 'contact-form.import.json');
    const runtimeImportPath = context.toRuntimePath(importPath);
    await fs.writeFile(importPath, `${JSON.stringify(importPayload, null, 4)}\n`);

    const result = await context.runCraftScript(`
use craft\\helpers\\Json;
use verbb\\formie\\Formie;
use verbb\\formie\\elements\\Form;
use verbb\\formie\\helpers\\ImportExportHelper;

$filePath = ${JSON.stringify(runtimeImportPath)};
$json = Json::decode(file_get_contents($filePath));
$form = ImportExportHelper::importFormFromJson($json, 'create');
$form = Form::find()->id($form->id)->status(null)->one();

if (!$form) {
    throw new RuntimeException('Unable to find imported contact form.');
}

$notificationsByHandle = [];

foreach ($form->getNotifications() as $notification) {
    $notificationsByHandle[$notification->handle] = [
        'id' => $notification->id,
        'handle' => $notification->handle,
        'name' => (string)$notification->name,
    ];
}

$sentNotificationIds = [];

if (${includeSentNotifications ? 'true' : 'false'}) {
    // Sent notifications need richer fixture data because we want the resulting
    // screenshot to look like a real message preview, not just a placeholder
    // body that proves the page loaded.
    $submissionFactory = Formie::$plugin->getFactories()->submission($form);
    $notificationMap = [];

    foreach ($form->getNotifications() as $notification) {
        $notificationMap[$notification->handle] = $notification;
    }

    $sentDefinitions = [
        [
            'subject' => 'A new submission was made on "Contact Form"',
            'to' => 'admin@wallaby.com.au',
            'from' => 'admin@wallaby.com.au',
            'fromName' => 'Wallaby Admin',
            'replyTo' => 'psherman@wallaby.com.au',
            'replyToName' => 'Peter Sherman',
            'htmlBody' => '<div style="font-family: -apple-system, BlinkMacSystemFont, &quot;Segoe UI&quot;, sans-serif; font-size: 10px; line-height: 1.5; color: #111827;"><p style="margin: 0 0 20px;">A new submission has been received on <strong>Contact Form</strong>.</p><p style="margin: 0 0 24px;">Please review the details below and follow up if needed.</p><p style="margin: 0 0 20px;"><strong>Your Name</strong><br>Peter Sherman</p><p style="margin: 0 0 20px;"><strong>Email Address</strong><br>psherman@wallaby.com.au</p><p style="margin: 0;"><strong>Message</strong><br>The reason for my enquiry is support.</p></div>',
            'textBody' => "A new submission has been received on Contact Form.\n\nPlease review the details below and follow up if needed.\n\nYour Name\nPeter Sherman\n\nEmail Address\npsherman@wallaby.com.au\n\nMessage\nThe reason for my enquiry is support.",
            'notificationHandle' => 'adminNotification',
            'values' => [
                'yourName' => ['firstName' => 'Peter', 'lastName' => 'Sherman'],
                'emailAddress' => 'psherman@wallaby.com.au',
                'message' => 'The reason for my enquiry is support.',
            ],
            'dateCreated' => '2020-11-08 19:09:23',
        ],
        [
            'subject' => 'Thanks for contacting us!',
            'to' => 'psherman@wallaby.com.au',
            'from' => 'admin@wallaby.com.au',
            'fromName' => 'Wallaby Admin',
            'replyTo' => 'admin@wallaby.com.au',
            'replyToName' => 'Wallaby Admin',
            'htmlBody' => '<p>Thanks again for contacting us. Our team will get back to you as soon as we can.</p>',
            'textBody' => 'Thanks again for contacting us. Our team will get back to you as soon as we can.',
            'notificationHandle' => 'userNotification',
            'values' => [
                'yourName' => ['firstName' => 'Peter', 'lastName' => 'Sherman'],
                'emailAddress' => 'psherman@wallaby.com.au',
                'message' => 'The reason for my enquiry is support.',
            ],
            'dateCreated' => '2020-11-08 19:09:23',
        ],
    ];

    foreach ($sentDefinitions as $definition) {
        $notification = $notificationMap[$definition['notificationHandle']] ?? null;

        if (!$notification) {
            continue;
        }

        $submission = $submissionFactory
            ->with($definition['values'])
            ->save();

        $email = new craft\\mail\\Message();
        $email->setSubject($definition['subject']);
        $email->setTo([$definition['to'] => $definition['to']]);
        $email->setFrom([$definition['from'] => $definition['fromName']]);
        $email->setReplyTo([$definition['replyTo'] => $definition['replyToName']]);
        $email->setHtmlBody($definition['htmlBody']);
        $email->setTextBody($definition['textBody']);

        Formie::$plugin->getSentNotifications()->saveSentNotification($submission, $notification, $email);

        $sentNotification = \\verbb\\formie\\elements\\SentNotification::find()
            ->submissionId($submission->id)
            ->status(null)
            ->one();

        if ($sentNotification) {
            craft\\helpers\\Db::update('{{%elements}}', [
                'dateCreated' => $definition['dateCreated'],
                'dateUpdated' => $definition['dateCreated'],
            ], ['id' => $sentNotification->id], [], false);

            $sentNotificationIds[] = $sentNotification->id;
        }
    }
}

echo Json::encode([
    'formId' => $form->id,
    'editRoute' => "/admin/formie/forms/edit/{$form->id}",
    'sentNotificationsRoute' => '/admin/formie/sent-notifications',
    'notifications' => $notificationsByHandle,
    'sentNotificationIds' => $sentNotificationIds,
], JSON_THROW_ON_ERROR);
`, { label: 'seed-contact-form-fixture' });

    return JSON.parse(result.trim()) as ContactFormFixture;
}

export async function seedAddressFieldFixture(context: ScreenshotSetupContext): Promise<{ formId: number; editRoute: string }> {
    const result = await context.runCraftScript(`
use craft\\helpers\\Json;
use verbb\\formie\\Formie;

$form = Formie::$plugin->getFactories()
    ->form([
        'title' => 'Address Field',
        'handle' => 'docsScreenshotAddressField',
    ])
    ->addressField('address', [
        'label' => 'Address',
        'required' => true,
    ])
    ->create();

echo Json::encode([
    'formId' => $form->id,
    'editRoute' => "/admin/formie/forms/edit/{$form->id}",
], JSON_THROW_ON_ERROR);
`, { label: 'seed-address-field-fixture' });

    return JSON.parse(result.trim()) as { formId: number; editRoute: string };
}

export async function seedMultiPageFormFixture(context: ScreenshotSetupContext): Promise<{ formId: number; editRoute: string }> {
    const result = await context.runCraftScript(`
use craft\\helpers\\Json;
use verbb\\formie\\Formie;

$form = Formie::$plugin->getFactories()
    ->form([
        'title' => 'Multi Page Form',
        'handle' => 'docsScreenshotMultiPageForm',
    ])
    ->multiPage(4)
    ->page(1)
    ->dropdownField('subject', [
        'label' => 'Select your subject',
        'placeholder' => 'Architecture',
        'options' => [
            ['label' => 'Architecture', 'value' => 'architecture'],
            ['label' => 'Engineering', 'value' => 'engineering'],
        ],
    ])
    ->page(2)
    ->singleLineTextField('phoneNumber', [
        'label' => 'Phone Number',
        'placeholder' => '0400 123 456',
    ])
    ->page(3)
    ->singleLineTextField('memberId', [
        'label' => 'Member ID',
        'placeholder' => 'VP-1042',
    ])
    ->page(4)
    ->htmlField('confirmationCopy', [
        'label' => 'Confirmation',
        'htmlContent' => '<p>Review your details before submitting.</p>',
    ])
    ->create();

// The page labels are what matter for the screenshot, so we explicitly rename
// them after factory creation instead of depending on factory defaults.
$layout = $form->getFormLayout();
$pages = $layout->getPages();
$labels = [
    'Personal Info',
    'Contact Details',
    'User Profile',
    'Confirm Your Details',
];

foreach ($pages as $index => $page) {
    $page->label = $labels[$index] ?? ('Page ' . ($index + 1));
}

$layout->setPages($pages);
$form->setFormLayout($layout);

if (!Craft::$app->elements->saveElement($form)) {
    throw new RuntimeException('Unable to save multi-page screenshot form.');
}

echo Json::encode([
    'formId' => $form->id,
    'editRoute' => "/admin/formie/forms/edit/{$form->id}",
], JSON_THROW_ON_ERROR);
`, { label: 'seed-multi-page-form-fixture' });

    return extractJsonResult(result) as { formId: number; editRoute: string };
}

export async function seedSyncedFieldFixture(context: ScreenshotSetupContext): Promise<{ formId: number; editRoute: string }> {
    const result = await context.runCraftScript(`
use craft\\helpers\\Json;
use verbb\\formie\\Formie;

$form = Formie::$plugin->getFactories()
    ->form([
        'title' => 'Synced Field Form',
        'handle' => 'docsScreenshotSyncedFieldForm',
    ])
    ->emailField('emailAddress', [
        'label' => 'Email Address',
        'placeholder' => 'eg. psherman@wallaby.com',
        'instructions' => 'Please enter your email so we can get it touch.',
    ])
    ->create();

$field = $form->getFormLayout()->getFieldByHandle('emailAddress');

if (!$field) {
    throw new RuntimeException('Unable to find synced screenshot field.');
}

// A field becomes visibly "synced" in the builder once it points at a sync id.
// Reusing its own id is enough for the screenshot fixture and keeps setup tiny.
$field->syncId = $field->id;
Formie::$plugin->getFields()->saveField($field);

echo Json::encode([
    'formId' => $form->id,
    'editRoute' => "/admin/formie/forms/edit/{$form->id}",
], JSON_THROW_ON_ERROR);
`, { label: 'seed-synced-field-fixture' });

    return extractJsonResult(result) as { formId: number; editRoute: string };
}

function extractJsonResult(result: string): unknown {
    const trimmed = result.trim();

    try {
        return JSON.parse(trimmed) as unknown;
    } catch {
        const match = trimmed.match(/(\{[\s\S]*\})\s*$/);

        if (!match) {
            throw new Error(`Unable to extract JSON result from output: ${trimmed}`);
        }

        return JSON.parse(match[1]) as unknown;
    }
}
