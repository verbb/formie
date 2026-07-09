<?php

declare(strict_types=1);

use verbb\formie\helpers\ImportExportHelper;
use verbb\formie\helpers\Table;
use verbb\formie\migrations\m260710_000000_fix_invalid_notification_handles;

use craft\db\Query;

it('fixes invalid notification handles during migration', function (): void {
    $export = [
        'handle' => 'invalidNotificationHandleMigration',
        'title' => 'Invalid Notification Handle Migration',
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
                'handle' => 'enquiryFromTheContactPageOfYourWebsite',
                'enabled' => 1,
                'subject' => 'Enquiry',
                'recipients' => 'email',
                'to' => '{field:name}',
            ],
        ],
        'exportVersion' => 'v4',
    ];

    $importedForm = ImportExportHelper::importFormFromJson($export, 'create');
    $notification = $importedForm->getNotifications()[0] ?? null;

    expect($notification)->not->toBeNull();

    \Craft::$app->getDb()->createCommand()
        ->update(Table::FORMIE_NOTIFICATIONS, [
            'handle' => 'enquiryFromTheContactPageOfYourWebsite.1',
        ], [
            'id' => $notification->id,
        ])
        ->execute();

    $migration = new m260710_000000_fix_invalid_notification_handles();
    expect($migration->safeUp())->toBeTrue();

    $updatedHandle = (new Query())
        ->select(['handle'])
        ->from(Table::FORMIE_NOTIFICATIONS)
        ->where(['id' => $notification->id])
        ->scalar();

    expect($updatedHandle)->toBe('enquiryFromTheContactPageOfYourWebsite');
});
