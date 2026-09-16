<?php

declare(strict_types=1);

use craft\db\Query;
use craft\elements\User;
use Tests\Support\WebRequestTestHelper;
use verbb\auth\Auth;
use verbb\auth\models\Token;
use verbb\formie\controllers\IntegrationsController;
use verbb\formie\Formie;
use verbb\formie\integrations\emailmarketing\Mailchimp;
use verbb\formie\services\Integrations;
use yii\web\BadRequestHttpException;
use yii\web\MethodNotAllowedHttpException;

it('preserves integration credentials unless disconnect is submitted with a valid request', function (string $method, bool $csrf, ?string $exception): void {
    $integration = new Mailchimp([
        'name' => 'Disconnect request',
        'handle' => 'disconnect' . bin2hex(random_bytes(6)),
        'scope' => Integrations::SCOPE_SITE,
        'enabled' => false,
    ]);
    expect(Formie::$plugin->getIntegrations()->saveIntegration($integration, false))->toBeTrue();

    $tokens = Auth::getInstance()->getTokens();
    $token = new Token([
        'ownerHandle' => 'formie',
        'providerType' => Mailchimp::class,
        'tokenType' => Token::TOKEN_TYPE_OAUTH2,
        'reference' => (string)$integration->id,
        'accessToken' => 'synthetic-disconnect-token',
    ]);
    expect($tokens->saveToken($token))->toBeTrue();

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($integration, $token, $method, $csrf, $exception): void {
        Craft::$app->getUser()->setIdentity(User::find()->admin(true)->one());
        $request->setIsCpRequest(true);
        $params = ['integration' => $integration->handle];
        if ($csrf) {
            $params[$request->csrfParam] = $request->getCsrfToken();
        }
        if ($method === 'POST') {
            $request->setBodyParams($params);
        } else {
            $request->setQueryParams($params);
        }

        $controller = new IntegrationsController('integrations', Formie::$plugin);
        if ($exception) {
            expect(fn() => $controller->runAction('disconnect'))->toThrow($exception);
        } else {
            expect($controller->runAction('disconnect')->statusCode)->toBe(200);
        }

        expect((new Query())->from('{{%auth_oauth_tokens}}')->where(['id' => $token->id])->exists())
            ->toBe($exception !== null);
    }, ['method' => $method, 'headers' => ['Accept' => 'application/json']]);
})->with([
    'read request' => ['GET', false, MethodNotAllowedHttpException::class],
    'incomplete submission' => ['POST', false, BadRequestHttpException::class],
    'valid submission' => ['POST', true, null],
]);
