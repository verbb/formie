<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\fields\SingleLineText;
use verbb\formie\models\SubmissionRequest;
use verbb\formie\services\SubmissionWorkflow;

it('captures B1 baseline for final submit workflow path', function (): void {
    $form = formie()
        ->form(['title' => 'Perf B1 Final Submit'])
        ->singleLineTextField('fullName', ['label' => 'Full Name'])
        ->emailField('email')
        ->numberField('score')
        ->create();

    $submission = new Submission();
    $submission->setForm($form);
    $submission->setFieldValueFromRequest('fullName', 'Perf User');
    $submission->setFieldValueFromRequest('email', 'perf@example.test');
    $submission->setFieldValueFromRequest('score', '42');

    $started = microtime(true);
    $response = (new SubmissionWorkflow())->processSubmissionRequest(new SubmissionRequest([
        'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
        'form' => $form,
        'submission' => $submission,
        'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
    ]));
    $elapsedMs = (int)((microtime(true) - $started) * 1000);

    expect($response->success)->toBeTrue()
        ->and($response->submission->id)->not->toBeNull();
    $saved = Submission::find()->id($response->submission->id)->status(null)->one();
    expect($saved->getFieldValue('fullName'))->toBe('Perf User')
        ->and($saved->getFieldValue('email'))->toBe('perf@example.test');
    expect($response->success)->toBeTrue()
        ->and($elapsedMs)->toBeLessThan(5000);
})->group('perf');

it('captures B2 baseline for partial multipage update path', function (): void {
    $settings = Formie::$plugin->getSettings();
    $originalPartialPayload = $settings->setOnlyCurrentPagePayload;
    $settings->setOnlyCurrentPagePayload = true;

    try {
        $form = formie()
            ->form(['title' => 'Perf B2 Partial'])
            ->multiPage(2)
            ->onPage(1)->singleLineTextField('pageOneField')
            ->onPage(2)->singleLineTextField('pageTwoField')
            ->create();

        $pages = $form->getPages();
        expect($pages)->toHaveCount(2);

        $submission = new Submission();
        $submission->setForm($form);
        $submission->setFieldValueFromRequest('pageOneField', 'p1');

        $process = new SubmissionWorkflow();
        $first = $process->processSubmissionRequest(new SubmissionRequest([
            'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
            'form' => $form,
            'submission' => $submission,
            'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
            'pageId' => (int)$pages[0]->id,
        ]));

        $submission = Submission::find()->id($first->submission->id)->status(null)->isIncomplete(null)->one();
        $form->setCurrentPage($pages[1]);
        $submission->setForm($form);
        $submission->setFieldValueFromRequest('pageTwoField', 'p2');

        $started = microtime(true);
        $second = $process->processSubmissionRequest(new SubmissionRequest([
            'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
            'form' => $form,
            'submission' => $submission,
            'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
            'pageId' => (int)$pages[1]->id,
        ]));
        $elapsedMs = (int)((microtime(true) - $started) * 1000);

        expect($second->success)->toBeTrue()
            ->and($second->submission->id)->not->toBeNull();
        $saved = Submission::find()->id($second->submission->id)->status(null)->isIncomplete(null)->one();
        expect($saved->getFieldValue('pageOneField'))->toBe('p1')
            ->and($saved->getFieldValue('pageTwoField'))->toBe('p2');
        expect($first->success)->toBeTrue()
            ->and($second->success)->toBeTrue()
            ->and($elapsedMs)->toBeLessThan(5000);
    } finally {
        $settings->setOnlyCurrentPagePayload = $originalPartialPayload;
    }
})->group('perf');

it('captures B3 baseline for mixed-family normalization pass', function (): void {
    $form = formie()
        ->form(['title' => 'Perf B3 Normalize'])
        ->singleLineTextField('fullName', ['label' => 'Full Name'])
        ->emailField('email')
        ->numberField('score')
        ->create();

    $submission = formie()->submission($form)->with([
        'fullName' => 'Normalize Runner',
        'email' => 'normalize@example.test',
        'score' => '12',
    ])->save();

    $started = microtime(true);

    for ($i = 0; $i < 300; $i++) {
        expect($submission->getFieldValue('fullName'))->toBe('Normalize Runner')
            ->and($submission->getFieldValue('email'))->toBe('normalize@example.test')
            ->and($submission->getFieldValueAsString('score'))->toBe('12');
    }

    $elapsedMs = (int)((microtime(true) - $started) * 1000);

    expect($elapsedMs)->toBeLessThan(5000);
})->group('perf');

it('captures B4 baseline for export and summary projection paths', function (): void {
    $form = formie()
        ->form(['title' => 'Perf B4 Projections'])
        ->singleLineTextField('fullName', ['label' => 'Full Name'])
        ->emailField('email')
        ->groupField('details', [
            'rows' => [[
                'fields' => [[
                    'type' => SingleLineText::class,
                    'handle' => 'company',
                    'label' => 'Company',
                ]],
            ]],
        ])
        ->create();

    $submission = formie()->submission($form)->with([
        'fullName' => 'Projection Runner',
        'email' => 'projection@example.test',
        'details' => ['company' => 'Verbb'],
    ])->save();

    $started = microtime(true);

    for ($i = 0; $i < 150; $i++) {
        $export = $submission->getValuesForExport();
        $summary = $submission->getValuesForSummary();
        expect($export['Full Name'])->toBe('Projection Runner')
            ->and($export['Email'])->toBe('projection@example.test')
            ->and($summary[0]['text'])->toContain('Projection Runner');
    }

    $elapsedMs = (int)((microtime(true) - $started) * 1000);

    expect($elapsedMs)->toBeLessThan(5000);
})->group('perf');
