<?php

use Tests\Support\WebRequestTestHelper;
use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\enums\SubmissionAuthorityType;
use verbb\formie\enums\SubmissionOperation;
use verbb\formie\enums\NavigationIntent;
use verbb\formie\models\Settings;
use verbb\formie\models\SubmissionAuthority;
use yii\web\ForbiddenHttpException;
use yii\web\TooManyRequestsHttpException;

function guardCommand($form, array $attributes = []) {
    return submissionCommand($attributes + [
        'form' => $form, 'submission' => new Submission(),
        'authority' => new SubmissionAuthority(SubmissionAuthorityType::VISITOR, (int)$form->id, null, 'guard-session'),
        'requestToken' => $form->getRequestToken(),
    ]);
}

it('requires a signed form-bound token for every interactive write', function ($operation, $navigation) {
    $form = formie()->form()->create();
    WebRequestTestHelper::withWebRequestContext(function () use ($form, $operation, $navigation) {
        $command = guardCommand($form, ['operation' => $operation, 'navigation' => $navigation, 'requestToken' => 'untrusted']);
        expect(fn() => Formie::$plugin->getSubmissionGuards()->validateRequest($command, false))->toThrow(ForbiddenHttpException::class);
        $other = formie()->form()->create();
        expect(fn() => Formie::$plugin->getSubmissionGuards()->validateRequest(guardCommand($form, ['requestToken' => $other->getRequestToken()]), false))->toThrow(ForbiddenHttpException::class);
    }, ['method' => 'POST']);
})->with([
    [SubmissionOperation::SUBMIT, NavigationIntent::ADVANCE],
    [SubmissionOperation::SUBMIT, NavigationIntent::BACK],
    [SubmissionOperation::SAVE_DRAFT, NavigationIntent::STAY],
]);

it('applies browser bot guards explicitly and keeps headless writes subject to integrity checks', function () {
    $form = formie()->form()->create();
    $settings = Formie::$plugin->getSettings();
    $original = $settings->getAttributes();
    try {
        $settings->enableHoneypot = true;
        $settings->enableMinimumSubmitTime = false;
        WebRequestTestHelper::withWebRequestContext(function () use ($form) {
            $guards = Formie::$plugin->getSubmissionGuards();
            expect($guards->validateRequest(guardCommand($form), true))->toContain('Honeypot');
            expect($guards->validateRequest(guardCommand($form), false))->toBeNull();
            expect($guards->validateRequest(guardCommand($form, ['operation' => SubmissionOperation::SAVE_DRAFT]), true))->toContain('Honeypot');
        }, ['method' => 'POST', 'bodyParams' => ['formieHoneypot' => 'bot']]);
    } finally { $settings->setAttributes($original, false); }
});

it('checks minimum submit time only on forward submission and enforces rate limits on drafts', function () {
    $form = formie()->form()->create();
    $settings = Formie::$plugin->getSettings();
    $original = $settings->getAttributes();
    try {
        $settings->enableHoneypot = false;
        $settings->enableMinimumSubmitTime = true;
        $settings->minimumSubmitTime = 30;
        WebRequestTestHelper::withWebRequestContext(function () use ($form, $settings) {
            $guards = Formie::$plugin->getSubmissionGuards();
            expect($guards->validateRequest(guardCommand($form), true))->not->toBeNull();
            expect($guards->validateRequest(guardCommand($form, ['operation' => SubmissionOperation::SAVE_DRAFT, 'navigation' => NavigationIntent::STAY]), true))->toBeNull();
            $settings->enableGlobalSubmissionThrottling = true;
            $settings->globalSubmissionThrottleLimit = 1;
            Craft::$app->getCache()->delete(\verbb\formie\services\SubmissionGuards::GLOBAL_THROTTLE_CACHE_KEY);
            $guards->validateRequest(guardCommand($form), false);
            expect(fn() => $guards->validateRequest(guardCommand($form, ['operation' => SubmissionOperation::SAVE_DRAFT]), false))->toThrow(TooManyRequestsHttpException::class);
        }, ['method' => 'POST', 'bodyParams' => ['formStartedAt' => (string)(int)(microtime(true) * 1000)]]);
    } finally { $settings->setAttributes($original, false); }
});

it('persists submission guard settings in the spam protection store', function (): void {
    Formie::$plugin->getSpamProtection()->saveValues(array_merge(
        Formie::$plugin->getSpamProtection()->getSettingsValues(),
        [
            'enableHoneypot' => false,
            'minimumSubmitTime' => 12,
            'enableReplayProtection' => false,
        ],
    ));

    $settings = new Settings();
    Formie::$plugin->getSpamProtection()->hydrateSettings($settings);

    expect($settings->enableHoneypot)->toBeFalse()
        ->and($settings->minimumSubmitTime)->toBe(12)
        ->and($settings->enableReplayProtection)->toBeFalse();
});
