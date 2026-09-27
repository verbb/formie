<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\server\SubmissionsController as ServerSubmissionsController;
use verbb\formie\controllers\SubmissionsController;
use verbb\formie\helpers\References;
use verbb\formie\helpers\UrlHelper as FormieUrlHelper;
use yii\web\ForbiddenHttpException;

beforeEach(function () { \verbb\formie\Formie::$plugin->getSettings()->completionRedirectAllowedOrigins = ['https://example.test']; });

it('forbids guest control-panel variants of anonymous legacy submission actions', function (): void {
    WebRequestTestHelper::withWebRequestContext(function ($request): void {
        $request->setIsCpRequest(true);

        $controller = new SubmissionsController('formie-submissions-guest-cp', Craft::$app);

        expect(fn() => $controller->beforeAction(
            new \yii\base\Action('submit', $controller)
        ))->toThrow(ForbiddenHttpException::class, 'Public submissions require the site request contract.');
    }, [
        'method' => 'POST',
        'requestUri' => '/admin/actions/formie/submissions/submit',
    ]);
})->group('security');

it('forbids guest control-panel variants of anonymous server submission actions', function (): void {
    WebRequestTestHelper::withWebRequestContext(function ($request): void {
        $request->setIsCpRequest(true);

        $controller = new ServerSubmissionsController('formie-server-submissions-guest-cp', Craft::$app);

        expect(fn() => $controller->beforeAction(
            new \yii\base\Action('submit', $controller)
        ))->toThrow(ForbiddenHttpException::class, 'Public submissions require the site request contract.');
    }, [
        'method' => 'POST',
        'requestUri' => '/admin/actions/formie/server/submissions/submit',
    ]);
})->group('security');

it('does not substitute the completion request query for journey-start capture', function (): void {
    $form = formie()
        ->form(['title' => 'Redirect Query Literal'])
        ->singleLineTextField('fullName')
        ->create();

    $submission = formie()->submission($form)->with(['fullName' => 'Ada'])->save();
    $form->setCurrentSubmission($submission);

    $form->settings->setAttributes([
        'submitAction' => 'url',
        'submitActionUrl' => 'https://example.test/thanks?name=' . References::field((string)$form->getFieldByHandle('fullName')->reference),
        'submitActionTab' => 'same-tab',
    ], false);
    expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($form): void {
        $request->setIsCpRequest(false);
        $request->setQueryParams([
            'utm_source' => 'newsletter',
            'inject' => '{{7*7}}',
            'refToken' => '{submission:id}',
        ]);

        $url = $form->getRedirectUrl();

        expect($url)->toContain('example.test/thanks')
            ->and($url)->toContain('name=Ada')
            ->and($url)->not->toContain('utm_source=newsletter')
            // Brace-bearing request values must not remain raw for later template passes.
            ->and($url)->not->toContain(rawurlencode('{{7*7}}'))
            ->and($url)->not->toContain(rawurlencode('{submission:id}'))
            ->and($url)->not->toContain('{{7*7}}')
            ->and($url)->not->toMatch('/\{submission:id\}/');
    }, [
        'method' => 'GET',
        'requestUri' => '/contact',
    ]);
})->group('security');

it('only swaps siteActionUrl host when it exactly matches the CP host', function (): void {
    expect(FormieUrlHelper::swapCpHostForSiteHost(
        'https://staging.example.com/actions/formie/x',
        'staging.example.com',
        'my-project.staging.example.com',
    ))->toBe('https://my-project.staging.example.com/actions/formie/x')
        ->and(FormieUrlHelper::swapCpHostForSiteHost(
            'https://my-project.staging.example.com/actions/formie/x',
            'staging.example.com',
            'my-project.staging.example.com',
        ))->toBe('https://my-project.staging.example.com/actions/formie/x');
})->group('security');

it('does not forward unallowlisted brace-bearing query values', function (): void {
    WebRequestTestHelper::withWebRequestContext(function ($request): void {
        $request->setIsCpRequest(false);
        $request->setQueryParams([
            'q' => '{craft.app}',
            't' => '{{7*7}}',
        ]);

        $url = FormieUrlHelper::appendRequestQueryString('https://example.test/path?keep=1');

        expect($url)->toContain('keep=1')
            ->and($url)->not->toContain(rawurlencode('{craft.app}'))
            ->and($url)->not->toContain(rawurlencode('{{7*7}}'))
            ->and($url)->not->toContain('{craft.app}')
            ->and($url)->not->toContain('{{7*7}}');
    }, [
        'method' => 'GET',
        'requestUri' => '/path',
    ]);
})->group('security');
