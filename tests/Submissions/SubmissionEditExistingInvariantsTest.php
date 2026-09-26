<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use verbb\formie\conditions\ConditionOperator;
use verbb\formie\elements\Submission;
use verbb\formie\Formie;
use verbb\formie\models\ManagedSubmissionRequest;
use verbb\formie\models\Notification;
use verbb\formie\models\SubmissionCommand;
use verbb\formie\models\SubmissionStatus;
use verbb\formie\services\Notifications;
use verbb\formie\services\SubmissionWorkflow;

use Craft;
use craft\elements\User;
use RuntimeException;
use yii\base\Event;

function editInvariantsStatus(string $handle, string $name, string $color = 'green'): SubmissionStatus
{
    $status = new SubmissionStatus([
        'name' => $name,
        'handle' => $handle . uniqid(),
        'color' => $color,
    ]);

    expect(Formie::$plugin->getSubmissionStatuses()->saveStatus($status))->toBeTrue();

    return $status;
}

function editInvariantsWithCpRequest(callable $callback, array $bodyParams = []): mixed
{
    $admin = User::find()->status(null)->admin(true)->one();
    expect($admin)->not->toBeNull();

    return WebRequestTestHelper::withWebRequestContext(function () use ($callback, $admin): mixed {
        Craft::$app->getRequest()->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity($admin);

        return $callback();
    }, [
        'method' => 'POST',
        'hostInfo' => 'https://craft.example.test',
        'httpHost' => 'craft.example.test',
        'bodyParams' => $bodyParams,
    ]);
}

function editInvariantsSaveIncomplete(mixed $form, array $values): Submission
{
    $submission = formie()->submission($form)->with($values)->save();
    $submission->isIncomplete = true;
    expect(Craft::$app->elements->saveElement($submission))->toBeTrue();

    $reloaded = Submission::find()->id($submission->id)->status(null)->isIncomplete(null)->one();

    if (!$reloaded) {
        throw new RuntimeException('Unable to reload incomplete submission for edit invariant tests.');
    }

    return $reloaded;
}

function editInvariantsTwoPageForm(string $title): mixed
{
    return formie()
        ->form([
            'title' => $title . ' ' . uniqid(),
            'settings' => ['submitMethod' => 'ajax'],
        ])
        ->multiPage(2)
        ->onPage(1)->singleLineTextField('pageOne', ['required' => true])
        ->onPage(2)->singleLineTextField('pageTwo', ['required' => true])
        ->create();
}

function editInvariantsRunEditExisting(
    mixed $form,
    Submission $submission,
    ?int $pageId = null,
    bool $cpRequest = false,
): mixed {
    return WebRequestTestHelper::withWebRequestContext(function () use ($form, $submission, $pageId, $cpRequest): mixed {
        Craft::$app->getRequest()->setIsCpRequest($cpRequest);

        $request = submissionCommand([
            'operation' => !$cpRequest && $submission->isIncomplete ? \verbb\formie\enums\SubmissionOperation::SUBMIT : \verbb\formie\enums\SubmissionOperation::REVISE,
            'form' => $form,
            'submission' => $submission,
            'navigation' => \verbb\formie\enums\NavigationIntent::ADVANCE,
            'pageId' => $pageId,
        ]);

        return runSubmissionCommand($request);
    }, [
        'method' => 'POST',
        'hostInfo' => 'https://craft.example.test',
        'httpHost' => 'craft.example.test',
    ]);
}

it('keeps revision operation policy explicit regardless of ambient request classification', function () {
    $form = formie()->form()->create();
    $submission = formie()->submission($form)->save();
    $command = submissionCommand(['operation' => \verbb\formie\enums\SubmissionOperation::REVISE, 'form' => $form, 'submission' => $submission]);
    expect($command->usesVisitorProgression())->toBeFalse();
});

