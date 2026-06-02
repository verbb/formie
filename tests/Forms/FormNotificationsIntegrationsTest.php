<?php

declare(strict_types=1);

use verbb\formie\elements\Form;
use verbb\formie\models\Notification;

it('supports forms with no notifications by default', function (): void {
    $form = formie()
        ->form(['title' => 'No Notifications'])
        ->singleLineTextField('fullName')
        ->create();

    expect($form->getNotifications())->toBeArray();
});

it('persists a basic notification attachment at form level', function (): void {
    $form = formie()
        ->form(['title' => 'Notification Attachment'])
        ->emailField('email')
        ->create();

    $notification = new Notification([
        'name' => 'Admin Notification',
        'handle' => 'adminNotification' . uniqid(),
        'enabled' => true,
        'subject' => 'New submission',
        'to' => 'admin@example.test',
    ]);

    $form->setNotifications([$notification]);
    $saved = Craft::$app->elements->saveElement($form);
    $reloaded = Form::find()->id($form->id)->one();

    expect($saved)->toBeTrue()
        ->and($reloaded?->getNotifications())->toBeArray()
        ->and(count($reloaded?->getNotifications() ?? []))->toBeGreaterThanOrEqual(1);
});

it('persists integration settings payload shape', function (): void {
    $form = formie()
        ->form(['title' => 'Integration Settings'])
        ->singleLineTextField('fullName')
        ->create();

    $form->settings->setAttributes([
        'integrations' => [
            'exampleIntegration' => [
                'enabled' => true,
                'customValue' => 'abc123',
            ],
        ],
    ], false);

    $saved = Craft::$app->elements->saveElement($form);
    $reloaded = Form::find()->id($form->id)->one();

    expect($saved)->toBeTrue()
        ->and($reloaded?->settings->integrations)->toHaveKey('exampleIntegration')
        ->and($reloaded?->settings->integrations['exampleIntegration']['enabled'])->toBeTrue()
        ->and($reloaded?->settings->integrations['exampleIntegration']['customValue'])->toBe('abc123');
});
