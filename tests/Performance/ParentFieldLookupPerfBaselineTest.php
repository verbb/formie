<?php

declare(strict_types=1);

use verbb\formie\fields\Name;
use verbb\formie\fields\SingleLineText;
use verbb\formie\fields\Number;
use verbb\formie\fields\subfields\NameFirst;
use verbb\formie\fields\subfields\NameLast;

it('captures nested parent field handle lookup baseline', function (): void {
    $groupRows = [[
        'fields' => [
            ['type' => SingleLineText::class, 'handle' => 'innerText', 'label' => 'Inner Text'],
            ['type' => Number::class, 'handle' => 'innerScore', 'label' => 'Inner Score'],
        ],
    ]];

    $nameRows = [[
        'fields' => [
            ['type' => NameFirst::class, 'handle' => 'firstName', 'label' => 'First Name', 'enabled' => true],
            ['type' => NameLast::class, 'handle' => 'lastName', 'label' => 'Last Name', 'enabled' => true],
        ],
    ]];

    $form = formie()
        ->form(['title' => 'Parent Field Lookup Perf'])
        ->nameField('multiName', [
            'useMultipleFields' => true,
            'rows' => $nameRows,
        ])
        ->groupField('groupContent', ['rows' => $groupRows])
        ->repeaterField('lineItems', ['rows' => $groupRows])
        ->create();

    /** @var Name $nameField */
    $nameField = $form->getFieldByHandle('multiName');
    $groupField = $form->getFieldByHandle('groupContent');
    $repeaterField = $form->getFieldByHandle('lineItems');

    expect($nameField)->not->toBeNull()
        ->and($groupField)->not->toBeNull()
        ->and($repeaterField)->not->toBeNull();

    // Measure the first probe separately because the cache only exists to make the repeated
    // steady-state lookups cheap after nested layouts have already been hydrated once.
    $coldLookupMs = measureParentFieldLookupPerfPhase(function () use ($nameField, $groupField, $repeaterField): void {
        $nameField->getFieldByHandle('firstName');
        $nameField->getFieldByHandle('lastName');
        $groupField->getFieldByHandle('innerText');
        $groupField->getFieldByHandle('innerScore');
        $repeaterField->getFieldByHandle('innerText');
        $repeaterField->getFieldByHandle('innerScore');
    });

    $warmLookupMs = measureParentFieldLookupPerfPhase(function () use ($nameField, $groupField, $repeaterField): void {
        for ($i = 0; $i < 5000; $i++) {
            $nameField->getFieldByHandle('firstName');
            $nameField->getFieldByHandle('lastName');
            $groupField->getFieldByHandle('innerText');
            $groupField->getFieldByHandle('innerScore');
            $repeaterField->getFieldByHandle('innerText');
            $repeaterField->getFieldByHandle('innerScore');
        }
    });

    fwrite(STDOUT, sprintf(
        "PARENT_FIELD_LOOKUP_PERF %s\n",
        json_encode([
            'coldLookupMs' => $coldLookupMs,
            'warmLookupMs' => $warmLookupMs,
        ], JSON_UNESCAPED_SLASHES)
    ));

    expect($coldLookupMs)->toBeLessThan(30000)
        ->and($warmLookupMs)->toBeLessThan(30000);
})->group('perf');

function measureParentFieldLookupPerfPhase(callable $callback): int
{
    $started = microtime(true);
    $callback();

    return (int)((microtime(true) - $started) * 1000);
}
