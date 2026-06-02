<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;

it('keeps a soft baseline for bulk form creation and submission writes', function (): void {
    $started = microtime(true);

    for ($formIndex = 1; $formIndex <= 8; $formIndex++) {
        $builder = formie()->form(['title' => 'Perf Baseline Form ' . $formIndex]);

        for ($fieldIndex = 1; $fieldIndex <= 12; $fieldIndex++) {
            $builder->singleLineTextField("field{$fieldIndex}");
        }

        $form = $builder->create();

        for ($submissionIndex = 1; $submissionIndex <= 15; $submissionIndex++) {
            $payload = [];

            for ($fieldIndex = 1; $fieldIndex <= 12; $fieldIndex++) {
                $payload["field{$fieldIndex}"] = "v-{$formIndex}-{$submissionIndex}-{$fieldIndex}";
            }

            formie()->submission($form)->with($payload)->save();
        }
    }

    $elapsedMs = (int)((microtime(true) - $started) * 1000);

    // Soft guardrail: only fail on severe regressions.
    expect($elapsedMs)->toBeLessThan(90000);
})->group('perf');

it('keeps a soft baseline for repeated custom criteria query loops', function (): void {
    $form = formie()
        ->form(['title' => 'Perf Baseline Query'])
        ->singleLineTextField('fullName')
        ->numberField('score')
        ->create();

    for ($i = 1; $i <= 120; $i++) {
        formie()->submission($form)->with([
            'fullName' => "Baseline {$i}",
            'score' => (string)$i,
        ])->save();
    }

    $started = microtime(true);

    for ($i = 1; $i <= 90; $i++) {
        $results = Submission::find()
            ->formId($form->id)
            ->field('score', (string)$i)
            ->all();

        expect($results)->not->toBeEmpty();
    }

    $elapsedMs = (int)((microtime(true) - $started) * 1000);

    expect($elapsedMs)->toBeLessThan(60000);
})->group('perf');