it('keeps completed multi-page cp saves complete and preserves the selected status', function (): void {
    $evaluated = editInvariantsStatus('evaluatedEditInvariant', 'Evaluated', 'orange');
    $form = editInvariantsTwoPageForm('CP Complete Multipage Edit');

    $submission = formie()
        ->submission($form)
        ->with(['pageOne' => 'one', 'pageTwo' => 'two'])
        ->save();

    expect($submission->isIncomplete)->toBeFalse();

    editInvariantsWithCpRequest(function () use ($form, $submission): void {
        $result = runManagedSubmission(new ManagedSubmissionRequest([
            'handle' => $form->handle,
            'operation' => \verbb\formie\enums\SubmissionOperation::REVISE,
            'siteId' => (int)$submission->siteId,
            'submissionId' => (int)$submission->id,
            'submitAction' => 'submit',
            'fieldParamNamespace' => 'fields',
        ]), \verbb\formie\enums\SubmissionAuthorityType::CONTROL_PANEL);

        expect($result->response->success)->toBeTrue();
    }, [
        'statusId' => (int)$evaluated->id,
        'fields' => [
            'pageOne' => 'one-updated',
            'pageTwo' => 'two-updated',
        ],
    ]);

    $reloaded = Submission::find()->id($submission->id)->status(null)->isIncomplete(null)->one();

    expect($reloaded)->not->toBeNull()
        ->and($reloaded->isIncomplete)->toBeFalse()
        ->and($reloaded->statusId)->toBe($evaluated->id)
        ->and($reloaded->getFieldValue('pageOne'))->toBe('one-updated')
        ->and($reloaded->getFieldValue('pageTwo'))->toBe('two-updated');
});

it('does not mark edit-existing workflow saves as new submissions', function (): void {
    $form = formie()
        ->form(['title' => 'Edit Existing New Submission Flag'])
        ->singleLineTextField('fullName')
        ->create();

    $existing = formie()->submission($form)->with(['fullName' => 'Before'])->save();
    $existing->setFieldValueFromRequest('fullName', 'After');

    $response = editInvariantsRunEditExisting($form, $existing, cpRequest: false);

    expect($response->success)->toBeTrue()
        ->and($response->outcome->type)->toBe(\verbb\formie\enums\SubmissionOutcomeType::REVISED);
});

it('keeps completed front-end edits complete without visitor page-flow side effects', function (): void {
    $custom = editInvariantsStatus('feCompleteEditInvariant', 'Reviewed', 'blue');
    $form = editInvariantsTwoPageForm('FE Complete Multipage Edit');
    $pages = $form->getPages();

    $submission = formie()
        ->submission($form)
        ->with(['pageOne' => 'one', 'pageTwo' => 'two'])
        ->save();
    $submission->setStatus($custom);
    expect(Craft::$app->elements->saveElement($submission))->toBeTrue();

    $editing = Submission::find()->id($submission->id)->status(null)->isIncomplete(null)->one();
    $editing->setFieldValueFromRequest('pageOne', 'one-edited');

    $editResponse = editInvariantsRunEditExisting($form, $editing, pageId: (int)$pages[0]->id, cpRequest: false);

    $reloaded = Submission::find()->id($submission->id)->status(null)->isIncomplete(null)->one();

    expect($editResponse->success)->toBeTrue()
        ->and($editResponse->outcome->type)->toBe(\verbb\formie\enums\SubmissionOutcomeType::REVISED)
        ->and($reloaded->isIncomplete)->toBeFalse()
        ->and($reloaded->statusId)->toBe($custom->id)
        ->and($reloaded->getFieldValue('pageOne'))->toBe('one-edited');
});

it('still allows incomplete front-end edits to progress through multi-page flow', function (): void {
    $form = editInvariantsTwoPageForm('FE Incomplete Multipage Edit');
    $pages = $form->getPages();

    $incomplete = editInvariantsSaveIncomplete($form, ['pageOne' => 'draft-one']);

    $editing = Submission::find()->id($incomplete->id)->status(null)->isIncomplete(null)->one();
    $editing->setFieldValueFromRequest('pageOne', 'draft-one-updated');

    $response = editInvariantsRunEditExisting($form, $editing, pageId: (int)$pages[0]->id, cpRequest: false);

    expect($response->success)->toBeTrue()
        ->and($response->outcome->type)->toBe(\verbb\formie\enums\SubmissionOutcomeType::PAGE_CHANGED)
        ->and($response->submission->isIncomplete)->toBeTrue()
        ->and($response->nextPage?->id)->toBe($pages[1]->id);

    $reloaded = Submission::find()->id($incomplete->id)->status(null)->isIncomplete(null)->one();
    expect($reloaded->getFieldValue('pageOne'))->toBe('draft-one-updated');
});

