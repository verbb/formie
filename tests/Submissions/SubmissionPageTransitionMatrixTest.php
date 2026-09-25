<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;
use verbb\formie\models\SubmissionCommand;
use verbb\formie\services\SubmissionWorkflow;

function runPageStep(SubmissionWorkflow $process, SubmissionCommand $request): mixed
{
    return runSubmissionCommand($request);
}

it('keeps canonical multipage page-transition behavior for submit/back/save and target-page navigation', function (): void {
    $form = formie()
        ->form(['title' => 'Page Transition Matrix'])
        ->multiPage(3)
        ->onPage(1)->singleLineTextField('pageOneField')
        ->onPage(2)->singleLineTextField('pageTwoField')
        ->onPage(3)->singleLineTextField('pageThreeField')
        ->create();

    $pages = $form->getPages();
    expect($pages)->toHaveCount(3);

    $submission = new Submission();
    $submission->setForm($form);
    $process = new SubmissionWorkflow();

    $submission->setFieldValueFromRequest('pageOneField', 'one');
    $step1 = runPageStep($process, submissionCommand([
        'operation' => \verbb\formie\enums\SubmissionOperation::SUBMIT,
        'form' => $form,
        'submission' => $submission,
        'navigation' => \verbb\formie\enums\NavigationIntent::ADVANCE,
        'pageId' => (int)$pages[0]->id,
    ]));

    $submission = $step1->submission;
    $submission->setFieldValueFromRequest('pageOneField', 'one');
    $submission->setFieldValueFromRequest('pageTwoField', 'two');
    $step2 = runPageStep($process, submissionCommand([
        'operation' => \verbb\formie\enums\SubmissionOperation::SUBMIT,
        'form' => $form,
        'submission' => $submission,
        'navigation' => \verbb\formie\enums\NavigationIntent::ADVANCE,
        'pageId' => (int)$pages[1]->id,
        'targetPageId' => (int)$pages[2]->id,
    ]));

    $submission = $step2->submission;
    $back = runPageStep($process, submissionCommand([
        'operation' => \verbb\formie\enums\SubmissionOperation::SUBMIT,
        'form' => $form,
        'submission' => $submission,
        'navigation' => \verbb\formie\enums\NavigationIntent::BACK,
        'pageId' => (int)$pages[2]->id,
    ]));

    $submission = $back->submission;
    $save = runPageStep($process, submissionCommand([
        'operation' => \verbb\formie\enums\SubmissionOperation::SAVE_DRAFT,
        'form' => $form,
        'submission' => $submission,
        'navigation' => \verbb\formie\enums\NavigationIntent::STAY,
        'pageId' => (int)$pages[1]->id,
    ]));

    expect($step1->success)->toBeTrue()
        ->and($step1->nextPage?->id)->toBe($pages[1]->id)
        ->and($step2->success)->toBeTrue()
        ->and($step2->nextPage?->id)->toBe($pages[2]->id)
        ->and($back->success)->toBeTrue()
        ->and($back->nextPage?->id)->toBe($pages[1]->id)
        ->and($save->success)->toBeTrue()
        ->and($save->nextPage?->id)->toBe($pages[1]->id);
});

it('keeps the same transition contract for ajax submit-method forms', function (): void {
    $form = formie()
        ->form([
            'title' => 'Page Transition Matrix Ajax',
            'settings' => [
                'submitMethod' => 'ajax',
            ],
        ])
        ->multiPage(2)
        ->onPage(1)->singleLineTextField('firstName')
        ->onPage(2)->singleLineTextField('lastName')
        ->create();

    $pages = $form->getPages();
    expect($pages)->toHaveCount(2);

    $submission = new Submission();
    $submission->setForm($form);
    $submission->setFieldValueFromRequest('firstName', 'Ajax');
    $process = new SubmissionWorkflow();

    $forward = runPageStep($process, submissionCommand([
        'operation' => \verbb\formie\enums\SubmissionOperation::SUBMIT,
        'form' => $form,
        'submission' => $submission,
        'navigation' => \verbb\formie\enums\NavigationIntent::ADVANCE,
        'pageId' => (int)$pages[0]->id,
    ]));

    $submission = $forward->submission;
    $submission->setFieldValueFromRequest('firstName', 'Ajax');
    $back = runPageStep($process, submissionCommand([
        'operation' => \verbb\formie\enums\SubmissionOperation::SUBMIT,
        'form' => $form,
        'submission' => $submission,
        'navigation' => \verbb\formie\enums\NavigationIntent::BACK,
        'pageId' => (int)$pages[1]->id,
    ]));

    expect($forward->success)->toBeTrue()
        ->and($forward->nextPage?->id)->toBe($pages[1]->id)
        ->and($back->success)->toBeTrue()
        ->and($back->nextPage?->id)->toBe($pages[0]->id);
});
