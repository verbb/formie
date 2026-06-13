<?php

declare(strict_types=1);

use verbb\formie\enums\workflow\Task;
use verbb\formie\helpers\IntegrationRerunPolicies;
use verbb\formie\helpers\IntegrationTriggerEvents;
use verbb\formie\integrations\elements\Entry;
use verbb\formie\elements\Form;
use verbb\formie\services\SubmissionWorkflow;

it('includes standard integration dispatch in the edit-existing workflow', function (): void {
    $workflow = new SubmissionWorkflow();
    $method = new ReflectionMethod($workflow, '_getModeTasks');
    $method->setAccessible(true);

    $tasks = $method->invoke($workflow)[SubmissionWorkflow::PROCESS_MODE_EDIT_EXISTING];

    expect($tasks)->toContain(Task::DISPATCH_GUARD_DISPATCH_ELIGIBILITY->value)
        ->and($tasks)->toContain(Task::DISPATCH_TRIGGER_INTEGRATIONS->value)
        ->and($tasks)->not->toContain(Task::DISPATCH_SEND_NOTIFICATIONS->value)
        ->and($tasks)->not->toContain(Task::DISPATCH_SEND_SPAM_NOTIFICATIONS->value)
        ->and($tasks)->not->toContain(Task::SCREEN_RUN_CAPTCHA_CHECKS->value);
});

it('only opts Entry integrations into submission-edit dispatch when configured', function (): void {
    $form = new Form();
    $form->settings->integrationPolicies = [
        'rerun' => [
            'entry' => [
                'policy' => IntegrationRerunPolicies::POLICY_ON_EDIT,
            ],
        ],
    ];

    $integration = new Entry([
        'handle' => 'entry',
        'updateElement' => true,
    ]);

    expect(IntegrationRerunPolicies::isEventAllowed(
        $form,
        $integration,
        IntegrationTriggerEvents::CP_SAVE,
    ))->toBeTrue()
        ->and(IntegrationRerunPolicies::isEventAllowed(
            $form,
            $integration,
            IntegrationTriggerEvents::FRONTEND_EDIT,
        ))->toBeTrue()
        ->and(IntegrationRerunPolicies::isEventAllowed(
            $form,
            $integration,
            IntegrationTriggerEvents::SUBMIT,
        ))->toBeTrue();
});
