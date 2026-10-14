<?php

use verbb\formie\Formie;
use Tests\Support\WebRequestTestHelper;
use yii\web\TooManyRequestsHttpException;

it('shares the anonymous request budget across clients at the same address', function (string $scope): void {
    $form = formie()->form()->singleLineTextField('name')->create();
    $settings = Formie::$plugin->getSettings();
    $oldBootstrap = $settings->anonymousFormBootstrapRateLimit;
    $oldRefresh = $settings->anonymousFormRefreshRateLimit;
    $oldWindow = $settings->anonymousFormRateWindowSeconds;
    $settings->anonymousFormBootstrapRateLimit = 1;
    $settings->anonymousFormRefreshRateLimit = 1;
    $settings->anonymousFormRateWindowSeconds = 60;

    try {
        WebRequestTestHelper::withWebRequestContext(function ($request) use ($form, $scope): void {
            $service = Formie::$plugin->getFormSessions();
            $issue = static fn() => $scope === 'bootstrap'
                ? $service->issueInitialSession($form, null, true)
                : $service->buildTokenPayload($form, true);
            $issue();
            expect($issue)->toThrow(TooManyRequestsHttpException::class);
            $admitted = 0;
            for ($i = 1; $i <= 5; $i++) {
                $request->getHeaders()->set('User-Agent', 'FormieClient/' . $i);
                try {
                    $issue();
                    $admitted++;
                } catch (TooManyRequestsHttpException) {
                }
            }
            expect($admitted)->toBe(0);
            expect((int)Craft::$app->getResponse()->getHeaders()->get('Retry-After'))->toBeGreaterThan(0);
            $otherForm = formie()->form()->singleLineTextField('name')->create();
            $other = $scope === 'bootstrap'
                ? $service->issueInitialSession($otherForm, null, true)
                : $service->buildTokenPayload($otherForm, true);
            expect($other)->not->toBeNull();
        }, ['method' => 'POST', 'remoteAddr' => '198.51.100.42', 'headers' => ['User-Agent' => 'FormieClient/0']]);
    } finally {
        $settings->anonymousFormBootstrapRateLimit = $oldBootstrap;
        $settings->anonymousFormRefreshRateLimit = $oldRefresh;
        $settings->anonymousFormRateWindowSeconds = $oldWindow;
    }
})->with(['bootstrap', 'refresh']);
