<?php

declare(strict_types=1);

use Craft;
use verbb\formie\conditions\ConditionOperator;
use verbb\formie\elements\Submission;
use verbb\formie\events\SubmissionCompleteEvent;
use verbb\formie\events\SubmissionPageAdvanceEvent;
use verbb\formie\Formie;
use verbb\formie\models\SubmissionRequest;
use verbb\formie\models\SubmissionStatus;
use verbb\formie\services\SubmissionWorkflow;
use yii\base\Event;

if (!function_exists('formieLifecycleEventProbe')) {
    function formieLifecycleEventProbe(): array
    {
        $complete = [];
        $pageAdvance = [];

        $completeHandler = static function(SubmissionCompleteEvent $event) use (&$complete): void {
            $complete[] = $event;
        };
        $pageAdvanceHandler = static function(SubmissionPageAdvanceEvent $event) use (&$pageAdvance): void {
            $pageAdvance[] = $event;
        };

        Event::on(Submission::class, Submission::EVENT_AFTER_COMPLETE, $completeHandler);
        Event::on(SubmissionWorkflow::class, SubmissionWorkflow::EVENT_AFTER_PAGE_ADVANCE, $pageAdvanceHandler);

        return [
            'complete' => &$complete,
            'pageAdvance' => &$pageAdvance,
            'off' => static function() use ($completeHandler, $pageAdvanceHandler): void {
                Event::off(Submission::class, Submission::EVENT_AFTER_COMPLETE, $completeHandler);
                Event::off(SubmissionWorkflow::class, SubmissionWorkflow::EVENT_AFTER_PAGE_ADVANCE, $pageAdvanceHandler);
            },
        ];
    }
}

it('fires afterComplete on a single-page submit and not afterPageAdvance', function (): void {
    $form = formie()
        ->form(['title' => 'Lifecycle Single Page'])
        ->settings(['disableCaptchas' => true])
        ->singleLineTextField('fullName', ['required' => true])
        ->create();

    $probe = formieLifecycleEventProbe();

    try {
        $submission = new Submission();
        $submission->setForm($form);
        $submission->setFieldValueFromRequest('fullName', 'Ada Lovelace');

        $response = (new SubmissionWorkflow())->processSubmissionRequest(new SubmissionRequest([
            'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
            'form' => $form,
            'submission' => $submission,
            'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
        ]));

        expect($response->success)->toBeTrue()
            ->and($probe['pageAdvance'])->toBeEmpty()
            ->and($probe['complete'])->toHaveCount(1)
            ->and($probe['complete'][0]->submission->id)->toBe($response->submission->id)
            ->and($response->submission->isIncomplete)->toBeFalse();
    } finally {
        $probe['off']();
    }
});

it('fires afterPageAdvance on Next and afterComplete on the last page', function (): void {
    $form = formie()
        ->form(['title' => 'Lifecycle Multipage'])
        ->settings(['disableCaptchas' => true])
        ->multiPage(2)
        ->onPage(1)->singleLineTextField('pageOne', ['required' => true])
        ->onPage(2)->singleLineTextField('pageTwo', ['required' => true])
        ->create();

    $pages = $form->getPages();
    $probe = formieLifecycleEventProbe();

    try {
        $submission = new Submission();
        $submission->setForm($form);
        $submission->setFieldValueFromRequest('pageOne', 'one');

        $pageOneResponse = (new SubmissionWorkflow())->processSubmissionRequest(new SubmissionRequest([
            'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
            'form' => $form,
            'submission' => $submission,
            'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
            'pageId' => (int)$pages[0]->id,
        ]));

        expect($pageOneResponse->success)->toBeTrue()
            ->and($pageOneResponse->submission->isIncomplete)->toBeTrue()
            ->and($pageOneResponse->nextPage?->id)->toBe($pages[1]->id)
            ->and($probe['pageAdvance'])->toHaveCount(1)
            ->and($probe['complete'])->toBeEmpty()
            ->and($probe['pageAdvance'][0]->fromPage?->id)->toBe($pages[0]->id)
            ->and($probe['pageAdvance'][0]->toPage?->id)->toBe($pages[1]->id);

        $pageOneResponse->submission->setFieldValueFromRequest('pageTwo', 'two');

        $pageTwoResponse = (new SubmissionWorkflow())->processSubmissionRequest(new SubmissionRequest([
            'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
            'form' => $form,
            'submission' => $pageOneResponse->submission,
            'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
            'pageId' => (int)$pages[1]->id,
        ]));

        expect($pageTwoResponse->success)->toBeTrue()
            ->and($pageTwoResponse->submission->isIncomplete)->toBeFalse()
            ->and($probe['pageAdvance'])->toHaveCount(1)
            ->and($probe['complete'])->toHaveCount(1);
    } finally {
        $probe['off']();
    }
});

