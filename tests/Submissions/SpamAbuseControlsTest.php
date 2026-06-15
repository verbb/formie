<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\fields\SingleLineText as SingleLineTextField;
use verbb\formie\Formie;
use verbb\formie\helpers\SpamHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\SuspiciousTextHelper;
use verbb\formie\models\FieldLayout;
use verbb\formie\models\Settings;
use verbb\formie\models\SubmissionRequest;
use verbb\formie\services\SubmissionGuards;
use verbb\formie\services\SubmissionWorkflow;

function createAbuseControlTestForm(): Form
{
    $form = new Form();
    $form->handle = 'abuse-control-test-' . uniqid();
    $form->title = 'Abuse Control Test';
    $form->uid = StringHelper::UUID();
    $form->setNotifications([]);

    return $form;
}

function withAbuseControlPostContext(callable $callback, array $bodyParams = []): mixed
{
    return WebRequestTestHelper::withWebRequestContext(function () use ($callback, $bodyParams): mixed {
        Craft::$app->getRequest()->setBodyParams(array_merge([
            'handle' => 'abuse-control-test-form',
            'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
            'formStartedAt' => (string)((int)(microtime(true) * 1000) - 10000),
            'formieHoneypot' => '',
        ], $bodyParams));

        return $callback();
    }, [
        'method' => 'POST',
        'bodyParams' => [],
    ]);
}

it('detects suspicious keyboard spam text', function (): void {
    $analysis = SuspiciousTextHelper::analyze('asdfghjkl qwertyuiop zxcvbnm');

    expect($analysis['is_suspicious'])->toBeTrue();
});

it('counts links in submission content', function (): void {
    $content = 'Visit https://example.com and www.test.com/path today.';

    expect(SpamHelper::countLinks($content))->toBe(2);
});

it('flags submissions that exceed the maximum link limit', function (): void {
    $form = createAbuseControlTestForm();
    $form->setFormLayout(new FieldLayout([
        'pages' => [[
            'label' => 'Page 1',
            'settings' => [],
            'rows' => [[
                'fields' => [[
                    'type' => SingleLineTextField::class,
                    'handle' => 'message',
                    'label' => 'Message',
                ]],
            ]],
        ]],
    ]));

    $submission = new Submission();
    $submission->setForm($form);
    $submission->setFieldValue('message', 'https://one.test https://two.test https://three.test');

    /** @var Settings $settings */
    $settings = Formie::$plugin->getSettings();
    $originalEnabled = $settings->enableMaximumLinks;
    $originalLimit = $settings->maximumLinks;

    try {
        $settings->enableMaximumLinks = true;
        $settings->maximumLinks = 2;

        $match = SpamHelper::checkMaximumLinks($submission);

        expect($match)->toBeArray()
            ->and($match['type'])->toBe('maximumLinks')
            ->and($match['value'])->toBe(3)
            ->and($match['limit'])->toBe(2);
    } finally {
        $settings->enableMaximumLinks = $originalEnabled;
        $settings->maximumLinks = $originalLimit;
    }
});

it('flags suspicious text in submission fields', function (): void {
    $form = createAbuseControlTestForm();
    $form->setFormLayout(new FieldLayout([
        'pages' => [[
            'label' => 'Page 1',
            'settings' => [],
            'rows' => [[
                'fields' => [[
                    'type' => SingleLineTextField::class,
                    'handle' => 'message',
                    'label' => 'Message',
                ]],
            ]],
        ]],
    ]));

    $submission = new Submission();
    $submission->setForm($form);
    $submission->setFieldValue('message', 'asdfghjkl qwertyuiop zxcvbnm');

    /** @var Settings $settings */
    $settings = Formie::$plugin->getSettings();
    $originalEnabled = $settings->enableSuspiciousTextDetection;

    try {
        $settings->enableSuspiciousTextDetection = true;

        $match = SpamHelper::checkSuspiciousText($submission);

        expect($match)->toBeArray()
            ->and($match['type'])->toBe('suspiciousText')
            ->and($match['value'])->toBe('message');
    } finally {
        $settings->enableSuspiciousTextDetection = $originalEnabled;
    }
});

