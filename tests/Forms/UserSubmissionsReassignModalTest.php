<?php

declare(strict_types=1);

use craft\elements\User;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\UserSubmissionsController;
use verbb\formie\Formie;

it('renders the modal for reassigning a deleted user\'s submissions', function (): void {
    $user = new User(['username' => 'reassign' . bin2hex(random_bytes(4)), 'email' => bin2hex(random_bytes(4)) . '@example.test']);
    expect(Craft::$app->getElements()->saveElement($user, false))->toBeTrue();

    try {
        $html = WebRequestTestHelper::withWebRequestContext(function ($request) use ($user): string {
            Craft::$app->getUser()->setIdentity(User::find()->admin(true)->one());
            $request->setIsCpRequest(true);
            $request->setBodyParams(['elementIds' => [$user->id], $request->csrfParam => $request->getCsrfToken()]);

            $controller = new UserSubmissionsController('user-submissions', Formie::$plugin);
            $response = $controller->runAction('reassign-modal');

            return ($response->contentHtml)();
        }, ['method' => 'POST', 'headers' => ['Accept' => 'application/json']]);

        expect($html)->toContain('name="newUserId"')
            ->and($html)->toContain('name="elementIds[]" value="' . $user->id . '"');
    } finally {
        Craft::$app->getElements()->deleteElement($user, true);
    }
});
