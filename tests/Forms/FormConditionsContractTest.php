<?php

declare(strict_types=1);

use verbb\formie\elements\Form;

it('persists page and button condition flags', function (): void {
    $form = formie()
        ->form(['title' => 'Condition Flags'])
        ->singleLineTextField('fullName')
        ->create();

    $pages = $form->getPages();
    $page = $pages[0];
    $pageSettings = $page->getPageSettings();
    $pageSettings->enablePageConditions = true;
    $pageSettings->enableNextButtonConditions = true;
    $page->setPageSettings($pageSettings->toArray());

    $layout = $form->getFormLayout();
    $layout->setPages([$page]);
    $form->setFormLayout($layout);

    $saved = Craft::$app->elements->saveElement($form);
    $reloaded = Form::find()->id($form->id)->one();

    expect($saved)->toBeTrue()
        ->and($reloaded?->hasPageConditions())->toBeTrue()
        ->and($reloaded?->hasButtonConditions())->toBeTrue()
        ->and($reloaded?->hasConditions())->toBeTrue();
});

it('persists field condition flags without field-specific logic tests', function (): void {
    $form = formie()
        ->form(['title' => 'Field Condition Flag'])
        ->singleLineTextField('fullName', ['enableConditions' => true])
        ->create();

    expect($form->hasFieldConditions())->toBeTrue()
        ->and($form->hasConditions())->toBeTrue();
});
