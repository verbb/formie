<?php

declare(strict_types=1);

use craft\errors\GqlException;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper as CraftUrlHelper;
use craft\models\GqlSchema;
use Tests\Support\UploadTestHelper;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\client\FormsController;
use verbb\formie\Formie;
use verbb\formie\helpers\UrlHelper;

// Issue 3157: headless mode put the client bootstrap's endpoints on the control panel route, where guests can't upload.
it('gives client-rendered forms site action endpoints in headless mode', function (string $transport): void {
    $location = 'volume:' . UploadTestHelper::ensureUploadVolume()->uid;
    $form = formie()->form()
        ->fileUploadField('simple', ['displayType' => 'fileInput', 'restrictFiles' => false, 'uploadLocationSource' => $location])
        ->fileUploadField('manager', ['displayType' => 'uploadManager', 'restrictFiles' => false, 'uploadLocationSource' => $location])
        ->create();

    try {
        WebRequestTestHelper::withWebRequestContext(function ($request) use ($transport, $form): void {
            // The simulated web app has its own config.
            $generalConfig = Craft::$app->getConfig()->getGeneral();
            expect(Craft::$app->getUser()->getIsGuest())->toBeTrue()
                ->and($request->getIsCpRequest())->toBeFalse();

            $siteUrls = [];

            foreach (['client/submissions/submit', 'file-upload/upload', 'file-upload/delete', 'file-upload/hydrate'] as $action) {
                $siteUrls[$action] = CraftUrlHelper::actionUrl('formie/' . $action);
            }

            $generalConfig->headlessMode = true;
            $cpUploadUrl = CraftUrlHelper::actionUrl('formie/file-upload/upload');
            expect($cpUploadUrl)->not->toBe($siteUrls['file-upload/upload']);

            $gql = Craft::$app->getGql();
            $previousSchema = null;

            try {
                $previousSchema = $gql->getActiveSchema();
            } catch (GqlException) {
            }

            $schema = new GqlSchema([
                'name' => 'Headless Client Endpoints',
                'uid' => StringHelper::UUID(),
                'scope' => $transport === 'graphql' ? ['formieForms.' . $form->uid . ':read'] : [],
            ]);
            $gql->setActiveSchema($schema);

            try {
                if ($transport === 'graphql') {
                    $response = $gql->executeQuery($schema, 'query($handle: String!) {
                        formieClientForm(handle: $handle) { definition }
                    }', ['handle' => $form->handle]);
                    expect($response)->not->toHaveKey('errors');
                    $definition = $response['data']['formieClientForm']['definition'];
                } else {
                    $request->setBodyParams(['handle' => $form->handle]);
                    $controller = new FormsController('client/forms', Formie::$plugin);
                    $response = $controller->runAction('load');
                    expect($response->getStatusCode())->toBe(200);
                    $definition = $response->data['definition'];
                }
            } finally {
                $gql->setActiveSchema($previousSchema);
                $gql->flushCaches();
            }

            $html = $form->getFieldByHandle('manager')->getClientRenderedInput()['html'];
            $generalConfig->headlessMode = false;

            expect($definition['submission']['endpoint'])->toBe($siteUrls['client/submissions/submit'])
                ->and($definition['submission']['uploadEndpoint'])->toBe($siteUrls['file-upload/upload']);

            $moduleConfig = null;

            foreach ($definition['modules']['entries'] as $module) {
                if (($module['moduleId'] ?? null) === 'formie:upload-manager') {
                    $moduleConfig = $module['config'];
                }
            }

            expect($moduleConfig)->not->toBeNull()
                ->and($moduleConfig['uploadEndpoint'])->toBe($siteUrls['file-upload/upload'])
                ->and($moduleConfig['deleteEndpoint'])->toBe($siteUrls['file-upload/delete'])
                ->and($moduleConfig['hydrateEndpoint'])->toBe($siteUrls['file-upload/hydrate']);

            foreach (['upload', 'delete', 'hydrate'] as $action) {
                expect($html)->toContain('data-formie-file-upload-' . $action . '-endpoint="' . htmlspecialchars($siteUrls['file-upload/' . $action]) . '"');
            }

            expect(json_encode($definition, JSON_UNESCAPED_SLASHES))->not->toContain($cpUploadUrl);
        }, ['method' => 'POST', 'requestUri' => '/actions/formie/client/forms/load']);
    } finally {
        Craft::$app->getElements()->deleteElement($form, true);
    }
})->with(['graphql', 'rest']);

it('restores headless mode after building a front-end action URL', function (bool $headlessMode): void {
    $generalConfig = Craft::$app->getConfig()->getGeneral();
    $original = $generalConfig->headlessMode;

    try {
        $generalConfig->headlessMode = $headlessMode;
        UrlHelper::frontEndActionUrl('formie/file-upload/upload');

        expect($generalConfig->headlessMode)->toBe($headlessMode);
    } finally {
        $generalConfig->headlessMode = $original;
    }
})->with([true, false]);
