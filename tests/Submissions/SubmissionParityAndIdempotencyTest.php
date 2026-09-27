<?php

declare(strict_types=1);

use craft\db\Query;
use verbb\formie\Formie;
use verbb\formie\helpers\Table;
use verbb\formie\models\Notification;
use verbb\formie\models\SubmissionCommand;
use verbb\formie\services\SubmissionWorkflow;

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
        expect($submission->getFieldValueAsData($handle))->toBe($field?->getValueAsData($value, $submission));
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

it('deduplicates post-submit workflow markers by business run across request keys', function (): void {
    $form = formie()
        ->form(['title' => 'Idempotency Workflow'])
        ->singleLineTextField('fullName')
        ->create();

    $submission = formie()->submission($form)->with(['fullName' => 'Idempotent'])->save();

    $workflow = new SubmissionWorkflow();
    $idempotencyKey = 'idem-' . uniqid();

    runSubmissionCommand(submissionCommand([
        'operation' => \verbb\formie\enums\SubmissionOperation::PAYMENT_REPLAY,
        'form' => $form,
        'submission' => $submission,
        'navigation' => \verbb\formie\enums\NavigationIntent::ADVANCE,
        'operationId' => $idempotencyKey,
    ]));
    $dispatchUid = (new Query())->select('uid')->from(\verbb\formie\services\SubmissionDispatches::TABLE)
        ->where(['submissionId' => $submission->id, 'kind' => 'completion'])->scalar();
    expect($dispatchUid)->toBeString()->not->toBe($idempotencyKey);
    $countAfterFirst = (new Query())
        ->from(Table::FORMIE_SUBMISSION_WORKFLOW)
        ->where([
            'submissionId' => $submission->id,
            'idempotencyKey' => hash('sha256', $dispatchUid),
        ])
        ->count();

    runSubmissionCommand(submissionCommand([
        'operation' => \verbb\formie\enums\SubmissionOperation::PAYMENT_REPLAY,
        'form' => $form,
        'submission' => $submission,
        'navigation' => \verbb\formie\enums\NavigationIntent::ADVANCE,
        'operationId' => $idempotencyKey,
    ]));
    $countAfterSecond = (new Query())
        ->from(Table::FORMIE_SUBMISSION_WORKFLOW)
        ->where([
            'submissionId' => $submission->id,
            'idempotencyKey' => hash('sha256', $dispatchUid),
        ])
        ->count();

    runSubmissionCommand(submissionCommand([
        'operation' => \verbb\formie\enums\SubmissionOperation::PAYMENT_REPLAY,
        'form' => $form,
        'submission' => $submission,
        'navigation' => \verbb\formie\enums\NavigationIntent::ADVANCE,
        'operationId' => $idempotencyKey . '-new',
    ]));
    $countAfterDifferentKey = (new Query())
        ->from(Table::FORMIE_SUBMISSION_WORKFLOW)
        ->where(['submissionId' => $submission->id])
        ->count();

    expect((int)$countAfterFirst)->toBeGreaterThan(0)
        ->and((int)$countAfterSecond)->toBe((int)$countAfterFirst)
        ->and((int)$countAfterDifferentKey)->toBe((int)$countAfterSecond)
        ->and((int)(new Query())->from(\verbb\formie\services\SubmissionDispatches::TABLE)
            ->where(['submissionId' => $submission->id, 'kind' => 'completion'])->count())->toBe(1);
});

it('rejects the second provisional progress writer at the same version', function (): void {
    $form = formie()->form()->singleLineTextField('fullName')->create();
    $service = Formie::$plugin->getSubmissionProgress();
    $a = $service->upsertPageState($form);
    $b = clone $a;
    $a->content = ['fullName' => 'first'];
    $service->saveProgress($a);
    $b->content = ['fullName' => 'stale'];
    expect(fn() => $service->saveProgress($b))->toThrow(\yii\web\ConflictHttpException::class);
    expect($service->loadProgress($a->id)->content)->toBe(['fullName' => 'first']);
});
