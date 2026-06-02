<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;

it('keeps custom field filtering deterministic when withCustomFields is disabled', function (): void {
    $form = formie()
        ->form(['title' => 'Query Custom Fields Disabled'])
        ->singleLineTextField('fullName')
        ->create();

    $submissionA = formie()->submission($form)->with(['fullName' => 'Needle'])->save();
    formie()->submission($form)->with(['fullName' => 'Haystack One'])->save();
    formie()->submission($form)->with(['fullName' => 'Haystack Two'])->save();

    $explicitResults = Submission::find()
        ->formId($form->id)
        ->withCustomFields(false)
        ->field('fullName', 'Needle')
        ->all();

    $dynamicResults = Submission::find()
        ->formId($form->id)
        ->withCustomFields(false)
        ->fullName('Needle')
        ->all();

    expect($explicitResults)->toHaveCount(1)
        ->and($explicitResults[0]->id)->toBe($submissionA->id);
    expect($dynamicResults)->toHaveCount(1)
        ->and($dynamicResults[0]->id)->toBe($submissionA->id);
});