it('persists abuse control settings in the spam protection store', function (): void {
    $saved = Formie::$plugin->getSpamProtection()->saveValues(array_merge(
        Formie::$plugin->getSpamProtection()->getSettingsValues(),
        [
            'enableSuspiciousTextDetection' => true,
            'suspiciousTextAllowedTerms' => "RFP\n",
            'enableMaximumLinks' => true,
            'maximumLinks' => 4,
            'enableGlobalSubmissionThrottling' => true,
            'globalSubmissionThrottleLimit' => 25,
            'globalSubmissionThrottleWindowSeconds' => 120,
            'enableIpSubmissionThrottling' => true,
            'ipSubmissionThrottleMinutes' => 10,
        ],
    ));

    expect($saved)->toBeTrue();

    $values = Formie::$plugin->getSpamProtection()->getSettingsValues();

    expect($values['enableSuspiciousTextDetection'])->toBeTrue()
        ->and($values['suspiciousTextAllowedTerms'])->toContain('RFP')
        ->and($values['enableMaximumLinks'])->toBeTrue()
        ->and($values['maximumLinks'])->toBe(4)
        ->and($values['enableGlobalSubmissionThrottling'])->toBeTrue()
        ->and($values['globalSubmissionThrottleLimit'])->toBe(25)
        ->and($values['globalSubmissionThrottleWindowSeconds'])->toBe(120)
        ->and($values['enableIpSubmissionThrottling'])->toBeTrue()
        ->and($values['ipSubmissionThrottleMinutes'])->toBe(10);
});

it('flags browser submissions when global submission throttling is exceeded', function (): void {
    $form = createAbuseControlTestForm();
    $cache = Craft::$app->getCache();
    $cache->delete(SubmissionGuards::GLOBAL_THROTTLE_CACHE_KEY);

    /** @var Settings $settings */
    $settings = Formie::$plugin->getSettings();
    $originalEnabled = $settings->enableGlobalSubmissionThrottling;
    $originalLimit = $settings->globalSubmissionThrottleLimit;
    $originalWindow = $settings->globalSubmissionThrottleWindowSeconds;
    $originalMinSubmitEnabled = $settings->enableMinimumSubmitTime;

    try {
        $settings->enableGlobalSubmissionThrottling = true;
        $settings->globalSubmissionThrottleLimit = 1;
        $settings->globalSubmissionThrottleWindowSeconds = 60;
        $settings->enableMinimumSubmitTime = false;

        $firstReason = withAbuseControlPostContext(function () use ($form): ?string {
            $submission = new Submission();
            $submission->setForm($form);

            return Formie::$plugin->getSubmissionGuards()->validateRequest(new SubmissionRequest([
                'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
                'form' => $form,
                'submission' => $submission,
                'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
                'requestToken' => 'throttle-token-1-' . uniqid(),
            ]));
        }, [
            'handle' => $form->handle,
        ]);

        $secondReason = withAbuseControlPostContext(function () use ($form): ?string {
            $submission = new Submission();
            $submission->setForm($form);

            return Formie::$plugin->getSubmissionGuards()->validateRequest(new SubmissionRequest([
                'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
                'form' => $form,
                'submission' => $submission,
                'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
                'requestToken' => 'throttle-token-2-' . uniqid(),
            ]));
        }, [
            'handle' => $form->handle,
        ]);

        expect($firstReason)->toBeNull()
            ->and($secondReason)->toContain('rate limit');
    } finally {
        $settings->enableGlobalSubmissionThrottling = $originalEnabled;
        $settings->globalSubmissionThrottleLimit = $originalLimit;
        $settings->globalSubmissionThrottleWindowSeconds = $originalWindow;
        $settings->enableMinimumSubmitTime = $originalMinSubmitEnabled;
        $cache->delete(SubmissionGuards::GLOBAL_THROTTLE_CACHE_KEY);
    }
});
