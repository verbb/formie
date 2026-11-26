<?php

use verbb\formie\Formie;
use verbb\formie\controllers\server\FormsController;
use verbb\formie\elements\Form;
use verbb\formie\services\RuntimeConfiguration;
use Tests\Support\WebRequestTestHelper;

use craft\db\Query;
use craft\helpers\Json;

// Statically cached pages keep the request token they were rendered with for as long as the cache lives (#3165).
function cachedInstanceConfigAgeRows(int $formId): void
{
    Craft::$app->getDb()->createCommand()->update('{{%formie_instance_configs}}', ['expiresAt' => time() - 86400], ['formId' => $formId])->execute();
    Formie::$plugin->getSubmissionProgress()->pruneProgress();
}

function cachedInstanceConfigTokenPayload(string $token): array
{
    return Json::decode(Craft::$app->getSecurity()->validateData($token));
}

it('keeps a cached page token working after the instance config retention period', function(): void {
    $form = formie()->form()->singleLineTextField('name')->hiddenField('pageUrl', ['valueSource' => 'currentUrl'])->create();
    $form->setSettings(['redirectUrl' => '/cached-thanks', 'displayFormTitle' => false]);
    Formie::$plugin->getRendering()->populateFormValues($form, ['name' => 'enforced'], true);
    $token = $form->getRequestToken();

    cachedInstanceConfigAgeRows($form->id);

    $fresh = Form::find()->id($form->id)->status(null)->one();
    (new RuntimeConfiguration())->restoreToken($fresh, $token);

    expect($fresh->settings->redirectUrl)->toBe('/cached-thanks')
        ->and($fresh->getInstanceConfig()->forced[$fresh->getFieldByHandle('name')->uid])->toBe('enforced')
        ->and($fresh->getInstanceConfig()->prefill)->toHaveKey($fresh->getFieldByHandle('pageUrl')->uid)
        ->and($token)->not->toContain('enforced')
        ->and($token)->not->toContain('cached-thanks');

    WebRequestTestHelper::withWebRequestContext(function() use ($form): void {
        $result = (new FormsController('server/forms', Formie::$plugin))->runAction('refresh-tokens');
        $refreshed = Form::find()->id($form->id)->status(null)->one();
        (new RuntimeConfiguration())->restoreToken($refreshed, $result->data['requestToken']);

        expect($refreshed->settings->redirectUrl)->toBe('/cached-thanks');
    }, ['method' => 'GET', 'queryParams' => ['handle' => $form->handle, 'siteId' => $form->siteId, 'requestToken' => $token], 'headers' => ['Accept' => 'application/json']]);
});

it('only restores an embedded instance config for the form and site it was issued for', function(): void {
    $form = formie()->form()->singleLineTextField('name')->create();
    $form->setRedirectUrl('/scoped');
    $token = $form->getRequestToken();
    $other = formie()->form()->singleLineTextField('name')->create();

    (new RuntimeConfiguration())->restoreToken($other, $token);

    expect($other->settings->redirectUrl)->not->toBe('/scoped')
        ->and($other->getInstanceConfig()->form)->toBe([]);
});

it('keeps instance configs too large for the request token in the database', function(): void {
    $form = formie()->form()->singleLineTextField('name')->create();
    Formie::$plugin->getRendering()->populateFormValues($form, ['name' => str_repeat('long value ', 400)]);
    $token = $form->getRequestToken();
    $payload = cachedInstanceConfigTokenPayload($token);

    expect($payload['config'])->toBeString()
        ->and($payload)->not->toHaveKey('configData')
        ->and(strlen($token))->toBeLessThan(RuntimeConfiguration::TOKEN_CONFIG_LIMIT + 500);

    $fresh = Form::find()->id($form->id)->status(null)->one();
    (new RuntimeConfiguration())->restoreToken($fresh, $token);

    expect($fresh->getInstanceConfig()->initial[$fresh->getFieldByHandle('name')->uid])->toBe(str_repeat('long value ', 400));
});

it('still restores tokens issued with a stored instance config', function(): void {
    $form = formie()->form()->singleLineTextField('name')->create();
    $form->setRedirectUrl('/stored');
    $runtime = new RuntimeConfiguration();
    $key = $runtime->persistInstance($form);
    $token = Craft::$app->getSecurity()->hashData(Json::encode(['form' => $form->uid, 'site' => $form->siteId, 'issued' => time(), 'nonce' => 'n', 'config' => $key]));
    $fresh = Form::find()->id($form->id)->status(null)->one();
    $runtime->restoreToken($fresh, $token);

    expect($fresh->settings->redirectUrl)->toBe('/stored');

    cachedInstanceConfigAgeRows($form->id);

    expect(fn() => $runtime->restoreToken(Form::find()->id($form->id)->status(null)->one(), $token))
        ->toThrow(\yii\web\ForbiddenHttpException::class, 'Form instance has expired. Reload the form.');
});
