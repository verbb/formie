<?php

declare(strict_types=1);

use craft\elements\Entry;
use craft\elements\User;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\FieldsController;
use verbb\formie\Formie;

// Users have no title, so a title-based label showed selected users as "Element <id>" in the builder.
it('labels selected element chips with the element UI label', function(): void {
    $admin = User::find()->admin(true)->status(null)->one();
    $name = 'element-label-' . bin2hex(random_bytes(4));
    $user = new User([
        'username' => $name,
        'email' => $name . '@example.test',
        'fullName' => 'Chip Label User',
        'active' => true,
        'pending' => false,
    ]);
    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();
    $entry = Entry::find()->status(null)->one();

    if (!$admin || !$entry) {
        test()->markTestSkipped('An admin user and an entry are required.');
    }

    $originalUser = Craft::$app->getUser()->getIdentity();

    try {
        WebRequestTestHelper::withWebRequestContext(function($request) use ($admin, $user, $entry): void {
            $request->setIsCpRequest(true);
            Craft::$app->getUser()->setIdentity($admin);
            $request->setBodyParams([
                $request->csrfParam => $request->getCsrfToken(),
                'elements' => [
                    ['id' => $user->id, 'siteId' => $user->siteId],
                    ['id' => $entry->id, 'siteId' => $entry->siteId],
                ],
            ]);
            $data = (new FieldsController('formie-fields-labels', Formie::$plugin))
                ->runAction('get-element-select-options')
                ->data;

            expect(array_column($data, 'label', 'id'))->toBe([
                $user->id => 'Chip Label User',
                $entry->id => $entry->getUiLabel(),
            ]);
        }, [
            'method' => 'POST',
            'headers' => ['Accept' => 'application/json'],
        ]);
    } finally {
        Craft::$app->getUser()->setIdentity($originalUser);
        Craft::$app->getElements()->deleteElement($user, true);
    }
});
