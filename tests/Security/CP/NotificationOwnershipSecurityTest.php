<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\models\Notification;

function notificationOwnershipFixture(int $formId, string $prefix): Notification
{
    $notification = new Notification([
        'formId' => $formId,
        'name' => $prefix . ' notification',
        'handle' => $prefix . bin2hex(random_bytes(6)),
        'enabled' => true,
        'subject' => $prefix . ' subject',
        'to' => 'recipient@example.test',
    ]);
    expect(Formie::$plugin->getNotifications()->saveNotification($notification))->toBeTrue();

    return $notification;
}

it('never reparents an existing notification through a forged form save', function (): void {
    $formA = formie()->form(['title' => 'Notification Owner A'])->create();
    $formB = formie()->form(['title' => 'Notification Owner B'])->create();
    $target = notificationOwnershipFixture((int)$formB->id, 'targetOwner');
    $forged = new Notification($target->getAttributes());
    $forged->name = 'Forged notification';
    $forged->subject = 'Forged subject';
    $formA->setNotifications([$forged]);

    expect(Craft::$app->getElements()->saveElement($formA))->toBeTrue();

    $stored = Formie::$plugin->getNotifications()->getNotificationById((int)$target->id);
    expect($stored->formId)->toBe((int)$formB->id)
        ->and($stored->name)->toBe($target->name)
        ->and($stored->subject)->toBe($target->subject)
        ->and($forged->getErrors('id'))->not->toBeEmpty();

    $stored->subject = 'Legitimate update';
    expect(Formie::$plugin->getNotifications()->saveNotification($stored))->toBeTrue()
        ->and(Formie::$plugin->getNotifications()->getNotificationById((int)$target->id)->subject)->toBe('Legitimate update');

    $created = notificationOwnershipFixture((int)$formA->id, 'newOwner');
    expect($created->id)->not->toBeNull()
        ->and($created->id)->not->toBe($target->id)
        ->and($created->formId)->toBe((int)$formA->id);
})->group('security');
