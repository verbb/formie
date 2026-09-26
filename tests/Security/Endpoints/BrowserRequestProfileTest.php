<?php

use verbb\formie\Formie;
use verbb\formie\controllers\client\SubmissionsController;
use verbb\formie\helpers\BrowserRequestProfile;
use verbb\formie\elements\Submission;
use Tests\Support\WebRequestTestHelper;

it('rejects a disallowed origin before any submission or queued work is created', function() {
    $form = formie()->form()->singleLineTextField('name')->create();
    WebRequestTestHelper::withWebRequestContext(function($request) use ($form) {
        $before = (int)Submission::find()->formId($form->id)->status(null)->isIncomplete(null)->isSpam(null)->count();
        $queue = (new \craft\db\Query())->from('{{%queue}}')->count();
        expect(fn() => (new SubmissionsController('submissions', Formie::$plugin))->runAction('submit'))->toThrow(\yii\web\ForbiddenHttpException::class)
            ->and((int)Submission::find()->formId($form->id)->status(null)->isIncomplete(null)->isSpam(null)->count())->toBe($before)
            ->and((new \craft\db\Query())->from('{{%queue}}')->count())->toBe($queue);
    }, ['method' => 'POST', 'headers' => ['Origin' => 'https://evil.example.test', 'Accept' => 'application/json', 'X-Formie-Profile' => 'cross-origin-public'], 'bodyParams' => ['handle' => $form->handle, 'values' => ['name' => 'attack']]]);
});

it('requires explicit public credentials and isolates an ambient administrative identity', function() {
    WebRequestTestHelper::withWebRequestContext(function($request, $response, $session) {
        Formie::$plugin->getSettings()->allowedOrigins = ['https://public.example.test'];
        Craft::$app->getUser()->setIdentity(\craft\elements\User::find()->admin(true)->one());
        $session->set('ambient-secret', 'not-public');
        expect(fn() => BrowserRequestProfile::enter())->toThrow(\yii\web\ForbiddenHttpException::class);
        expect(BrowserRequestProfile::enter(true))->toBe(BrowserRequestProfile::CROSS_ORIGIN)
            ->and(Craft::$app->getUser()->getIsGuest())->toBeTrue()
            ->and($session->get('ambient-secret'))->toBeNull();
        $token = $response->getHeaders()->get('X-Formie-Session');
        expect($token)->not->toBeEmpty();
        $request->getHeaders()->set('X-Formie-Session', $token);
        $session->set('public-marker', 'retained');
        BrowserRequestProfile::enter();
        expect($session->get('public-marker'))->toBe('retained');
    }, ['method' => 'POST', 'headers' => ['Origin' => 'https://public.example.test', 'X-Formie-Profile' => 'cross-origin-public']]);
});

it('keeps public schemas out of the trusted administrative mutation profile', function() {
    $gql = Craft::$app->getGql();
    try { $previous = $gql->getActiveSchema(); } catch (\craft\errors\GqlException) { $previous = null; }
    try {
        $gql->setActiveSchema(new \craft\models\GqlSchema(['name' => 'Public', 'isPublic' => true, 'scope' => ['formieSubmissions.all:create']]));
        expect(fn() => BrowserRequestProfile::enterAdministrative())->toThrow(\yii\web\ForbiddenHttpException::class);
        $gql->setActiveSchema(new \craft\models\GqlSchema(['name' => 'Authenticated API', 'isPublic' => false]));
        BrowserRequestProfile::enterAdministrative();
        WebRequestTestHelper::withWebRequestContext(function() {
            expect(fn() => BrowserRequestProfile::enterAdministrative())->toThrow(\yii\web\ForbiddenHttpException::class);
        }, ['headers' => ['X-Formie-Profile' => 'same-origin-browser']]);
    } finally { $gql->setActiveSchema($previous); }
});

it('rejects same-origin submission without CSRF even when the legacy guest exception is enabled', function() {
    WebRequestTestHelper::withWebRequestContext(function() {
        Formie::$plugin->getSettings()->enableCsrfValidationForGuests = false;
        expect(fn() => (new SubmissionsController('submissions', Formie::$plugin))->runAction('submit'))->toThrow(\yii\web\BadRequestHttpException::class);
    }, ['method' => 'POST', 'headers' => ['Accept' => 'application/json', 'X-Formie-Profile' => 'same-origin-browser']]);
});
