<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\FieldsController;
use yii\web\ForbiddenHttpException;

it('requires formie form access permission for cp field helper actions', function (): void {
    $originalUser = Craft::$app->getUser()->getIdentity();
    Craft::$app->getUser()->setIdentity(null);

    try {
        WebRequestTestHelper::withWebRequestContext(function (): void {
            $controller = new FieldsController('formie-fields-security', Craft::$app);

            expect(fn() => $controller->runAction('get-field-type-config'))
                ->toThrow(ForbiddenHttpException::class);
        }, [
            'method' => 'POST',
            'headers' => [
                'Accept' => 'application/json',
            ],
            'bodyParams' => [
                'type' => 'verbb\\formie\\fields\\SingleLineText',
            ],
        ]);
    } finally {
        Craft::$app->getUser()->setIdentity($originalUser);
    }
})->group('security');
