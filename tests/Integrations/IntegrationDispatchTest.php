<?php

declare(strict_types=1);

use verbb\formie\elements\Form;
use verbb\formie\models\IntegrationDispatchPlan;
use verbb\formie\models\Notification;
use verbb\formie\services\IntegrationDispatch;

it('resolves integration dispatch steps from explicit plan steps', function (): void {
    $plan = IntegrationDispatchPlan::fromFormSettings([
        'enabled' => true,
        'steps' => [
            ['handle' => 'b', 'mode' => 'queued'],
            ['handle' => 'a', 'mode' => 'immediate'],
        ],
    ]);

    expect($plan->getOrderedHandles(new Form()))->toBe(['b', 'a'])
        ->and($plan->getImmediateHandles(new Form()))->toBe(['a'])
        ->and($plan->getQueuedHandles(new Form()))->toBe(['b']);
});

it('uses form default notification timing when notification dispatch timing is default', function (): void {
    $form = new Form();
    $form->settings->integrationDispatch = [
        'enabled' => true,
        'notificationTiming' => IntegrationDispatchPlan::NOTIFICATION_TIMING_AFTER,
    ];

    $notification = new Notification([
        'dispatchTiming' => Notification::DISPATCH_TIMING_DEFAULT,
    ]);

    $service = new IntegrationDispatch();

    expect($service->shouldSendNotificationAtPhase($notification, $form, IntegrationDispatch::PHASE_BEFORE))->toBeFalse()
        ->and($service->shouldSendNotificationAtPhase($notification, $form, IntegrationDispatch::PHASE_AFTER))->toBeTrue();
});

it('allows per-notification dispatch timing overrides', function (): void {
    $form = new Form();
    $form->settings->integrationDispatch = [
        'enabled' => true,
        'notificationTiming' => IntegrationDispatchPlan::NOTIFICATION_TIMING_BEFORE,
    ];

    $notification = new Notification([
        'dispatchTiming' => Notification::DISPATCH_TIMING_AFTER,
    ]);

    $service = new IntegrationDispatch();

    expect($service->shouldSendNotificationAtPhase($notification, $form, IntegrationDispatch::PHASE_BEFORE))->toBeFalse()
        ->and($service->shouldSendNotificationAtPhase($notification, $form, IntegrationDispatch::PHASE_AFTER))->toBeTrue();
});

it('records integration dispatch context results', function (): void {
    $context = \verbb\formie\models\IntegrationDispatchContext::fromSubmission(null);

    $context->record('user', [
        'success' => true,
        'elementId' => 99,
        'url' => 'https://example.test/users/99',
    ]);

    expect($context->wasSuccessful('user'))->toBeTrue()
        ->and($context->getResult('user')['elementId'])->toBe(99);
});

it('detects when any notification requires the after-integrations phase', function (): void {
    $form = new class extends Form {
        public array $auditNotifications = [];

        public function getEnabledNotifications(): array
        {
            return $this->auditNotifications;
        }
    };
    $form->settings->integrationDispatch = [
        'enabled' => true,
        'notificationTiming' => IntegrationDispatchPlan::NOTIFICATION_TIMING_BEFORE,
    ];
    $form->auditNotifications = [
        new Notification([
            'name' => 'After Override',
            'dispatchTiming' => Notification::DISPATCH_TIMING_AFTER,
        ]),
    ];

    $service = new IntegrationDispatch();

    expect($service->needsAfterNotificationsPhase($form))->toBeTrue();
});


it('round trips integration context and reads previously double encoded context', function (): void {
    $form = formie()->form()->singleLineTextField('fullName')->create();
    $submission = formie()->submission($form)->with(['fullName' => 'Context'])->save();
    $context = new \verbb\formie\models\IntegrationDispatchContext();
    $context->record('example', ['success' => true, 'elementId' => 42]);
    $service = new IntegrationDispatch();
    $service->saveContext($submission, $context);
    $raw = (new \craft\db\Query())->select(['integrationDispatchContext'])
        ->from(\verbb\formie\helpers\Table::FORMIE_SUBMISSIONS)->where(['id' => $submission->id])->scalar();
    expect(\craft\helpers\Json::decodeIfJson($raw))->toBe($context->toStorageArray());

    // Simulate the historical encoding for compatibility with already saved rows.
    Craft::$app->getDb()->createCommand()->update(\verbb\formie\helpers\Table::FORMIE_SUBMISSIONS,
        ['integrationDispatchContext' => \craft\helpers\Json::encode($context->toStorageArray())],
        ['id' => $submission->id],
    )->execute();
    $restored = \verbb\formie\elements\Submission::find()->id($submission->id)->status(null)->isIncomplete(null)->one();
    expect($service->loadContext($restored)->getResult('example'))->toBe(['success' => true, 'elementId' => 42]);
});
