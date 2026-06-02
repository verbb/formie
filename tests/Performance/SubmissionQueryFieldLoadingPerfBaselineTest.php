<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;
use verbb\formie\fields\SingleLineText;

it('captures submission query field-loading baseline for repeated criteria queries', function (): void {
    $rows = [[
        'fields' => [[
            'type' => SingleLineText::class,
            'handle' => 'innerText',
            'label' => 'Inner Text',
        ]],
    ]];

    $form = formie()
        ->form(['title' => 'Submission Query Field Loading Perf'])
        ->singleLineTextField('fullName')
        ->groupField('groupContent', ['rows' => $rows])
        ->repeaterField('lineItems', ['rows' => $rows])
        ->create();

    formie()->submission($form)->with([
        'fullName' => 'Needle',
        'groupContent' => ['innerText' => 'Group Needle'],
        'lineItems' => [
            ['innerText' => 'Row Needle'],
            ['innerText' => 'Other'],
        ],
    ])->save();

    formie()->submission($form)->with([
        'fullName' => 'Haystack',
        'groupContent' => ['innerText' => 'Group Haystack'],
        'lineItems' => [
            ['innerText' => 'Row Haystack'],
        ],
    ])->save();

    $criteriaQueryMs = measureSubmissionQueryFieldLoadingPerfPhase(function () use ($form): void {
        for ($i = 0; $i < 80; $i++) {
            Submission::find()
                ->form($form)
                ->fullName('Needle')
                ->all();

            Submission::find()
                ->form($form)
                ->groupContent(['innerText' => 'Group Needle'])
                ->all();

            Submission::find()
                ->form($form)
                ->lineItems(['innerText' => 'Row Needle'])
                ->all();
        }
    });

    fwrite(STDOUT, sprintf(
        "SUBMISSION_QUERY_FIELD_LOADING_PERF %s\n",
        json_encode([
            'criteriaQueryMs' => $criteriaQueryMs,
        ], JSON_UNESCAPED_SLASHES)
    ));

    expect($criteriaQueryMs)->toBeLessThan(30000);
})->group('perf');

function measureSubmissionQueryFieldLoadingPerfPhase(callable $callback): int
{
    $started = microtime(true);
    $callback();

    return (int)((microtime(true) - $started) * 1000);
}
