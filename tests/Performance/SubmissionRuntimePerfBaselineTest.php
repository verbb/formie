<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;
use verbb\formie\fields\SingleLineText;

it('captures submission runtime hotspots for nested projections and query field loading', function (): void {
    $rows = [[
        'fields' => [[
            'type' => SingleLineText::class,
            'handle' => 'innerText',
            'label' => 'Inner Text',
        ]],
    ]];

    $form = formie()
        ->form(['title' => 'Submission Runtime Perf'])
        ->singleLineTextField('fullName')
        ->emailField('email')
        ->groupField('groupContent', ['rows' => $rows])
        ->repeaterField('lineItems', ['rows' => $rows])
        ->create();

    $submission = formie()->submission($form)->with([
        'fullName' => 'Runtime Perf',
        'email' => 'runtime@example.test',
        'groupContent' => ['innerText' => 'Group Value'],
        'lineItems' => [
            ['innerText' => 'Row One'],
            ['innerText' => 'Row Two'],
        ],
    ])->save();

    $projectionMs = measureSubmissionRuntimePerfPhase(function () use ($submission): void {
        for ($i = 0; $i < 150; $i++) {
            $submission->getValuesForExport();
            $submission->getValuesForSummary();
            $submission->getFieldValuesForField(SingleLineText::class);
        }
    });

    $queryMs = measureSubmissionRuntimePerfPhase(function () use ($form): void {
        for ($i = 0; $i < 75; $i++) {
            Submission::find()
                ->form($form)
                ->fullName('Runtime Perf')
                ->all();

            Submission::find()
                ->form($form)
                ->groupContent('Group Value')
                ->all();
        }
    });

    fwrite(STDOUT, sprintf(
        "SUBMISSION_RUNTIME_PERF %s\n",
        json_encode([
            'projectionMs' => $projectionMs,
            'queryMs' => $queryMs,
        ], JSON_UNESCAPED_SLASHES)
    ));

    expect($projectionMs)->toBeLessThan(30000)
        ->and($queryMs)->toBeLessThan(30000);
})->group('perf');

function measureSubmissionRuntimePerfPhase(callable $callback): int
{
    $started = microtime(true);
    $callback();

    return (int)((microtime(true) - $started) * 1000);
}
