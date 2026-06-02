<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;

it('returns queried submissions with usable projection/value helper contracts', function (): void {
    $form = formie()
        ->form(['title' => 'Query Projection'])
        ->singleLineTextField('fullName')
        ->emailField('email')
        ->create();

    $saved = formie()
        ->submission($form)
        ->with([
            'fullName' => 'Projection Name',
            'email' => 'projection@example.test',
        ])
        ->save();

    $queried = Submission::find()->id($saved->id)->one();

    expect($queried)->not->toBeNull()
        ->and($queried->getFieldValue('fullName'))->not->toBeNull()
        ->and($queried->getValuesAsString())->toBeArray()
        ->and($queried->getValuesAsArray())->toBeArray()
        ->and($queried->getValuesForExport())->toBeArray()
        ->and($queried->getValuesForSummary())->toBeArray();
});
