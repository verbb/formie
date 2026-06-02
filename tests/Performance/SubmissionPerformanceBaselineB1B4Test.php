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
        ->singleLineTextField('fullName')
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
        ->and($elapsedMs)->toBeLessThan(20000);
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

        $submission = $first->submission;
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

        expect($first->success)->toBeTrue()
            ->and($second->success)->toBeTrue()
            ->and($elapsedMs)->toBeLessThan(20000);
    } finally {
        $settings->setOnlyCurrentPagePayload = $originalPartialPayload;
    }
})->group('perf');

it('captures B3 baseline for mixed-family normalization pass', function (): void {
    $form = formie()
        ->form(['title' => 'Perf B3 Normalize'])
        ->singleLineTextField('fullName')
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
        $submission->getFieldValue('fullName');
        $submission->getFieldValue('email');
        $submission->getFieldValue('score');
    }

    $elapsedMs = (int)((microtime(true) - $started) * 1000);

    expect($elapsedMs)->toBeLessThan(20000);
})->group('perf');

it('captures B4 baseline for export and summary projection paths', function (): void {
    $form = formie()
        ->form(['title' => 'Perf B4 Projections'])
        ->singleLineTextField('fullName')
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
        $submission->getValuesForExport();
        $submission->getValuesForSummary();
    }

    $elapsedMs = (int)((microtime(true) - $started) * 1000);

    expect($elapsedMs)->toBeLessThan(30000);
})->group('perf');
