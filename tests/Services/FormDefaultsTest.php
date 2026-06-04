<?php

declare(strict_types=1);

use verbb\formie\elements\Form;
use verbb\formie\fields\Date;
use verbb\formie\fields\Email;
use verbb\formie\fields\Entries;
use verbb\formie\fields\FileUpload;
use verbb\formie\fields\Number;
use verbb\formie\fields\Checkboxes;
use verbb\formie\fields\Hidden;
use verbb\formie\fields\Radio;
use verbb\formie\fields\SingleLineText;
use verbb\formie\fields\Users;
use verbb\formie\Formie;
use verbb\formie\models\Notification;

it('bootstraps registered fields without recursion when applying field defaults', function (): void {
    $fields = Formie::$plugin->getFields()->getRegisteredFields(false);

    expect($fields)->not->toBeEmpty()
        ->and(new Date())->toBeInstanceOf(Date::class);
});

it('migrates legacy file upload and date default settings into fieldDefaults', function (): void {
    $service = Formie::$plugin->getFormDefaults();

    $migrated = $service->migrateLegacyFieldDefaults([
        'defaultFileUploadVolume' => 'folder:legacy-volume',
        'defaultDateDisplayType' => 'datePicker',
        'defaultDateValueOption' => 'today',
        'defaultDateTime' => '2024-01-15 09:00:00',
    ]);

    expect($migrated['fieldDefaults'][FileUpload::class]['uploadLocationSource'] ?? null)->toBe('folder:legacy-volume')
        ->and($migrated['fieldDefaults'][Date::class]['displayType'] ?? null)->toBe('datePicker')
        ->and($migrated['fieldDefaults'][Date::class]['defaultOption'] ?? null)->toBe('today')
        ->and($migrated['fieldDefaults'][Date::class]['defaultValue'] ?? null)->toBe('2024-01-15 09:00:00')
        ->and($migrated)->not->toHaveKey('defaultFileUploadVolume')
        ->and($migrated)->not->toHaveKey('defaultDateDisplayType')
        ->and($migrated)->not->toHaveKey('defaultDateValueOption')
        ->and($migrated)->not->toHaveKey('defaultDateTime');
});

it('applies form defaults to new forms when values are not posted', function (): void {
    $settings = Formie::$plugin->getSettings();
    $settings->formDefaults = [
        'submissionTitleFormat' => '{form.title}',
        'collectIp' => true,
        'submitMethod' => 'ajax',
        'displayPageProgress' => true,
        'progressPosition' => 'start',
        'requiredIndicator' => 'optional',
    ];

    $form = new Form();
    Formie::$plugin->getFormDefaults()->applyToNewForm($form, []);

    expect($form->settings->submissionTitleFormat)->toBe('{form.title}')
        ->and($form->settings->collectIp)->toBeTrue()
        ->and($form->settings->submitMethod)->toBe('ajax')
        ->and($form->settings->displayPageProgress)->toBeTrue()
        ->and($form->settings->progressPosition)->toBe('start')
        ->and($form->settings->requiredIndicator)->toBe('optional');
});

it('applies supported field defaults when creating new fields', function (): void {
    Formie::$plugin->getSettings()->fieldDefaults = [
        Date::class => [
            'displayType' => 'dropdowns',
            'defaultOption' => 'today',
            'defaultValue' => null,
        ],
        Email::class => [
            'validateDomain' => true,
            'blockFreeDomains' => true,
        ],
        Number::class => [
            'decimals' => 2,
        ],
    ];

    $dateField = new Date();
    $emailField = new Email();
    $numberField = new Number();

    expect($dateField->displayType)->toBe('dropdowns')
        ->and($dateField->defaultOption)->toBe('today')
        ->and($emailField->validateDomain)->toBeTrue()
        ->and($emailField->blockFreeDomains)->toBeTrue()
        ->and($numberField->decimals)->toBe(2);
});

it('does not override explicitly provided field config with defaults', function (): void {
    Formie::$plugin->getSettings()->fieldDefaults = [
        Date::class => [
            'displayType' => 'dropdowns',
        ],
    ];

    $field = new Date(['displayType' => 'inputs']);

    expect($field->displayType)->toBe('inputs');
});

it('applies notification defaults including attachPdf and pdfTemplateId', function (): void {
    Formie::$plugin->getSettings()->notificationDefaults = [
        'fromName' => 'Site Admin',
        'from' => 'admin@example.com',
        'subject' => 'New submission',
        'attachFiles' => true,
        'attachPdf' => true,
        'pdfTemplateId' => 42,
        'enabled' => false,
    ];

    $notification = new Notification();
    Formie::$plugin->getFormDefaults()->applyToNewNotification($notification, []);

    expect($notification->fromName)->toBe('Site Admin')
        ->and($notification->from)->toBe('admin@example.com')
        ->and($notification->subject)->toBe('New submission')
        ->and($notification->attachFiles)->toBeTrue()
        ->and((bool)$notification->attachPdf)->toBeTrue()
        ->and($notification->pdfTemplateId)->toBe(42)
        ->and($notification->enabled)->toBeFalse();
});

it('exposes schema-backed field type defaults config for supported field types', function (): void {
    $fieldTypes = Formie::$plugin->getFormDefaults()->getFieldTypeDefaultsConfig();
    $types = array_column($fieldTypes, 'type');

    expect($types)->toContain(
        Date::class,
        FileUpload::class,
        \verbb\formie\fields\Phone::class,
        \verbb\formie\fields\Agree::class,
        SingleLineText::class,
        Email::class,
        Number::class,
        Entries::class,
        \verbb\formie\fields\Radio::class,
        \verbb\formie\fields\Checkboxes::class,
        \verbb\formie\fields\Hidden::class,
    );

    $dateConfig = collect($fieldTypes)->firstWhere('type', Date::class);

    expect($dateConfig)->not->toBeNull()
        ->and($dateConfig['schema'] ?? null)->not->toBeEmpty()
        ->and($dateConfig['schemaIndex'] ?? null)->toBeArray();
});

