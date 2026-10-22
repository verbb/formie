<?php

use craft\elements\User;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\FormsController;
use verbb\formie\Formie;

it('persists Name label translations without changing the source or child layout', function (bool $multiple, int $siteIndex): void {
    if (!Craft::$app->getIsMultiSite()) {
        $this->markTestSkipped('Requires the default multisite runtime.');
    }

    $form = formie()->form()->nameField('person', ['label' => 'Name', 'useMultipleFields' => $multiple])->create();
    $formId = (int)$form->id;
    $sourceSiteId = Formie::$plugin->getFormSiteOverrides()->getSourceSiteId($form);
    $siteId = (int)Craft::$app->getSites()->getAllSiteIds()[$siteIndex];
    $fieldId = $form->getFieldByHandle('person')->definitionId;
    $nestedLayoutId = $form->getFieldByHandle('person')->nestedLayoutId;

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($formId, $siteId, $fieldId): void {
        Craft::$app->getUser()->setIdentity(User::find()->admin(true)->one());
        $request->setIsCpRequest(true);

        foreach (['Nom', 'Votre nom'] as $label) {
            $request->setBodyParams([
                $request->csrfParam => $request->getCsrfToken(),
                'id' => $formId,
                'siteId' => $siteId,
                'translations' => ['fieldOverrides' => [$fieldId => ['label' => $label]]],
            ]);
            $response = (new FormsController('forms', Formie::$plugin))->runAction('save');
            expect($response?->data)->not->toHaveKey('errors')
                ->and((int)($response?->data['data']['id'] ?? 0))->toBe($formId);

            Formie::$plugin->getForms()->invalidateFormCaches();
            $translated = Formie::$plugin->getForms()->getFormById($formId, $siteId);
            expect($translated->getFieldByHandle('person')->label)->toBe($label);
        }
    }, ['method' => 'POST', 'headers' => ['Accept' => 'application/json']]);

    Formie::$plugin->getForms()->invalidateFormCaches();
    $source = Formie::$plugin->getForms()->getFormById($formId, $sourceSiteId);
    expect($source->getFieldByHandle('person')->label)->toBe('Name')
        ->and($source->getFieldByHandle('person')->useMultipleFields)->toBe($multiple)
        ->and($source->getFieldByHandle('person')->nestedLayoutId)->toBe($nestedLayoutId)
        ->and(Formie::$plugin->getFormSiteOverrides()->getSourceSiteId($source))->toBe($sourceSiteId);
})->with([false, true])->with([1, 2]);