it('fires afterComplete on the last visible page when a later page is hidden', function (): void {
    $form = formie()
        ->form(['title' => 'Lifecycle Hidden Last Page'])
        ->settings(['disableCaptchas' => true])
        ->multiPage(3)
        ->onPage(1)->singleLineTextField('pageOne', ['required' => true])
        ->onPage(2)->singleLineTextField('pageTwo', ['required' => true])
        ->onPage(3)->singleLineTextField('pageThreeHidden')
        ->create();

    $pages = $form->getPages();
    $pageSettings = $pages[2]->getPageSettings();
    $pageSettings->enablePageConditions = true;
    $pageSettings->pageConditions = [
        'showRule' => 'show',
        'conditionRule' => 'all',
        'conditions' => [[
            'field' => 'pageTwo',
            'condition' => ConditionOperator::EQ,
            'value' => '__never_show__',
        ]],
    ];
    $pages[2]->setPageSettings($pageSettings->toArray());
    $layout = $form->getFormLayout();
    $layout->setPages($pages);
    $form->setFormLayout($layout);
    Craft::$app->getElements()->saveElement($form);

    $pages = $form->getPages();
    $probe = formieLifecycleEventProbe();

    try {
        $submission = new Submission();
        $submission->setForm($form);
        $submission->setFieldValueFromRequest('pageOne', 'one');
        $submission->setFieldValueFromRequest('pageTwo', 'visible-final');

        $response = (new SubmissionWorkflow())->processSubmissionRequest(new SubmissionRequest([
            'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
            'form' => $form,
            'submission' => $submission,
            'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
            'pageId' => (int)$pages[1]->id,
        ]));

        expect($response->success)->toBeTrue()
            ->and($response->submission->isIncomplete)->toBeFalse()
            ->and($probe['pageAdvance'])->toBeEmpty()
            ->and($probe['complete'])->toHaveCount(1);
    } finally {
        $probe['off']();
    }
});

it('does not fire lifecycle events for save-draft or failed validation', function (): void {
    $form = formie()
        ->form(['title' => 'Lifecycle Draft And Invalid'])
        ->settings(['disableCaptchas' => true])
        ->singleLineTextField('fullName', ['required' => true])
        ->create();

    $probe = formieLifecycleEventProbe();

    try {
        $draft = new Submission();
        $draft->setForm($form);
        $draft->setFieldValueFromRequest('fullName', 'Draft');

        $draftResponse = (new SubmissionWorkflow())->processSubmissionRequest(new SubmissionRequest([
            'processMode' => SubmissionWorkflow::PROCESS_MODE_SAVE_DRAFT,
            'form' => $form,
            'submission' => $draft,
            'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SAVE,
        ]));

        $invalid = new Submission();
        $invalid->setForm($form);

        $invalidResponse = (new SubmissionWorkflow())->processSubmissionRequest(new SubmissionRequest([
            'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
            'form' => $form,
            'submission' => $invalid,
            'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
        ]));

        expect($draftResponse->success)->toBeTrue()
            ->and($invalidResponse->success)->toBeFalse()
            ->and($probe['pageAdvance'])->toBeEmpty()
            ->and($probe['complete'])->toBeEmpty();
    } finally {
        $probe['off']();
    }
});

it('persists status changes made in afterComplete before dispatch', function (): void {
    $status = new SubmissionStatus([
        'name' => 'Ready For Evaluation',
        'handle' => 'readyForEval' . uniqid(),
        'color' => 'green',
    ]);
    expect(Formie::$plugin->getSubmissionStatuses()->saveStatus($status))->toBeTrue();

    $form = formie()
        ->form(['title' => 'Lifecycle Status Mutate'])
        ->settings(['disableCaptchas' => true])
        ->singleLineTextField('fullName')
        ->create();

    $handler = static function(SubmissionCompleteEvent $event) use ($status): void {
        $event->submission->setStatus($status);
    };

    Event::on(Submission::class, Submission::EVENT_AFTER_COMPLETE, $handler);

    try {
        $submission = new Submission();
        $submission->setForm($form);
        $submission->setFieldValueFromRequest('fullName', 'Status Mutate');

        $response = (new SubmissionWorkflow())->processSubmissionRequest(new SubmissionRequest([
            'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
            'form' => $form,
            'submission' => $submission,
            'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
        ]));

        $reloaded = Formie::$plugin->getSubmissions()->getSubmissionById((int)$response->submission->id);

        expect($response->success)->toBeTrue()
            ->and($reloaded?->statusId)->toBe($status->id);
    } finally {
        Event::off(Submission::class, Submission::EVENT_AFTER_COMPLETE, $handler);
    }
});

it('fires afterComplete when a control-panel save marks a submission complete', function (): void {
    $form = formie()
        ->form(['title' => 'Lifecycle CP Complete'])
        ->settings(['disableCaptchas' => true])
        ->singleLineTextField('fullName')
        ->create();

    $submission = new Submission();
    $submission->setForm($form);
    $submission->title = 'Incomplete lifecycle CP ' . uniqid();
    $submission->isIncomplete = true;
    $submission->setFieldValueFromRequest('fullName', 'Incomplete');
    expect(Craft::$app->getElements()->saveElement($submission))->toBeTrue();

    $probe = formieLifecycleEventProbe();

    try {
        $submission->isIncomplete = false;
        expect(Craft::$app->getElements()->saveElement($submission))->toBeTrue()
            ->and($probe['complete'])->toHaveCount(1)
            ->and($probe['pageAdvance'])->toBeEmpty();
    } finally {
        $probe['off']();
    }
});
