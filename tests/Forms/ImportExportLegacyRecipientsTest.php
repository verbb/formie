<?php

declare(strict_types=1);

use verbb\formie\fields\Recipients;
use verbb\formie\helpers\ImportExportHelper;

it('imports forms with legacy recipient placeholder options', function (): void {
    $export = [
        'handle' => 'legacyRecipientsImport',
        'title' => 'Legacy Recipients Import',
        'settings' => '{}',
        'pages' => [
            [
                'label' => 'Page 1',
                'settings' => [],
                'rows' => [
                    [
                        'fields' => [
                            [
                                'type' => Recipients::class,
                                'settings' => [
                                    'label' => 'Region',
                                    'handle' => 'contactRegion',
                                    'displayType' => 'dropdown',
                                    'options' => [
                                        [
                                            'label' => 'Click here to select a contact ...',
                                            'value' => 'Click here to select a contact ...',
                                            'isDefault' => true,
                                        ],
                                        [
                                            'label' => 'ACT',
                                            'value' => 'act@example.com',
                                            'isDefault' => false,
                                        ],
                                        [
                                            'label' => 'NT: Darwin........................',
                                            'value' => 'NT: Darwin........................',
                                            'isDefault' => false,
                                        ],
                                    ],
                                    'optionsMode' => 'static',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'notifications' => [],
        'exportVersion' => 'v4',
    ];

    $importedForm = ImportExportHelper::importFormFromJson($export, 'create');

    expect($importedForm->hasErrors())->toBeFalse()
        ->and($importedForm->id)->toBeGreaterThan(0);

    $field = $importedForm->getFields()[0] ?? null;

    expect($field)->toBeInstanceOf(Recipients::class)
        ->and($field->hasErrors('options'))->toBeFalse()
        ->and($field->options[0]['value'] ?? null)->toBe('')
        ->and($field->options[0]['default'] ?? null)->toBeFalse();
});

it('clears stale date subfield defaults when importing today defaults', function (): void {
    $export = [
        'handle' => 'todayDateImport',
        'title' => 'Today Date Import',
        'settings' => '{}',
        'pages' => [
            [
                'label' => 'Page 1',
                'settings' => [],
                'rows' => [
                    [
                        'fields' => [
                            [
                                'type' => \verbb\formie\fields\Date::class,
                                'settings' => [
                                    'label' => 'Date Signed',
                                    'handle' => 'dateSigned',
                                    'displayType' => 'datePicker',
                                    'defaultOption' => 'today',
                                    'rows' => [
                                        [
                                            'fields' => [
                                                [
                                                    'type' => 'verbb\\formie\\fields\\subfields\\DateDate',
                                                    'settings' => [
                                                        'handle' => 'date',
                                                        'defaultValue' => '2024-09-26T09:50:07+10:00',
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'notifications' => [],
        'exportVersion' => 'v4',
    ];

    $importedForm = ImportExportHelper::importFormFromJson($export, 'create');

    expect($importedForm->hasErrors())->toBeFalse();

    $field = $importedForm->getFields()[0] ?? null;

    expect($field)->toBeInstanceOf(\verbb\formie\fields\Date::class);

    $subField = $field->getFields()[0] ?? null;

    expect($subField?->defaultValue)->toBeNull();
});

it('sanitizes invalid notification handles during import', function (): void {
    $export = [
        'handle' => 'legacyNotificationImport',
        'title' => 'Legacy Notification Import',
        'settings' => '{}',
        'pages' => [
            [
                'label' => 'Page 1',
                'settings' => [],
                'rows' => [
                    [
                        'fields' => [
                            [
                                'type' => \verbb\formie\fields\SingleLineText::class,
                                'settings' => [
                                    'label' => 'Name',
                                    'handle' => 'name',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'notifications' => [
            [
                'name' => 'Enquiry from the Contact page of your website.',
                'handle' => 'enquiryFromTheContactPageOfYourWebsite.1',
                'enabled' => 1,
                'subject' => 'Enquiry',
                'recipients' => 'email',
                'to' => '{field:name}',
            ],
        ],
        'exportVersion' => 'v4',
    ];

    $importedForm = ImportExportHelper::importFormFromJson($export, 'create');

    expect($importedForm->hasErrors())->toBeFalse()
        ->and($importedForm->getNotifications()[0]->handle ?? null)
            ->toBe('enquiryFromTheContactPageOfYourWebsite');
});
