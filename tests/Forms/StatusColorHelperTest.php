<?php

declare(strict_types=1);

use craft\enums\Color;
use verbb\formie\Formie;
use verbb\formie\helpers\StatusColorHelper;

it('maps formie status colors to craft color enums', function (): void {
    expect(StatusColorHelper::resolveColor('green'))->toBe(Color::Green)
        ->and(StatusColorHelper::resolveColor('grey'))->toBe(Color::Gray)
        ->and(StatusColorHelper::resolveColor('turquoise'))->toBe(Color::Teal)
        ->and(StatusColorHelper::resolveColor('light'))->toBe(Color::Gray)
        ->and(StatusColorHelper::resolveColor('green', 'active'))->toBe(Color::Teal)
        ->and(StatusColorHelper::resolveColor('orange', 'draft'))->toBe(Color::Orange)
        ->and(StatusColorHelper::resolveColor('green', 'new'))->toBe(Color::Teal)
        ->and(StatusColorHelper::resolveColor('grey', 'archived'))->toBe(Color::Gray);
});

it('returns craft color enums from form status definitions', function (): void {
    $statuses = Formie::$plugin->getFormStatuses()->getStatusesArray();

    expect($statuses)->not->toBeEmpty();

    foreach ($statuses as $status) {
        expect($status['color'])->toBeInstanceOf(Color::class);
    }
})->skip(fn (): bool => !class_exists(\Craft::class) || !\Craft::$app, 'Requires Craft bootstrap');
