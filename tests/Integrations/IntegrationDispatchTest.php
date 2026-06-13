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
