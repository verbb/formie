<?php

declare(strict_types=1);

use verbb\formie\enums\workflow\Task;
use verbb\formie\integrations\elements\Entry;
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
    $integration = new Entry([
        'updateElement' => false,
        'updateOnSubmissionEdit' => true,
    ]);

    expect($integration->shouldTriggerOnSubmissionEdit())->toBeFalse();

    $integration->updateElement = true;

    expect($integration->shouldTriggerOnSubmissionEdit())->toBeTrue();

    $integration->updateOnSubmissionEdit = false;

    expect($integration->shouldTriggerOnSubmissionEdit())->toBeFalse();
});
