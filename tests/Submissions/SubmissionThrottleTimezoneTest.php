<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use verbb\formie\elements\Submission;
use verbb\formie\Formie;
use verbb\formie\helpers\Table;
use yii\web\TooManyRequestsHttpException;

it('enforces the IP throttle window in elapsed minutes regardless of timezone', function (string $timezone, bool $crossTransition): void {
    $settings = Formie::$plugin->getSettings();
    $settings->enableGlobalSubmissionThrottling = false;
    $settings->enableIpSubmissionThrottling = true;
    $settings->ipSubmissionThrottleMinutes = 5;
    $form = formie()->form()->singleLineTextField('message')->create();
    $saved = formie()->submission($form)->save();
    $originalTimezone = date_default_timezone_get();

    try {
        WebRequestTestHelper::withWebRequestContext(function () use ($timezone, $crossTransition, $settings, $form, $saved): void {
            Craft::$app->setTimeZone($timezone);
            $now = time();

            if ($crossTransition) {
                // Span the latest clock change without mocking PHP's clock or the guard.
                // Local-time subtraction followed by UTC conversion must also fail this case.
                $transitions = (new DateTimeZone($timezone))->getTransitions($now - 370 * 86400, $now);
                expect(count($transitions))->toBeGreaterThan(1);
                $transition = $transitions[array_key_last($transitions)];
                $settings->ipSubmissionThrottleMinutes = (int)ceil(($now - $transition['ts'] + 120) / 60);
            }

            $cutoff = $now - $settings->ipSubmissionThrottleMinutes * 60;
            $check = fn() => Formie::$plugin->getSubmissionGuards()->validateRequest(
                guardCommand($form, ['submission' => new Submission(['ipAddress' => '192.0.2.233'])]), false,
            );

            Craft::$app->getDb()->createCommand()->update(Table::FORMIE_SUBMISSIONS, [
                'ipAddress' => '192.0.2.233',
                'dateCreated' => gmdate('Y-m-d H:i:s', $cutoff + 60),
            ], ['id' => $saved->id])->execute();
            expect($check)->toThrow(TooManyRequestsHttpException::class, 'Too many submissions from this IP address.');

            Craft::$app->getDb()->createCommand()->update(Table::FORMIE_SUBMISSIONS, [
                'dateCreated' => gmdate('Y-m-d H:i:s', $cutoff - 60),
            ], ['id' => $saved->id])->execute();
            expect($check())->toBeNull();
        }, ['method' => 'POST']);
    } finally {
        Craft::$app->setTimeZone($originalTimezone);
    }
})->with([
    'UTC' => ['UTC', false],
    'positive offset' => ['Europe/Zagreb', false],
    'negative offset' => ['America/New_York', false],
    'Melbourne' => ['Australia/Melbourne', false],
    'Zagreb clock change' => ['Europe/Zagreb', true],
    'Melbourne clock change' => ['Australia/Melbourne', true],
]);
