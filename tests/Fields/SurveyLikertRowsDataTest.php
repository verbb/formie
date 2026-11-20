<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;
use verbb\formie\fields\Survey;

// Integrations and delivery snapshots read answers through getValuesAsData(), which must include Likert scales with rows.
it('includes each row of a Likert scale in the submission data', function(): void {
    $form = formie()->form()->addField(Survey::class, 'rating', [
        'label' => 'Rating',
        'displayType' => Survey::DISPLAY_LIKERT,
        'multipleRowsEnabled' => true,
        'likertRows' => [['label' => 'Speed', 'value' => 'speed'], ['label' => 'Quality', 'value' => 'quality']],
        'options' => [['label' => 'Good', 'value' => 'good'], ['label' => 'Bad', 'value' => 'bad']],
    ])->create();

    try {
        $submission = new Submission(['formId' => $form->id]);
        $submission->setFieldValue('rating', ['speed' => 'good', 'quality' => 'bad']);

        expect($submission->getValuesAsData()['rating'])->toBe(['speed' => 'good', 'quality' => 'bad']);
    } finally {
        Craft::$app->getElements()->deleteElement($form, true);
    }
});
