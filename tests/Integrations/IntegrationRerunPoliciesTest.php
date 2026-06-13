<?php

declare(strict_types=1);

use verbb\formie\elements\Form;
use verbb\formie\helpers\IntegrationRerunPolicies;
use verbb\formie\helpers\IntegrationTriggerEvents;
use verbb\formie\integrations\elements\Entry;

it('defaults integrations to submit-only re-run behaviour', function (): void {
    $form = new Form();
    $integration = new Entry(['handle' => 'entry']);

    expect(IntegrationRerunPolicies::getPolicy($form, $integration))->toBe(IntegrationRerunPolicies::POLICY_SUBMIT_ONLY)
        ->and(IntegrationRerunPolicies::getAllowedEvents($form, $integration))->toBe([IntegrationTriggerEvents::SUBMIT]);
});

it('resolves on-edit policy to submit and edit events', function (): void {
    $form = new Form();
    $form->settings->integrationPolicies = [
        'rerun' => [
            'entry' => [
                'policy' => IntegrationRerunPolicies::POLICY_ON_EDIT,
            ],
        ],
    ];

    $integration = new Entry(['handle' => 'entry']);

    expect(IntegrationRerunPolicies::getAllowedEvents($form, $integration))->toBe([
        IntegrationTriggerEvents::SUBMIT,
        IntegrationTriggerEvents::FRONTEND_EDIT,
        IntegrationTriggerEvents::CP_SAVE,
    ]);
});

it('falls back to legacy Entry update-on-edit settings when no form policy exists', function (): void {
    $form = new Form();
    $integration = new Entry([
        'handle' => 'entry',
        'updateElement' => true,
        'updateOnSubmissionEdit' => true,
    ]);

    expect(IntegrationRerunPolicies::getPolicy($form, $integration))->toBe(IntegrationRerunPolicies::POLICY_ON_EDIT)
        ->and(IntegrationRerunPolicies::isEventAllowed(
            $form,
            $integration,
            IntegrationTriggerEvents::CP_SAVE,
        ))->toBeTrue();
});

it('allows operator unmark actions to run submit-only integrations', function (): void {
    $form = new Form();
    $integration = new Entry(['handle' => 'mailchimp']);

    expect(IntegrationRerunPolicies::isEventAllowed(
        $form,
        $integration,
        IntegrationTriggerEvents::UNMARK_SPAM,
        true,
    ))->toBeTrue()
        ->and(IntegrationRerunPolicies::isEventAllowed(
            $form,
            $integration,
            IntegrationTriggerEvents::UNMARK_SPAM,
        ))->toBeFalse();
});

it('respects custom re-run event selections', function (): void {
    $form = new Form();
    $form->settings->integrationPolicies = [
        'rerun' => [
            'hubspot' => [
                'policy' => IntegrationRerunPolicies::POLICY_CUSTOM,
                'events' => [IntegrationTriggerEvents::SUBMIT, IntegrationTriggerEvents::UNMARK_SPAM],
            ],
        ],
    ];

    $integration = new Entry(['handle' => 'hubspot']);

    expect(IntegrationRerunPolicies::isEventAllowed(
        $form,
        $integration,
        IntegrationTriggerEvents::UNMARK_SPAM,
    ))->toBeTrue()
        ->and(IntegrationRerunPolicies::isEventAllowed(
            $form,
            $integration,
            IntegrationTriggerEvents::CP_SAVE,
        ))->toBeFalse();
});
