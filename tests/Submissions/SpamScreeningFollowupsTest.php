<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Email as EmailField;
use verbb\formie\Formie;
use verbb\formie\helpers\SpamHelper;
use verbb\formie\models\FieldLayout;
use verbb\formie\models\Settings;
use verbb\formie\models\SubmissionRequest;
use verbb\formie\services\SubmissionWorkflow;

it('flags expired browser form sessions when submit expiration is enabled', function (): void {
    $form = createGuardTestForm();

    /** @var Settings $settings */
    $settings = Formie::$plugin->getSettings();
    $originalEnabled = $settings->enableFormSubmitExpiration;
    $originalSeconds = $settings->formSubmitExpiration;

    try {
        $settings->enableFormSubmitExpiration = true;
        $settings->formSubmitExpiration = 30;

        $reason = withSubmissionGuardsPostContext(function () use ($form): ?string {
            $submission = new Submission();
            $submission->setForm($form);

            return Formie::$plugin->getSubmissionGuards()->validateRequest(new SubmissionRequest([
                'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
                'form' => $form,
                'submission' => $submission,
                'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
                'requestToken' => 'expiration-token-' . uniqid(),
            ]));
        }, [
            'handle' => $form->handle,
            'formStartedAt' => (string)((int)(microtime(true) * 1000) - 60000),
        ]);

        expect($reason)->toContain('expired');
    } finally {
        $settings->enableFormSubmitExpiration = $originalEnabled;
        $settings->formSubmitExpiration = $originalSeconds;
    }
});

it('flags globally blocked email domains during spam screening', function (): void {
    $form = createGuardTestForm();
    $form->setFormLayout(new FieldLayout([
        'pages' => [[
            'label' => 'Page 1',
            'settings' => [],
            'rows' => [[
                'fields' => [[
                    'type' => EmailField::class,
                    'handle' => 'email',
                    'label' => 'Email',
                ]],
            ]],
        ]],
    ]));

    $submission = new Submission();
    $submission->setForm($form);
    $submission->setFieldValue('email', 'bot@mailinator.com');

    /** @var Settings $settings */
    $settings = Formie::$plugin->getSettings();
    $originalEnabled = $settings->enableBlockedEmailDomains;
    $originalDomains = $settings->blockedEmailDomains;

    try {
        $settings->enableBlockedEmailDomains = true;
        $settings->blockedEmailDomains = "mailinator.com\n";

        $match = SpamHelper::checkGlobalEmailRules($submission);

        expect($match)->toBeArray()
            ->and($match['type'])->toBe('blockedEmailDomain')
            ->and($match['value'])->toBe('mailinator.com');
    } finally {
        $settings->enableBlockedEmailDomains = $originalEnabled;
        $settings->blockedEmailDomains = $originalDomains;
    }
});

it('persists extended spam screening settings in the spam protection store', function (): void {
    Formie::$plugin->getSpamProtection()->saveValues(array_merge(
        Formie::$plugin->getSpamProtection()->getSettingsValues(),
        [
            'enableBlockedEmailDomains' => true,
            'blockedEmailDomains' => "example-blocked.test\n",
            'enableFormSubmitExpiration' => true,
            'formSubmitExpiration' => 120,
        ],
    ));

    $settings = new Settings();
    Formie::$plugin->getSpamProtection()->hydrateSettings($settings);

    expect($settings->enableBlockedEmailDomains)->toBeTrue()
        ->and($settings->blockedEmailDomains)->toContain('example-blocked.test')
        ->and($settings->enableFormSubmitExpiration)->toBeTrue()
        ->and($settings->formSubmitExpiration)->toBe(120);
});
