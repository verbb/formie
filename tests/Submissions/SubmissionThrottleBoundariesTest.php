<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use verbb\formie\elements\Submission;
use verbb\formie\Formie;
use verbb\formie\helpers\Table;
use verbb\formie\services\SubmissionGuards;
use yii\web\TooManyRequestsHttpException;

it('accepts requests after the global throttle window expires and starts a fresh allowance', function (): void {
    $settings = Formie::$plugin->getSettings();
    $settings->enableGlobalSubmissionThrottling = true;
    $settings->globalSubmissionThrottleLimit = 1;
    $settings->globalSubmissionThrottleWindowSeconds = 60;
    $settings->enableIpSubmissionThrottling = false;
    $cache = Craft::$app->getCache();
    $key = SubmissionGuards::GLOBAL_THROTTLE_CACHE_KEY;
    $previous = $cache->get($key);
    $form = createGuardTestForm();

    try {
        $cache->set($key, ['count' => 1, 'resetAt' => time() + 60], 60);
        WebRequestTestHelper::withWebRequestContext(function () use ($form, $cache, $key): void {
            $check = fn() => Formie::$plugin->getSubmissionGuards()->validateRequest(guardCommand($form), false);
            expect($check)->toThrow(TooManyRequestsHttpException::class);

            // Keep the expired record in cache to exercise the window boundary itself.
            $cache->set($key, ['count' => 1, 'resetAt' => time()], 60);
            expect($check())->toBeNull()
                ->and($cache->get($key)['count'])->toBe(1)
                ->and($cache->get($key)['resetAt'])->toBeGreaterThan(time());
            expect($check)->toThrow(TooManyRequestsHttpException::class);
        }, ['method' => 'POST']);
    } finally {
        $cache->delete($key);
        if (is_array($previous) && ($previous['resetAt'] ?? 0) > time()) {
            $cache->set($key, $previous, $previous['resetAt'] - time());
        }
    }
});

it('limits an IP only within its form and admits it again after the configured window', function (): void {
    $settings = Formie::$plugin->getSettings();
    $settings->enableGlobalSubmissionThrottling = false;
    $settings->enableIpSubmissionThrottling = true;
    $settings->ipSubmissionThrottleMinutes = 5;
    $form = formie()->form()->singleLineTextField('message')->create();
    $otherForm = formie()->form()->singleLineTextField('message')->create();
    $saved = formie()->submission($form)->save();
    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_SUBMISSIONS, [
        'ipAddress' => '192.0.2.231',
        'dateCreated' => gmdate('Y-m-d H:i:s'),
    ], ['id' => $saved->id])->execute();

    WebRequestTestHelper::withWebRequestContext(function () use ($form, $otherForm, $saved): void {
        $check = fn($target, string $ip) => Formie::$plugin->getSubmissionGuards()->validateRequest(
            guardCommand($target, ['submission' => new Submission(['ipAddress' => $ip])]), false,
        );
        expect(fn() => $check($form, '192.0.2.231'))->toThrow(TooManyRequestsHttpException::class);
        expect($check($form, '192.0.2.232'))->toBeNull()
            ->and($check($otherForm, '192.0.2.231'))->toBeNull();

        Craft::$app->getDb()->createCommand()->update(Table::FORMIE_SUBMISSIONS, [
            'dateCreated' => gmdate('Y-m-d H:i:s', time() - 360),
        ], ['id' => $saved->id])->execute();
        expect($check($form, '192.0.2.231'))->toBeNull();
    }, ['method' => 'POST']);
});
