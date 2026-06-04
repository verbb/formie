<?php

declare(strict_types=1);

use verbb\formie\elements\Form;
use verbb\formie\fields\Date;
use verbb\formie\fields\FileUpload;
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
    ];

    $field = new Date();

    expect($field->displayType)->toBe('dropdowns')
        ->and($field->defaultOption)->toBe('today');
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

it('applies notification defaults including attachPdf', function (): void {
    Formie::$plugin->getSettings()->notificationDefaults = [
        'fromName' => 'Site Admin',
        'from' => 'admin@example.com',
        'subject' => 'New submission',
        'attachFiles' => true,
        'attachPdf' => true,
        'enabled' => false,
    ];

    $notification = new Notification();
    Formie::$plugin->getFormDefaults()->applyToNewNotification($notification, []);

    expect($notification->fromName)->toBe('Site Admin')
        ->and($notification->from)->toBe('admin@example.com')
        ->and($notification->subject)->toBe('New submission')
        ->and($notification->attachFiles)->toBeTrue()
        ->and((bool)$notification->attachPdf)->toBeTrue()
        ->and($notification->enabled)->toBeFalse();
});

it('exposes schema-backed field type defaults config for supported field types', function (): void {
    $fieldTypes = Formie::$plugin->getFormDefaults()->getFieldTypeDefaultsConfig();
    $types = array_column($fieldTypes, 'type');

    expect($types)->toContain(Date::class, FileUpload::class);

    $dateConfig = collect($fieldTypes)->firstWhere('type', Date::class);

    expect($dateConfig)->not->toBeNull()
        ->and($dateConfig['schema'] ?? null)->not->toBeEmpty()
        ->and($dateConfig['schemaIndex'] ?? null)->toBeArray();
});

it('caches field type defaults config within the service instance', function (): void {
    $service = Formie::$plugin->getFormDefaults();

    expect($service->getFieldTypeDefaultsConfig())->toBe($service->getFieldTypeDefaultsConfig());
});
