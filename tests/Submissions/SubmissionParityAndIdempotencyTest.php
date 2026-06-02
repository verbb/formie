<?php

declare(strict_types=1);

use craft\db\Query;
use verbb\formie\helpers\Table;
use verbb\formie\models\Notification;
use verbb\formie\models\SubmissionRequest;
use verbb\formie\services\SubmissionWorkflow;
use verbb\formie\services\SubmissionDrafts;
use verbb\formie\state\DraftSubmissionState;

it('keeps projection parity between field-level and submission wrapper value APIs', function (): void {
    $form = formie()
        ->form(['title' => 'Projection Parity'])
        ->singleLineTextField('fullName')
        ->emailField('email')
        ->create();

    $submission = formie()->submission($form)->with([
        'fullName' => 'Parity User',
        'email' => 'parity@example.test',
    ])->save();

    $notification = new Notification(['name' => 'n', 'handle' => 'n' . uniqid()]);

    foreach (['fullName', 'email'] as $handle) {
        $field = $form->getFieldByHandle($handle);
        $value = $submission->getFieldValue($handle);

        expect($submission->getFieldValueAsString($handle))->toBe($field?->getValueAsString($value, $submission));
        expect($submission->getFieldValueAsArray($handle))->toBe($field?->getValueAsArray($value, $submission));
        expect($submission->getFieldValueForExport($handle))->toBe($field?->getValueForExport($value, $submission));
        expect($submission->getFieldValueForSummary($handle))->toBe($field?->getValueForSummary($value, $submission));
        expect($submission->getFieldValueForReference($handle, $notification))->toBe($field?->getValueForReference($value, $submission));
        expect($submission->getFieldValueForReferenceBlock($handle, $notification))->toBe($field?->getValueForReferenceBlock($value, $notification, $submission));
        expect($submission->getFieldValueForVariable($handle, $notification))->toBe($submission->getFieldValueForReference($handle, $notification));
        expect($submission->getFieldValueForEmail($handle, $notification))->toBe($submission->getFieldValueForReferenceBlock($handle, $notification));
    }
});

it('bridges deprecated email value listeners through the reference block pipeline', function (): void {
    $form = formie()
        ->form(['title' => 'Deprecated Email Event Bridge'])
        ->singleLineTextField('fullName')
        ->create();

    $submission = formie()->submission($form)->with([
        'fullName' => 'Bridge User',
    ])->save();

    $notification = new Notification(['name' => 'n', 'handle' => 'n' . uniqid()]);
    $field = $form->getFieldByHandle('fullName');
    $value = $submission->getFieldValue('fullName');

    expect($field)->not->toBeNull();

    $legacySaw = null;

    $field?->on($field::EVENT_MODIFY_VALUE_FOR_REFERENCE_BLOCK, function ($event): void {
        $event->value = 'canonical-value';
    });

    $field?->on($field::EVENT_MODIFY_VALUE_FOR_EMAIL, function ($event) use (&$legacySaw): void {
        $legacySaw = $event->value;
        $event->value = 'legacy-value';
    });

    expect($field?->getValueForReferenceBlock($value, $notification, $submission))
        ->toBe('legacy-value')
        ->and($legacySaw)->toBe('canonical-value');
});

it('deduplicates post-submit workflow markers by idempotency key', function (): void {
    $form = formie()
        ->form(['title' => 'Idempotency Workflow'])
        ->singleLineTextField('fullName')
        ->create();

    $submission = formie()->submission($form)->with(['fullName' => 'Idempotent'])->save();

    $workflow = new SubmissionWorkflow();
    $idempotencyKey = 'idem-' . uniqid();

    $workflow->processSubmissionRequest(new SubmissionRequest([
        'processMode' => SubmissionWorkflow::PROCESS_MODE_PAYMENT_REPLAY,
        'form' => $form,
        'submission' => $submission,
        'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
        'requestToken' => $idempotencyKey,
    ]));
    $countAfterFirst = (new Query())
        ->from(Table::FORMIE_SUBMISSION_WORKFLOW)
        ->where([
            'submissionId' => $submission->id,
            'idempotencyKey' => $idempotencyKey,
        ])
        ->count();

    $workflow->processSubmissionRequest(new SubmissionRequest([
        'processMode' => SubmissionWorkflow::PROCESS_MODE_PAYMENT_REPLAY,
        'form' => $form,
        'submission' => $submission,
        'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
        'requestToken' => $idempotencyKey,
    ]));
    $countAfterSecond = (new Query())
        ->from(Table::FORMIE_SUBMISSION_WORKFLOW)
        ->where([
            'submissionId' => $submission->id,
            'idempotencyKey' => $idempotencyKey,
        ])
        ->count();

    $workflow->processSubmissionRequest(new SubmissionRequest([
        'processMode' => SubmissionWorkflow::PROCESS_MODE_PAYMENT_REPLAY,
        'form' => $form,
        'submission' => $submission,
        'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
        'requestToken' => $idempotencyKey . '-new',
    ]));
    $countAfterDifferentKey = (new Query())
        ->from(Table::FORMIE_SUBMISSION_WORKFLOW)
        ->where(['submissionId' => $submission->id])
        ->count();

    expect((int)$countAfterFirst)->toBeGreaterThan(0)
        ->and((int)$countAfterSecond)->toBe((int)$countAfterFirst)
        ->and((int)$countAfterDifferentKey)->toBeGreaterThan((int)$countAfterSecond);
});

it('keeps deterministic last-write behavior for concurrent draft-state saves', function (): void {
    $form = formie()
        ->form(['title' => 'Draft Concurrency'])
        ->singleLineTextField('fullName')
        ->create();

    $managerA = new SubmissionDrafts();
    $managerB = new SubmissionDrafts();

    $context = ['scope' => 'concurrency', 'instance' => 'same'];
    $key = $managerA->resolveFormInstanceKey($form, null, $context);

    $stateA = new DraftSubmissionState([
        'formInstanceKey' => $key,
        'content' => ['fieldUid' => 'valueA'],
        'snapshot' => ['writer' => 'A'],
        'version' => 1,
    ]);

    $stateB = new DraftSubmissionState([
        'formInstanceKey' => $key,
        'content' => ['fieldUid' => 'valueB'],
        'snapshot' => ['writer' => 'B'],
        'version' => 2,
    ]);

    $managerA->saveDraftState($stateA);
    $managerB->saveDraftState($stateB);

    $loaded = $managerA->loadDraftState($key);

    expect($loaded)->not->toBeNull()
        ->and($loaded?->content['fieldUid'] ?? null)->toBe('valueB')
        ->and($loaded?->snapshot['writer'] ?? null)->toBe('B');
});
