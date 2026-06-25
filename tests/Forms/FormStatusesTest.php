<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\models\FormStatus;

it('resolves form status handles to ids', function (): void {
    $service = Formie::$plugin->getFormStatuses();
    $active = $service->getStatusByHandle('active');

    expect($active)->not->toBeNull()
        ->and($service->resolveStatusId('active'))->toBe((int)$active->id)
        ->and($service->resolveStatusId('draft'))->toBeInt();
})->skip(fn (): bool => !class_exists(\Craft::class) || !\Craft::$app, 'Requires Craft bootstrap');

it('prevents deleting the default form status', function (): void {
    $default = Formie::$plugin->getFormStatuses()->getDefaultStatus();

    expect($default)->not->toBeNull()
        ->and($default->canDelete())->toBeFalse();
})->skip(fn (): bool => !class_exists(\Craft::class) || !\Craft::$app, 'Requires Craft bootstrap');

it('allows deleting unused non-default form statuses', function (): void {
    $status = new FormStatus([
        'name' => 'Temporary',
        'handle' => 'temporary' . uniqid(),
        'color' => 'blue',
    ]);

    expect(Formie::$plugin->getFormStatuses()->saveStatus($status))->toBeTrue()
        ->and($status->canDelete())->toBeTrue();
})->skip(fn (): bool => !class_exists(\Craft::class) || !\Craft::$app, 'Requires Craft bootstrap');

it('resolves missing or invalid form status ids to a configured status', function (): void {
    $service = Formie::$plugin->getFormStatuses();
    $default = $service->getDefaultStatus();

    expect($default)->not->toBeNull()
        ->and($service->resolveStatus(null)?->id)->toBe((int)$default->id)
        ->and($service->resolveStatus(999999)?->id)->toBe((int)$default->id);
})->skip(fn (): bool => !class_exists(\Craft::class) || !\Craft::$app, 'Requires Craft bootstrap');
