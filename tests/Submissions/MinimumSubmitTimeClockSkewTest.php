<?php

use Tests\Support\WebRequestTestHelper;
use verbb\formie\Formie;
use verbb\formie\enums\SubmissionOutcomeType;
use verbb\formie\models\HtmlSubmissionRequest;

/**
 * Browser timestamps for a visitor whose device clock differs from the server's.
 */
function skewedGuardTimestamps(int $clockOffsetSeconds, int $secondsOnForm): array
{
    $browserNow = (int)(microtime(true) * 1000) + $clockOffsetSeconds * 1000;

    return [
        'formStartedAt' => (string)($browserNow - $secondsOnForm * 1000),
        'formSubmittedAt' => (string)$browserNow,
    ];
}

function withSubmitTimeGuards(callable $callback, array $overrides = []): mixed
{
    $settings = Formie::$plugin->getSettings();
    $original = $settings->getAttributes();

    try {
        $settings->enableHoneypot = false;
        $settings->enableMinimumSubmitTime = true;
        $settings->minimumSubmitTime = 3;
        $settings->enableFormSubmitExpiration = false;
        $settings->setAttributes($overrides, false);

        return $callback();
    } finally {
        $settings->setAttributes($original, false);
    }
}

it('measures minimum submit time on the browser clock when the device clock is ahead or behind', function(int $clockOffsetSeconds) {
    $form = formie()->form()->create();

    withSubmitTimeGuards(function() use ($form, $clockOffsetSeconds) {
        WebRequestTestHelper::withWebRequestContext(function() use ($form) {
            expect(Formie::$plugin->getSubmissionGuards()->validateRequest(guardCommand($form), true))->toBeNull();
        }, ['method' => 'POST', 'bodyParams' => skewedGuardTimestamps($clockOffsetSeconds, 20)]);

        WebRequestTestHelper::withWebRequestContext(function() use ($form) {
            expect(Formie::$plugin->getSubmissionGuards()->validateRequest(guardCommand($form), true))->toBe('Form submitted too quickly.');
        }, ['method' => 'POST', 'bodyParams' => skewedGuardTimestamps($clockOffsetSeconds, 1)]);
    });
})->with([
    'clock a minute fast' => [60],
    'clock an hour fast' => [3600],
    'clock a minute slow' => [-60],
]);

it('falls back to the server clock when the browser submit time is missing or unusable', function(array $bodyParams, ?string $expected) {
    $form = formie()->form()->create();

    withSubmitTimeGuards(function() use ($form, $bodyParams, $expected) {
        WebRequestTestHelper::withWebRequestContext(function() use ($form, $expected) {
            expect(Formie::$plugin->getSubmissionGuards()->validateRequest(guardCommand($form), true))->toBe($expected);
        }, ['method' => 'POST', 'bodyParams' => $bodyParams]);
    });
})->with([
    'no submit time, started just now' => fn() => [['formStartedAt' => (string)(int)(microtime(true) * 1000)], 'Form submitted too quickly.'],
    'no submit time, started earlier' => fn() => [['formStartedAt' => (string)((int)(microtime(true) * 1000) - 20000)], null],
    'submit time before start time' => fn() => [['formStartedAt' => (string)(int)(microtime(true) * 1000), 'formSubmittedAt' => (string)((int)(microtime(true) * 1000) - 20000)], 'Form submitted too quickly.'],
    'non-numeric submit time' => fn() => [['formStartedAt' => (string)(int)(microtime(true) * 1000), 'formSubmittedAt' => 'later'], 'Form submitted too quickly.'],
    'no start time' => fn() => [['formSubmittedAt' => (string)(int)(microtime(true) * 1000)], 'Minimum submit time param missing.'],
]);

it('measures form submit expiration on the browser clock', function() {
    $form = formie()->form()->create();

    withSubmitTimeGuards(function() use ($form) {
        // A device clock two hours slow must not expire a form filled in for a minute.
        WebRequestTestHelper::withWebRequestContext(function() use ($form) {
            expect(Formie::$plugin->getSubmissionGuards()->validateRequest(guardCommand($form), true))->toBeNull();
        }, ['method' => 'POST', 'bodyParams' => skewedGuardTimestamps(-7200, 60)]);

        WebRequestTestHelper::withWebRequestContext(function() use ($form) {
            expect(Formie::$plugin->getSubmissionGuards()->validateRequest(guardCommand($form), true))->toBe('Form session has expired.');
        }, ['method' => 'POST', 'bodyParams' => skewedGuardTimestamps(0, 7200)]);
    }, ['enableFormSubmitExpiration' => true, 'formSubmitExpiration' => 3600]);
});

it('saves submissions from a fast device clock and logs guard rejections', function() {
    $form = formie()->form()->create();

    withSubmitTimeGuards(function() use ($form) {
        $submit = function(array $timestamps) use ($form) {
            return WebRequestTestHelper::withWebRequestContext(function() use ($form) {
                return runHtmlSubmission(new HtmlSubmissionRequest([
                    'handle' => $form->handle,
                    'siteId' => $form->siteId,
                    'submitAction' => 'submit',
                ]))->response;
            }, ['method' => 'POST', 'bodyParams' => $timestamps + ['formieHoneypot' => '']]);
        };

        $saved = $submit(skewedGuardTimestamps(60, 20));

        expect($saved->success)->toBeTrue()
            ->and($saved->outcome->type)->not->toBe(SubmissionOutcomeType::REJECTED)
            ->and($saved->submission->id)->not->toBeNull();

        $logger = Craft::getLogger();
        $messageOffset = count($logger->messages);
        $rejected = $submit(skewedGuardTimestamps(60, 1));
        $messages = array_column(array_slice($logger->messages, $messageOffset), 0);

        expect($rejected->outcome->type)->toBe(SubmissionOutcomeType::REJECTED)
            ->and($rejected->submission->id)->toBeNull()
            ->and(implode("\n", array_filter($messages, 'is_string')))->toContain('Form submitted too quickly.')
            ->and(implode("\n", array_filter($messages, 'is_string')))->toContain($form->handle);
    });
});
