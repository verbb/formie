<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\helpers\Table;
use verbb\formie\models\SubmissionStatus;
use verbb\formie\elements\Submission;

it('filters submissions by status name when the label differs from the handle', function (): void {
    $status = new SubmissionStatus([
        'name' => 'May Be',
        'handle' => 'maybe',
        'color' => 'orange',
    ]);

    expect(Formie::$plugin->getSubmissionStatuses()->saveStatus($status))->toBeTrue()
        ->and($status->id)->not->toBeNull();

    $form = formie()
        ->form(['title' => 'Status Filter By Name'])
        ->singleLineTextField('fullName')
        ->create();

    $submission = formie()->submission($form)->with(['fullName' => 'Filter Me'])->save();

    $submission->statusId = $status->id;
    expect(\Craft::$app->elements->saveElement($submission))->toBeTrue();

    $byHandle = Submission::find()
        ->formId($form->id)
        ->anyStatus()
        ->status('maybe')
        ->ids()
    ;

    $byName = Submission::find()
        ->formId($form->id)
        ->anyStatus()
        ->status('May Be')
        ->ids()
    ;

    expect($byHandle)->toBe([$submission->id])
        ->and($byName)->toBe([$submission->id]);
});

it('persists default statusId when saving a submission without one', function (): void {
    $form = formie()
        ->form(['title' => 'Status Persistence'])
        ->singleLineTextField('fullName')
        ->create();

    $defaultStatus = $form->getDefaultStatus();
    expect($defaultStatus)->not->toBeNull();

    $submission = new Submission();
    $submission->setForm($form);
    $submission->title = 'Direct Save Submission';
    $submission->setFieldValueFromRequest('fullName', 'Persist Status');

    expect($submission->statusId)->toBeNull()
        ->and(\Craft::$app->elements->saveElement($submission))->toBeTrue()
        ->and($submission->statusId)->toBe($defaultStatus->id);

    $storedStatusId = (new \craft\db\Query())
        ->select(['statusId'])
        ->from([Table::FORMIE_SUBMISSIONS])
        ->where(['id' => $submission->id])
        ->scalar()
    ;

    expect((int)$storedStatusId)->toBe($defaultStatus->id);
});