it('sends status-change notifications when cp workflow saves change the submission status', function (): void {
    $evaluated = editInvariantsStatus('evaluatedCpNotify', 'Evaluated Notify', 'orange');
    $form = formie()
        ->form(['title' => 'CP Status Change Notification'])
        ->singleLineTextField('fullName')
        ->create();

    $notificationHandle = 'evaluatedAlert' . uniqid();

    $form->setNotifications([
        new Notification([
            'name' => 'Evaluated alert',
            'handle' => $notificationHandle,
            'enabled' => true,
            'subject' => 'Evaluated',
            'to' => 'email@example.test',
            'conditions' => [
                'conditionRule' => 'all',
                'conditions' => [[
                    'field' => '{submission:status}',
                    'condition' => ConditionOperator::EQ,
                    'value' => $evaluated->handle,
                ]],
            ],
        ]),
    ]);
    expect(Craft::$app->elements->saveElement($form))->toBeTrue();

    $existing = formie()->submission($form)->with(['fullName' => 'Notify Before'])->save();

    $settings = Formie::$plugin->getSettings();
    $previousUseQueue = $settings->useQueueForNotifications;
    $settings->useQueueForNotifications = false;

    $sent = [];
    $handler = function ($event) use (&$sent): void {
        $sent[] = $event->notification->handle ?? null;
        $event->isValid = false;
    };

    Event::on(Notifications::class, Notifications::EVENT_BEFORE_SEND_NOTIFICATION, $handler);

    try {
        editInvariantsWithCpRequest(function () use ($form, $existing): void {
            $result = runManagedSubmission(new ManagedSubmissionRequest([
                'handle' => $form->handle,
                'operation' => \verbb\formie\enums\SubmissionOperation::REVISE,
                'siteId' => (int)$existing->siteId,
                'submissionId' => (int)$existing->id,
                'submitAction' => 'submit',
                'fieldParamNamespace' => 'fields',
            ]), \verbb\formie\enums\SubmissionAuthorityType::CONTROL_PANEL);

            expect($result->response->success)->toBeTrue()
                ->and($result->response->outcome->type)->toBe(\verbb\formie\enums\SubmissionOutcomeType::REVISED);
        }, [
            'statusId' => (int)$evaluated->id,
            'fields' => [
                'fullName' => 'Notify After',
            ],
        ]);

        // Dispatch runs after the persistence transaction commits, so configured
        // synchronous notifications execute in this request.
        expect($sent)->toBe([$notificationHandle]);
    } finally {
        $settings->useQueueForNotifications = $previousUseQueue;
        Event::off(Notifications::class, Notifications::EVENT_BEFORE_SEND_NOTIFICATION, $handler);
    }
});

it('marks incomplete cp submissions complete only when markAsComplete is posted', function (): void {
    $form = formie()
        ->form(['title' => 'CP Mark As Complete'])
        ->singleLineTextField('fullName')
        ->create();

    $incomplete = editInvariantsSaveIncomplete($form, ['fullName' => 'Still going']);

    editInvariantsWithCpRequest(function () use ($form, $incomplete): void {
        $result = runManagedSubmission(new ManagedSubmissionRequest([
            'handle' => $form->handle,
            'operation' => \verbb\formie\enums\SubmissionOperation::REVISE,
            'siteId' => (int)$incomplete->siteId,
            'submissionId' => (int)$incomplete->id,
            'submitAction' => 'submit',
            'fieldParamNamespace' => 'fields',
        ]), \verbb\formie\enums\SubmissionAuthorityType::CONTROL_PANEL);

        expect($result->response->success)->toBeTrue();
    }, [
        'fields' => [
            'fullName' => 'Still going updated',
        ],
    ]);

    $stillIncomplete = Submission::find()->id($incomplete->id)->status(null)->isIncomplete(null)->one();
    expect($stillIncomplete->isIncomplete)->toBeTrue()
        ->and($stillIncomplete->getFieldValue('fullName'))->toBe('Still going updated');

    editInvariantsWithCpRequest(function () use ($form, $incomplete): void {
        $result = runManagedSubmission(new ManagedSubmissionRequest([
            'handle' => $form->handle,
            'operation' => \verbb\formie\enums\SubmissionOperation::REVISE,
            'siteId' => (int)$incomplete->siteId,
            'submissionId' => (int)$incomplete->id,
            'submitAction' => 'submit',
            'fieldParamNamespace' => 'fields',
        ]), \verbb\formie\enums\SubmissionAuthorityType::CONTROL_PANEL);

        expect($result->response->success)->toBeTrue();
    }, [
        'markAsComplete' => '1',
        'fields' => [
            'fullName' => 'Now complete',
        ],
    ]);

    $completed = Submission::find()->id($incomplete->id)->status(null)->isIncomplete(null)->one();
    expect($completed->isIncomplete)->toBeFalse()
        ->and($completed->getFieldValue('fullName'))->toBe('Now complete');
});