it('exposes schema-backed form and notification defaults config', function (): void {
    $service = Formie::$plugin->getFormDefaults();

    expect($service->getFormDefaultsSchemaConfig()['schema'] ?? null)->toHaveCount(16)
        ->and($service->getNotificationDefaultsSchemaConfig()['schema'] ?? null)->toHaveCount(9);
});

it('applies captcha integration defaults to integrations settings', function (): void {
    $captchas = Formie::$plugin->getFormDefaults()->getIntegrationCaptchaOptions();

    if ($captchas === []) {
        expect(true)->toBeTrue();

        return;
    }

    $handle = $captchas[0]['handle'];
    Formie::$plugin->getSettings()->integrationDefaults = [
        'captchas' => [
            $handle => false,
        ],
    ];

    $integrations = [];
    Formie::$plugin->getFormDefaults()->applyCaptchaDefaultsToIntegrations($integrations);

    expect($integrations[$handle]['enabled'] ?? null)->toBeFalse();
});

it('applies captcha integration defaults to new forms', function (): void {
    $captchas = Formie::$plugin->getFormDefaults()->getIntegrationCaptchaOptions();

    if ($captchas === []) {
        expect(true)->toBeTrue();

        return;
    }

    $handle = $captchas[0]['handle'];
    Formie::$plugin->getSettings()->integrationDefaults = [
        'captchas' => [
            $handle => false,
        ],
    ];

    $form = new Form();
    Formie::$plugin->getFormDefaults()->applyCaptchaDefaultsToNewForm($form);

    expect($form->settings->integrations[$handle]['enabled'] ?? null)->toBeFalse();
});

it('prepares field defaults for the editor from class defaults when nothing is stored', function (): void {
    Formie::$plugin->getSettings()->fieldDefaults = [];

    $prepared = Formie::$plugin->getFormDefaults()->prepareFieldTypeDefaultsForEditor(Entries::class);

    expect($prepared['displayType'] ?? null)->toBe('dropdown')
        ->and($prepared['labelSource'] ?? null)->toBe('title')
        ->and($prepared['orderBy'] ?? null)->toBe('title ASC')
        ->and($prepared['layout'] ?? null)->toBeNull();
});

it('prepares select field defaults from class defaults for checkboxes hidden and users', function (): void {
    Formie::$plugin->getSettings()->fieldDefaults = [];

    $service = Formie::$plugin->getFormDefaults();

    expect($service->prepareFieldTypeDefaultsForEditor(Checkboxes::class)['layout'] ?? null)->toBe('vertical')
        ->and($service->prepareFieldTypeDefaultsForEditor(Hidden::class)['defaultOption'] ?? null)->toBe('custom');

    if (!Formie::$plugin->getFields()->getRegisteredFieldByType(Users::class, false)) {
        expect(true)->toBeTrue();

        return;
    }

    expect($service->prepareFieldTypeDefaultsForEditor(Users::class)['labelSource'] ?? null)->toBe('email');
});

it('uses stored field defaults in the editor over class defaults', function (): void {
    Formie::$plugin->getSettings()->fieldDefaults = [
        Entries::class => [
            'displayType' => 'radio',
        ],
    ];

    $prepared = Formie::$plugin->getFormDefaults()->prepareFieldTypeDefaultsForEditor(Entries::class);

    expect($prepared['displayType'] ?? null)->toBe('radio')
        ->and($prepared['labelSource'] ?? null)->toBe('title');
});

it('normalizes field defaults matching class defaults back to inherit for storage', function (): void {
    $service = Formie::$plugin->getFormDefaults();

    $normalized = $service->normalizeFieldDefaultsForStorage([
        Entries::class => [
            'displayType' => 'dropdown',
            'labelSource' => 'title',
            'orderBy' => 'title ASC',
        ],
        Radio::class => [
            'layout' => 'vertical',
        ],
        Date::class => [
            'displayType' => 'dropdowns',
        ],
    ]);

    expect($normalized)->not->toHaveKey(Entries::class)
        ->and($normalized)->not->toHaveKey(Radio::class)
        ->and($normalized[Date::class]['displayType'] ?? null)->toBe('dropdowns');
});

it('normalizes inherit boolean settings payload values for storage', function (): void {
    $service = Formie::$plugin->getFormDefaults();

    $normalized = $service->normalizeSettingsPayload([
        'notificationDefaults' => [
            'attachFiles' => '1',
            'attachPdf' => '0',
            'enabled' => '',
            'pdfTemplateId' => '7',
        ],
        'integrationDefaults' => [
            'captchas' => [
                'recaptcha' => '1',
            ],
        ],
    ]);

    expect($normalized['notificationDefaults']['attachFiles'])->toBeTrue()
        ->and($normalized['notificationDefaults']['attachPdf'])->toBeFalse()
        ->and($normalized['notificationDefaults']['enabled'])->toBeNull()
        ->and($normalized['notificationDefaults']['pdfTemplateId'])->toBe(7)
        ->and($normalized['integrationDefaults']['captchas']['recaptcha'])->toBeTrue();
});

it('caches field type defaults config within the service instance', function (): void {
    $service = Formie::$plugin->getFormDefaults();

    expect($service->getFieldTypeDefaultsConfig())->toBe($service->getFieldTypeDefaultsConfig());
});
