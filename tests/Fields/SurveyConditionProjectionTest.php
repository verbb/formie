<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;
use verbb\formie\fields\Survey;

it('projects persisted multi-row Likert answers for conditions', function (array $answers, ?array $expected): void {
    $form = formie()->form()->addField(Survey::class, 'feedback', [
        'displayType' => Survey::DISPLAY_LIKERT,
        'multipleRowsEnabled' => true,
        'likertRows' => [
            ['label' => 'Service', 'value' => 'service'],
            ['label' => 'Quality', 'value' => 'quality'],
        ],
        'options' => [['label' => 'No', 'value' => '0'], ['label' => 'Yes', 'value' => '1']],
    ])->create();
    $saved = formie()->submission($form)->with(['feedback' => $answers])->save();
    $loaded = Submission::find()->id($saved->id)->status(null)->one();

    expect($loaded->getFieldValueForCondition('feedback'))->toBe($expected);
})->with([
    'both rows' => [['service' => '1', 'quality' => '0'], ['service' => '1', 'quality' => '0']],
    'partial zero answer' => [['service' => '0'], ['service' => '0']],
    'empty' => [[], null],
]);
