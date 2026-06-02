<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;

it('queries date fields by civil date part criteria for partial values', function (): void {
    $form = formie()
        ->form(['title' => 'Date Part Query'])
        ->dateField('dob', [
            'displayType' => 'inputs',
            'dateFormat' => 'Y-m',
        ])
        ->create();

    $matchingSubmission = formie()->submission($form)->with([
        'dob' => [
            'year' => '2026',
            'month' => '02',
        ],
    ])->save();

    formie()->submission($form)->with([
        'dob' => [
            'year' => '2025',
            'month' => '02',
        ],
    ])->save();

    $results = Submission::find()
        ->formId($form->id)
        ->field('dob', ['year' => 2026, 'month' => 2])
        ->all();

    expect($results)->toHaveCount(1)
        ->and($results[0]->id)->toBe($matchingSubmission->id);
});

it('supports legacy-style date range operators against comparable part keys', function (): void {
    $form = formie()
        ->form(['title' => 'Date Legacy Operator Query'])
        ->dateField('dob', [
            'displayType' => 'inputs',
            'dateFormat' => 'Y-m-d',
            'timeFormat' => 'H:i',
        ])
        ->create();

    $inRange = formie()->submission($form)->with([
        'dob' => [
            'year' => '2024',
            'month' => '03',
            'day' => '10',
            'hour' => '12',
            'minute' => '00',
        ],
    ])->save();

    formie()->submission($form)->with([
        'dob' => [
            'year' => '2024',
            'month' => '08',
            'day' => '10',
            'hour' => '12',
            'minute' => '00',
        ],
    ])->save();

    $results = Submission::find()
        ->formId($form->id)
        ->field('dob', ['and', '>= 2024-01-01', '< 2024-06-01'])
        ->all();

    expect($results)->toHaveCount(1)
        ->and($results[0]->id)->toBe($inRange->id);
});
