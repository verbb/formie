import { readFile, writeFile } from 'node:fs/promises';
import { join } from 'node:path';

import type { ScreenshotSetupContext } from '@verbb/craft-screenshots/types';

export type FormieScreenshotFixture = {
    editRoute: string;
    formId: number;
};

type FormieStencil = Record<string, any>;

/** Import Formie’s own contact-form stencil so screenshots exercise the real builder and field models. */
export async function seedFormieContactForm(context: ScreenshotSetupContext): Promise<FormieScreenshotFixture> {
    return seedStencilForm(context, 'screenshotContactForm', 'Contact Form', (stencil) => {
        stencil.notifications = notificationHandles(stencil.notifications);
    });
}

/** Seed a contact form with a persisted field-visibility rule for the native Conditions tab. */
export async function seedFormieConditionalFieldForm(context: ScreenshotSetupContext): Promise<FormieScreenshotFixture> {
    return seedStencilForm(context, 'screenshotConditionalFieldForm', 'Conditional Contact Form', (stencil) => {
        const nameField = stencil.pages[0].rows[0].fields[0].settings;

        nameField.enableConditions = true;
        nameField.conditions = {
            showRule: 'show',
            conditionRule: 'all',
            conditions: [
                { field: '{field:emailAddress}', condition: 'endsWith', value: 'example.com' },
            ],
        };
        stencil.notifications = [];
    });
}

/** Configure a local-only Mailchimp list so the real form integration mapping renders without API traffic. */
export async function seedFormieIntegrationForm(context: ScreenshotSetupContext): Promise<FormieScreenshotFixture> {
    const fixture = await seedFormieContactForm(context);

    await context.runCraftScript(`
use craft\\helpers\\Db;
use craft\\helpers\\Json;
use verbb\\formie\\base\\Integration;
use verbb\\formie\\elements\\Form;
use verbb\\formie\\Formie;
use verbb\\formie\\helpers\\Table;
use verbb\\formie\\integrations\\emailmarketing\\Mailchimp;
use verbb\\formie\\models\\IntegrationCollection;
use verbb\\formie\\models\\IntegrationField;
use verbb\\formie\\models\\IntegrationFormSettings;

$integrations = Formie::$plugin->getIntegrations();
$integration = $integrations->getIntegrationByHandle('screenshotMailchimp');

if (!$integration) {
    $integration = $integrations->createIntegration([
        'type' => Mailchimp::class,
        'name' => 'Mailchimp',
        'handle' => 'screenshotMailchimp',
        'enabled' => true,
        'apiKey' => 'screenshot-us1',
    ]);
    $integrations->saveIntegration($integration, false);
}

$formSettings = new IntegrationFormSettings([
    'lists' => [
        new IntegrationCollection([
            'id' => 'wallaby-newsletter',
            'name' => 'Wallaby Newsletter',
            'fields' => [
                new IntegrationField(['handle' => 'email_address', 'name' => 'Email Address', 'required' => '1']),
                new IntegrationField(['handle' => 'FNAME', 'name' => 'First Name']),
                new IntegrationField(['handle' => 'LNAME', 'name' => 'Last Name']),
                new IntegrationField(['handle' => 'tags', 'name' => 'Tags']),
            ],
        ]),
    ],
]);

Db::update(Table::FORMIE_INTEGRATIONS, [
    'cache' => Json::encode([
        'connection' => Integration::CONNECT_SUCCESS,
        'settings' => $formSettings->serialize(),
    ]),
], ['id' => $integration->id]);

$form = Form::find()->id(${fixture.formId})->status(null)->one();
if (!$form) {
    throw new RuntimeException('Unable to find the Formie integration screenshot form.');
}

$form->setIntegrationSettings('screenshotMailchimp', [
    'enabled' => true,
    'listId' => 'wallaby-newsletter',
    'fieldMapping' => [
        'email_address' => '{field:emailAddress}',
        'FNAME' => '{field:yourName.firstName}',
        'LNAME' => '{field:yourName.lastName}',
        'tags' => 'website-enquiry',
    ],
], false);

Craft::$app->getElements()->saveElement($form, false);
`, { label: 'seed-formie-mailchimp-integration' });

    return fixture;
}

/** Seed a stable notification so its real Formie preview stays useful across repeated captures. */
export async function seedFormieEmailPreviewForm(context: ScreenshotSetupContext): Promise<FormieScreenshotFixture> {
    return seedStencilForm(context, 'screenshotEmailPreviewFormV2', 'Contact Form', (stencil) => {
        const notification = stencil.notifications[0];

        notification.handle = 'adminNotification';
        notification.to = 'admin@wallaby.com.au';
        notification.replyTo = 'psherman@wallaby.com.au';
        notification.from = 'admin@wallaby.com.au';
        notification.fromName = 'Wallaby Admin';
        notification.subject = 'A new submission was made on "Contact Form"';
        notification.content = JSON.stringify([
            paragraph([
                textNode('A new submission has been received on '),
                textNode('Contact Form', true),
                textNode('.'),
            ]),
            paragraph([textNode('Please review the details below and follow up if needed.')]),
            paragraph([textNode('Your Name', true), hardBreak(), textNode('Peter Sherman')]),
            paragraph([textNode('Email Address', true), hardBreak(), textNode('psherman@wallaby.com.au')]),
            paragraph([textNode('Message', true), hardBreak(), textNode('The reason for my enquiry is support.')]),
        ]);

        stencil.notifications = [notification];
    });
}

