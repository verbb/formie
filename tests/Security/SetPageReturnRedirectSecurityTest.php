<?php

declare(strict_types=1);

use Craft;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\SubmissionsController;
use verbb\formie\helpers\SetPageReturnUrlHelper;

it('legacy set-page redirect follows signed return token and ignores referer', function (): void {
    $form = formie()
        ->form(['title' => 'Legacy Set Page Redirect'])
        ->multiPage(2)
        ->onPage(1)
        ->singleLineTextField('firstName')
        ->onPage(2)
        ->singleLineTextField('lastName')
        ->create();

    $pageId = (int)($form->getPages()[1]->id ?? 0);

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($form, $pageId): void {
        $request->setPathInfo('formie/safe-return');
        $request->setUrl('https://craft.example.test/formie/safe-return');
        $request->getHeaders()->set('Referer', 'https://evil.example.com/phish');

        $token = SetPageReturnUrlHelper::createTokenFromCurrentRequest($request);
        expect($token)->not->toBeNull();
        expect(Craft::$app->getSecurity()->validateData($token))->toBe('/formie/safe-return');

        $request->setBodyParams([
            'handle' => (string)$form->handle,
            'pageId' => (string)$pageId,
            SetPageReturnUrlHelper::QUERY_PARAM => $token,
        ]);

        $controller = new SubmissionsController('formie-submissions-set-page-redirect', Craft::$app);
        $response = $controller->actionSetPage();

        expect($response->statusCode)->toBe(302);

        $location = (string)$response->getHeaders()->get('location');
        expect($location)->toContain('formie/safe-return')
            ->not->toContain('evil.example');
    }, [
        'method' => 'POST',
    ]);
})->group('security');

it('legacy set-page redirect rejects tampered return token', function (): void {
    $form = formie()
        ->form(['title' => 'Legacy Set Page Tamper'])
        ->multiPage(2)
        ->onPage(1)
        ->singleLineTextField('firstName')
        ->onPage(2)
        ->singleLineTextField('lastName')
        ->create();

    $pageId = (int)($form->getPages()[1]->id ?? 0);

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($form, $pageId): void {
        $request->setPathInfo('actions/formie/submissions/set-page');
        $request->setUrl('https://craft.example.test/actions/formie/submissions/set-page?handle=x&pageId=1');
        $request->getHeaders()->set('Referer', 'https://evil.example.com/phish');

        $request->setBodyParams([
            'handle' => (string)$form->handle,
            'pageId' => (string)$pageId,
            SetPageReturnUrlHelper::QUERY_PARAM => 'not-a-valid-token',
        ]);

        $controller = new SubmissionsController('formie-submissions-set-page-tamper', Craft::$app);
        $response = $controller->actionSetPage();

        expect($response->statusCode)->toBe(302);

        $location = (string)$response->getHeaders()->get('location');
        expect($location)->not->toContain('evil.example');
    }, [
        'method' => 'POST',
    ]);
})->group('security');
