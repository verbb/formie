<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\client\SubmissionsController;
use verbb\formie\Formie;
use verbb\formie\models\Settings;
use yii\web\BadRequestHttpException;

it('requires csrf validation for anonymous client submit requests when enabled', function (): void {
    $form = formie()
        ->form(['title' => 'Client CSRF Security'])
        ->singleLineTextField('fullName')
        ->create();

    /** @var Settings $settings */
    $settings = Formie::$plugin->getSettings();
    $original = $settings->enableCsrfValidationForGuests;

    try {
        $settings->enableCsrfValidationForGuests = true;

        WebRequestTestHelper::withWebRequestContext(function () use ($form): void {
            $controller = new SubmissionsController('formie-client-submissions-csrf', Craft::$app);

            expect(fn() => $controller->runAction('submit', [
                'handle' => (string)$form->handle,
                'action' => 'submit',
                'values' => [
                    'fullName' => 'Security Tester',
                ],
                'session' => [],
            ]))->toThrow(BadRequestHttpException::class);
        }, [
            'method' => 'POST',
            'bodyParams' => [
                'handle' => (string)$form->handle,
                'action' => 'submit',
                'values' => [
                    'fullName' => 'Security Tester',
                ],
                'session' => [],
            ],
        ]);
    } finally {
        $settings->enableCsrfValidationForGuests = $original;
    }
})->group('security');
