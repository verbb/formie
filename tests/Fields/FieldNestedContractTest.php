<?php

declare(strict_types=1);

use verbb\formie\fields\SingleLineText;

it('supports nested parent field families in layout and submission payloads', function (): void {
    $rows = [[
        'fields' => [[
            'type' => SingleLineText::class,
            'handle' => 'innerText',
            'label' => 'Inner Text',
        ]],
    ]];

    $form = formie()
        ->form(['title' => 'Nested Fields'])
        ->groupField('groupContent', ['rows' => $rows])
        ->repeaterField('lineItems', ['rows' => $rows])
        ->create();

    $submission = formie()
        ->submission($form)
        ->with([
            'groupContent' => ['innerText' => 'Group Value'],
            'lineItems' => [
                ['innerText' => 'Row One'],
                ['innerText' => 'Row Two'],
            ],
        ])
        ->save();

    expect($form->getFieldByHandle('groupContent'))->not->toBeNull()
        ->and($form->getFieldByHandle('lineItems'))->not->toBeNull()
        ->and($submission->id)->not->toBeNull()
        ->and($submission->getFieldValue('groupContent.innerText'))->toBe('Group Value')
        ->and($submission->getFieldValue('lineItems.0.innerText'))->toBe('Row One')
        ->and($submission->getFieldValue('lineItems.1.innerText'))->toBe('Row Two')
        ->and($submission->serializeFieldValues())->toBeArray()
        ->and($submission->serializeFieldValues())->not->toBeEmpty();
});