/** Build a real four-page Formie form from its own supported import schema. */
export async function seedFormieMultiPageForm(context: ScreenshotSetupContext): Promise<FormieScreenshotFixture> {
    return seedStencilForm(context, 'screenshotMultiPageFormV3', 'Project Enquiry', (stencil) => {
        const sourcePage = stencil.pages[0];
        const rows = sourcePage.rows;
        const page = (label: string, pageRows: unknown[]) => ({
            ...sourcePage,
            label,
            rows: pageRows,
        });

        stencil.pages = [
            page('Personal info', [{
                fields: [{
                    type: 'verbb\\formie\\fields\\Dropdown',
                    settings: {
                        label: 'Select your subject',
                        handle: 'subject',
                        enabled: true,
                        required: false,
                        options: [
                            { label: 'Architecture', value: 'architecture', isDefault: true },
                            { label: 'Interior design', value: 'interiorDesign', isDefault: false },
                            { label: 'Landscape design', value: 'landscapeDesign', isDefault: false },
                        ],
                    },
                }],
            }]),
            page('Contact details', [rows[1]]),
            page('User profile', [rows[0]]),
            page('Confirm your details', [rows[2]]),
        ];
        stencil.notifications = [];
    });
}

/** Create a real Address field whose own settings modal can be captured. */
export async function seedFormieAddressForm(context: ScreenshotSetupContext): Promise<FormieScreenshotFixture> {
    return seedStencilForm(context, 'screenshotAddressForm', 'Address Details', (stencil) => {
        stencil.pages[0].rows = [{
            fields: [{
                type: 'verbb\\formie\\fields\\Address',
                settings: {
                    label: 'Postal Address',
                    handle: 'address',
                    instructions: 'Enter the address we should use for correspondence.',
                    enabled: true,
                    required: true,
                },
            }],
        }];
        stencil.notifications = [];
    });
}

/** Create a genuine synced Formie field and persist its sync relationship. */
export async function seedFormieSyncedField(context: ScreenshotSetupContext): Promise<FormieScreenshotFixture> {
    const fixture = await seedStencilForm(context, 'screenshotSyncedFieldForm', 'Newsletter Signup', (stencil) => {
        stencil.pages[0].rows = [stencil.pages[0].rows[1]];
        stencil.notifications = [];
    });

    await context.runCraftScript(`
use verbb\\formie\\elements\\Form;
use verbb\\formie\\Formie;

$form = Form::find()->id(${fixture.formId})->status(null)->one();
$field = $form?->getFormLayout()->getFieldByHandle('emailAddress');
if (!$field) {
    throw new RuntimeException('Unable to find the synced screenshot field.');
}
$field->syncId = $field->id;
Formie::$plugin->getFields()->saveField($field);
`, { label: 'seed-formie-synced-field-state' });

    return fixture;
}

/** Create a real Payment field so its native builder preview can be captured without contacting a gateway. */
export async function seedFormiePaymentForm(context: ScreenshotSetupContext): Promise<FormieScreenshotFixture> {
    return seedStencilForm(context, 'screenshotPaymentFormV2', 'Payment Form', (stencil) => {
        stencil.pages[0].rows = [{
            fields: [{
                type: 'verbb\\formie\\fields\\Payment',
                settings: {
                    label: 'Payment',
                    handle: 'payment',
                    enabled: true,
                    required: false,
                    paymentIntegration: 'screenshotStripe',
                    paymentIntegrationType: 'verbb\\formie\\integrations\\payments\\Stripe',
                },
            }],
        }];
        stencil.notifications = [];
    });
}

async function seedStencilForm(
    context: ScreenshotSetupContext,
    handle: string,
    title: string,
    mutate: (stencil: FormieStencil) => void,
): Promise<FormieScreenshotFixture> {
    const stencil = JSON.parse(await readFile(join(context.pluginRoot, 'src/migrations/stencils/contact-form.json'), 'utf8')) as FormieStencil;
    mutate(stencil);

    const payloadPath = join(context.tempRoot, `${handle}.json`);
    await writeFile(payloadPath, `${JSON.stringify({ ...stencil, title, handle }, null, 4)}\n`);

    const result = await context.runCraftScript(`
use craft\\helpers\\Json;
use verbb\\formie\\elements\\Form;
use verbb\\formie\\helpers\\ImportExportHelper;

$handle = ${JSON.stringify(handle)};
$form = Form::find()->handle($handle)->status(null)->one();
if (!$form) {
    $json = Json::decode(file_get_contents(${JSON.stringify(context.toRuntimePath(payloadPath))}));
    $form = ImportExportHelper::importFormFromJson($json, 'create');
}

$form = Form::find()->id($form->id)->status(null)->one();
if (!$form) {
    throw new RuntimeException('Unable to create the Formie screenshot form.');
}

echo Json::encode([
    'formId' => $form->id,
    'editRoute' => "/admin/formie/forms/edit/{$form->id}",
], JSON_THROW_ON_ERROR);
`, { label: `seed-formie-${handle}` });

    return JSON.parse(result.trim()) as FormieScreenshotFixture;
}

function notificationHandles(notifications: unknown): unknown[] {
    return Array.isArray(notifications)
        ? notifications.map((notification, index) => ({
            ...(notification as Record<string, unknown>),
            handle: index === 0 ? 'adminNotification' : 'userNotification',
        }))
        : [];
}

function paragraph(content: Record<string, unknown>[]): Record<string, unknown> {
    return { type: 'paragraph', content };
}

function textNode(text: string, bold = false): Record<string, unknown> {
    return {
        type: 'text',
        text,
        ...(bold ? { marks: [{ type: 'bold' }] } : {}),
    };
}

function hardBreak(): Record<string, unknown> {
    return { type: 'hardBreak' };
}
