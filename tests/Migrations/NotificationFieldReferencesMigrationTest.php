<?php

declare(strict_types=1);

use craft\db\Query;
use verbb\formie\Formie;
use verbb\formie\helpers\Table;
use verbb\formie\migrations\m260913_000000_notification_field_references;
use verbb\formie\models\Notification;

it('migrates notification handles within their own form and preserves current and unknown tokens on rerun', function (): void {
    $expected = [];
    foreach (['First', 'Second'] as $label) {
        $form = formie()->form()->singleLineTextField('fullName')->create();
        $reference = $form->getFieldByHandle('fullName')->reference;
        $notification = new Notification(['formId' => $form->id, 'name' => $label, 'handle' => strtolower($label),
            'subject' => 'Hello {field:fullName}; keep {field:' . $reference . '} and {field:unknown}',
            'to' => 'migration@example.test', 'enabled' => false]);
        $saved = Formie::$plugin->getNotifications()->saveNotification($notification);
        expect($notification->getErrors())->toBe([]);
        expect($saved)->toBeTrue();
        $expected[$notification->id] = 'Hello {field:' . $reference . '}; keep {field:' . $reference . '} and {field:unknown}';
    }
    $migration = new m260913_000000_notification_field_references();
    foreach ([1, 2] as $run) {
        expect($migration->safeUp())->toBeTrue();
        foreach ($expected as $id => $subject) {
            expect((new Query())->select('subject')->from(Table::FORMIE_NOTIFICATIONS)->where(['id' => $id])->scalar())->toBe($subject);
        }
    }
});
