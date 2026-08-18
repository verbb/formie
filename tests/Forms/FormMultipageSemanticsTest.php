<?php

declare(strict_types=1);

use Craft;
use verbb\formie\conditions\ConditionOperator;
use verbb\formie\elements\Submission;
use verbb\formie\models\SubmissionRequest;
use verbb\formie\services\SubmissionWorkflow;

it('resolves current and next pages for multipage forms', function (): void {
    $form = formie()
        ->form(['title' => 'Multipage Semantics'])
        ->multiPage(3)
        ->onPage(1)->singleLineTextField('pageOne')
        ->onPage(2)->singleLineTextField('pageTwo')
        ->onPage(3)->singleLineTextField('pageThree')
        ->create();

    $pages = $form->getPages();
    $current = $form->getCurrentPage();
    $next = $form->getNextPage($current);
    $lastNext = $form->getNextPage($pages[2]);

    expect($current)->not->toBeNull()
        ->and($next)->not->toBeNull()
        ->and($next?->id)->toBe($pages[1]->id)
        ->and($lastNext)->toBeNull();
});

it('treats the last visible page as final when a later page is conditionally hidden (#2927)', function (): void {
    $form = formie()
        ->form([
            'title' => 'Hidden Final Page Semantics',
            'settings' => [
                'submitMethod' => 'ajax',
            ],
        ])
        ->multiPage(3)
        ->onPage(1)->singleLineTextField('pageOneRequired', ['required' => true])
        ->onPage(2)->singleLineTextField('pageTwoValue', ['required' => true])
        ->onPage(3)->singleLineTextField('pageThreeHidden')
        ->create();

    $pages = $form->getPages();
    $pageSettings = $pages[2]->getPageSettings();
    $pageSettings->enablePageConditions = true;
    // Never satisfied — page 3 stays hidden for any real submission value.
    $pageSettings->pageConditions = [
        'showRule' => 'show',
        'conditionRule' => 'all',
        'conditions' => [[
            'field' => 'pageTwoValue',
            'condition' => ConditionOperator::EQ,
            'value' => '__never_show_final_page__',
        ]],
    ];
    $pages[2]->setPageSettings($pageSettings->toArray());

    $layout = $form->getFormLayout();
    $layout->setPages($pages);
    $form->setFormLayout($layout);
    Craft::$app->getElements()->saveElement($form);

    $pages = $form->getPages();
    $submission = new Submission();
    $submission->setForm($form);
    $submission->setFieldValueFromRequest('pageTwoValue', 'visible-final');

    expect($pages[2]->isConditionallyHidden($submission))->toBeTrue()
        ->and($form->getNextPage($pages[1], $submission))->toBeNull()
        // Without a submission, the hidden page still counts as “next”.
        ->and($form->isLastPage($pages[1]))->toBeFalse()
        // With the submission, page 2 is the last reachable page.
        ->and($form->isLastPage($pages[1], $submission))->toBeTrue();

    // Reproduce the tabs jump: submit from the last visible page with page 1 empty.
    $response = (new SubmissionWorkflow())->processSubmissionRequest(new SubmissionRequest([
        'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
        'form' => $form,
        'submission' => $submission,
        'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
        'pageId' => (int)$pages[1]->id,
    ]));

    expect($response->success)->toBeFalse()
        ->and($response->submission->hasErrors('pageOneRequired'))->toBeTrue()
        // Final-page completion already flipped isIncomplete in normalize; the bug was
        // skipping earlier-page validation while still treating the submit as complete.
        ->and($response->submission->validateCurrentPageOnly)->toBeFalse();
});

it('keeps page handles non-empty across multipage setups', function (): void {
    $form = formie()
        ->form(['title' => 'Page Handles'])
        ->multiPage(2)
        ->onPage(1)->singleLineTextField('alpha')
        ->onPage(2)->singleLineTextField('beta')
        ->create();

    $handles = array_map(static fn($page) => $page->getHandle(), $form->getPages());

    expect($handles[0])->not->toBeEmpty()
        ->and($handles[1])->not->toBeEmpty();
});
