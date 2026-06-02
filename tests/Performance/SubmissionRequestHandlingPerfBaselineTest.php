<?php

declare(strict_types=1);

use verbb\formie\content\SubmissionContentNormalizer;
use verbb\formie\elements\Submission;

it('captures current-page field handle lookup baseline', function (): void {
    $form = formie()
        ->form(['title' => 'Submission Current Page Handle Perf'])
        ->multiPage(3)
        ->onPage(1)->singleLineTextField('pageOneA')
        ->onPage(1)->singleLineTextField('pageOneB')
        ->onPage(1)->singleLineTextField('pageOneC')
        ->onPage(2)->singleLineTextField('pageTwoA')
        ->onPage(2)->singleLineTextField('pageTwoB')
        ->onPage(2)->singleLineTextField('pageTwoC')
        ->onPage(2)->singleLineTextField('pageTwoD')
        ->onPage(3)->singleLineTextField('pageThreeA')
        ->onPage(3)->singleLineTextField('pageThreeB')
        ->create();

    $submission = new Submission();
    $submission->setForm($form);

    $pages = $form->getPages();
    expect($pages)->toHaveCount(3);

    $form->setCurrentPage($pages[1]);

    $manager = $submission->getContentManager();
    $lookup = new ReflectionMethod($manager, '_currentPageFieldHandles');
    $lookup->setAccessible(true);

    $coldLookupMs = measureSubmissionRequestPerfPhase(function () use ($lookup, $manager, $submission): void {
        $lookup->invoke($manager, $submission);
    });

    $warmLookupMs = measureSubmissionRequestPerfPhase(function () use ($lookup, $manager, $submission): void {
        for ($i = 0; $i < 5000; $i++) {
            $lookup->invoke($manager, $submission);
        }
    });

    fwrite(STDOUT, sprintf(
        "SUBMISSION_REQUEST_HANDLING_PERF %s\n",
        json_encode([
            'currentPageColdLookupMs' => $coldLookupMs,
            'currentPageWarmLookupMs' => $warmLookupMs,
        ], JSON_UNESCAPED_SLASHES)
    ));

    expect($coldLookupMs)->toBeLessThan(30000)
        ->and($warmLookupMs)->toBeLessThan(30000);
})->group('perf');

it('captures request payload normalization baseline for mostly non-file forms', function (): void {
    $builder = formie()->form(['title' => 'Submission Normalize Request Perf']);

    for ($i = 1; $i <= 40; $i++) {
        $builder->singleLineTextField("textField{$i}");
    }

    $form = $builder
        ->emailField('emailField')
        ->fileUploadField('attachments', ['restrictFiles' => false])
        ->create();

    $submission = new Submission();
    $submission->setForm($form);

    $normalizer = new SubmissionContentNormalizer();

    $normalizeMs = measureSubmissionRequestPerfPhase(function () use ($normalizer, $submission): void {
        for ($i = 0; $i < 120; $i++) {
            $normalizer->normalizeRequestPayload($submission, [
                'textField1' => 'Needle',
                'emailField' => 'perf@example.test',
            ], 'fields');
        }
    });

    fwrite(STDOUT, sprintf(
        "SUBMISSION_REQUEST_HANDLING_PERF %s\n",
        json_encode([
            'normalizeRequestPayloadMs' => $normalizeMs,
        ], JSON_UNESCAPED_SLASHES)
    ));

    expect($normalizeMs)->toBeLessThan(30000);
})->group('perf');

function measureSubmissionRequestPerfPhase(callable $callback): int
{
    $started = microtime(true);
    $callback();

    return (int)((microtime(true) - $started) * 1000);
}
