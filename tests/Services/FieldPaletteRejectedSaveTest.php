<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\models\FormGroup;

it('rejects a field palette it cannot save instead of throwing', function (array $payload): void {
    $palette = Formie::$plugin->getFieldPalette();

    expect($palette->savePalette($payload))->toBeFalse()
        ->and($palette->saveGroupPalette(new FormGroup(), $payload))->toBeFalse();
})->with([
    'missing groups' => [['fields' => []]],
    'duplicate group handles' => [['groups' => [
        ['name' => 'First', 'handle' => 'sameHandle', 'fields' => []],
        ['name' => 'Second', 'handle' => 'sameHandle', 'fields' => []],
    ]]],
]);

it('rejects saving a field palette when admin changes are disabled instead of throwing', function (): void {
    $config = Craft::$app->getConfig()->getGeneral();
    $original = $config->allowAdminChanges;
    $config->allowAdminChanges = false;

    try {
        $palette = Formie::$plugin->getFieldPalette();

        expect($palette->savePalette(['groups' => []]))->toBeFalse()
            ->and($palette->saveGroupPalette(new FormGroup(), ['groups' => []]))->toBeFalse();
    } finally {
        $config->allowAdminChanges = $original;
    }
});
