<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\client\SubmissionsController;
use verbb\formie\elements\Submission;
use verbb\formie\Formie;
use yii\web\BadRequestHttpException;

it('rejects a missing csrf token and accepts the same request with its valid token', function (): void {
    $form = formie()->form()->singleLineTextField('fullName')->create();
    Formie::$plugin->getSettings()->enableCsrfValidationForGuests = true;
    WebRequestTestHelper::withWebRequestContext(function ($request) use ($form): void {
        $session = Formie::$plugin->getClientSessionService()->issueInitialSession($form)->toArrayRecursive();
        $body = ['handle' => $form->handle, 'action' => 'submit', 'values' => ['fullName' => 'CSRF control'], 'session' => $session];
        $count = fn() => (int)Submission::find()->formId($form->id)->status(null)->isSpam(null)->isIncomplete(null)->count();
        $before = $count();
        $queueBefore = (int)(new \craft\db\Query())->from('{{%queue}}')->count();
        $request->setBodyParams($body);
        $controller = new SubmissionsController('client-submissions', Craft::$app);
        expect(fn() => $controller->runAction('submit'))->toThrow(BadRequestHttpException::class, 'Unable to verify your data submission.')
            ->and($count())->toBe($before)
            ->and((int)(new \craft\db\Query())->from('{{%queue}}')->count())->toBe($queueBefore);

        $request->setBodyParams($body + [$request->csrfParam => $request->getCsrfToken()]);
        $result = (new SubmissionsController('client-submissions', Craft::$app))->runAction('submit');
        expect($result->data['success'])->toBeTrue()
            ->and($count())->toBe($before + 1)
            ->and(Submission::find()->formId($form->id)->one()->getFieldValue('fullName'))->toBe('CSRF control');
    }, ['method' => 'POST', 'headers' => ['Accept' => 'application/json']]);
})->group('security');

it('limits browser access to bootstrap tokens to configured origins', function (string $origin, ?string $allowed): void {
    $form = formie()->form()->singleLineTextField('name')->create();
    WebRequestTestHelper::withWebRequestContext(function () use ($form, $allowed): void {
        Craft::$app->getConfig()->getGeneral()->allowedGraphqlOrigins = ['https://frontend.example.test'];
        $response = (new \verbb\formie\controllers\client\FormsController('forms', Formie::$plugin))->runAction('load');
        expect($response->data['definition']['handle'])->toBe($form->handle)
            ->and($response->getHeaders()->get('Access-Control-Allow-Origin'))->toBe($allowed)
            ->and($response->getHeaders()->get('Access-Control-Allow-Credentials'))->toBe($allowed ? 'true' : null);
    }, ['method' => 'POST', 'bodyParams' => ['handle' => $form->handle], 'headers' => ['Accept' => 'application/json', 'Origin' => $origin]]);
})->with([
    'configured browser' => ['https://frontend.example.test', 'https://frontend.example.test'],
    'untrusted browser' => ['https://untrusted.example.test', null],
])->group('security');

it('bootstraps a public form without a prior csrf token and supplies one for submissions', function (): void {
    $form = formie()->form()->singleLineTextField('name')->create();
    Formie::$plugin->getSettings()->enableCsrfValidationForGuests = true;
    WebRequestTestHelper::withWebRequestContext(function () use ($form): void {
        $before = (int)Submission::find()->formId($form->id)->status(null)->isIncomplete(null)->isSpam(null)->count();
        $response = (new \verbb\formie\controllers\client\FormsController('forms', Formie::$plugin))->runAction('load');
        expect($response->data['definition']['handle'])->toBe($form->handle)
            ->and($response->data['session']['tokens']['csrf']['value'])->not->toBeEmpty()
            ->and((int)Submission::find()->formId($form->id)->status(null)->isIncomplete(null)->isSpam(null)->count())->toBe($before);
    }, ['method' => 'POST', 'bodyParams' => ['handle' => $form->handle], 'headers' => ['Accept' => 'application/json']]);
})->group('security');
