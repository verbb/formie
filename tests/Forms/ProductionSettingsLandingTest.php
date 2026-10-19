<?php

declare(strict_types=1);

use craft\elements\User;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\SettingsController;
use verbb\formie\Formie;
use yii\web\ForbiddenHttpException;

it('routes production settings to an available permitted page', function(bool $admin, array $permissions, ?string $page): void {
    $name = 'productionSettings' . bin2hex(random_bytes(6));
    $user = new User(['username' => $name, 'email' => $name . '@example.test', 'admin' => $admin]);
    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();
    expect(Craft::$app->getUserPermissions()->saveUserPermissions($user->id, [
        'accessCp', 'accessPlugin-formie', ...$permissions,
    ]))->toBeTrue();

    WebRequestTestHelper::withWebRequestContext(function($request) use ($user, $page): void {
        Craft::$app->getConfig()->getGeneral()->allowAdminChanges = false;
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(User::find()->id($user->id)->status(null)->one());
        $controller = new SettingsController('settings', Formie::$plugin);

        if ($page === null) {
            expect(fn() => $controller->runAction('index'))->toThrow(ForbiddenHttpException::class);
            return;
        }

        $response = $controller->runAction('index');
        expect($response->statusCode)->toBe(302)
            ->and($response->headers->get('Location'))->toContain('formie/settings/' . $page)
            ->and($response->headers->get('Location'))->not->toContain('formie/settings/general');
    });
})->with([
    'administrator' => [true, [], 'import-export'],
    'all settings' => [false, ['formie-accessSettings'], 'import-export'],
    'spam only' => [false, ['formie-settingsSpamProtection'], 'spam-protection'],
    'support only' => [false, ['formie-settingsSupport'], 'support'],
    'unavailable general settings' => [false, ['formie-settingsGeneral'], null],
    'no settings permission' => [false, [], null],
]);
