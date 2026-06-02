<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;

it('handles large form and high submission volume profile', function (): void {
    $builder = formie()->form(['title' => 'Perf Large Form']);

    for ($i = 1; $i <= 35; $i++) {
        $builder->singleLineTextField("text{$i}");
    }

    $form = $builder->create();

    for ($submissionIndex = 1; $submissionIndex <= 40; $submissionIndex++) {
        $payload = [];

        for ($fieldIndex = 1; $fieldIndex <= 35; $fieldIndex++) {
            $payload["text{$fieldIndex}"] = "value-{$submissionIndex}-{$fieldIndex}";
        }

        formie()->submission($form)->with($payload)->save();
    }

    $count = (int)Submission::find()->formId($form->id)->count();
    expect($count)->toBe(40);
})->group('perf');

it('handles repeated custom field criteria queries profile', function (): void {
    $form = formie()
        ->form(['title' => 'Perf Query'])
        ->singleLineTextField('fullName')
        ->numberField('score')
        ->create();

    for ($i = 1; $i <= 60; $i++) {
        formie()->submission($form)->with([
            'fullName' => "Profile User {$i}",
            'score' => (string)$i,
        ])->save();
    }

    for ($i = 1; $i <= 25; $i++) {
        $results = Submission::find()
            ->formId($form->id)
            ->field('score', (string)$i)
            ->all();

        expect($results)->not->toBeEmpty();
    }
})->group('perf');
