<?php

declare(strict_types=1);

use craft\web\View;
use verbb\formie\elements\Form;
use verbb\formie\positions\AboveInput;
use verbb\formie\positions\BelowInput;

it('outputs field error position on the field layout', function (): void {
    $form = formie()
        ->form(['title' => 'Field Error Position Layout'])
        ->singleLineTextField('fullName', [
            'errorMessagePosition' => AboveInput::class,
        ])
        ->create();

    $field = $form->getFieldByHandle('fullName');
    $view = Craft::$app->getView();
    $oldTemplateMode = $view->getTemplateMode();
    $view->setTemplateMode(View::TEMPLATE_MODE_CP);

    try {
        $html = $view->renderTemplate('formie/_special/form-template/field', [
            'form' => $form,
            'field' => $field,
            'value' => '',
            'element' => null,
        ]);
    } finally {
        $view->setTemplateMode($oldTemplateMode);
    }

    expect($html)->toContain('data-formie-error-position="above"');
});

it('defaults field error position to below input', function (): void {
    $form = formie()
        ->form(['title' => 'Field Error Position Default'])
        ->singleLineTextField('fullName')
        ->create();

    $field = $form->getFieldByHandle('fullName');
    $view = Craft::$app->getView();
    $oldTemplateMode = $view->getTemplateMode();
    $view->setTemplateMode(View::TEMPLATE_MODE_CP);

    try {
        $html = $view->renderTemplate('formie/_special/form-template/field', [
            'form' => $form,
            'field' => $field,
            'value' => '',
            'element' => null,
        ]);
    } finally {
        $view->setTemplateMode($oldTemplateMode);
    }

    expect($html)->toContain('data-formie-error-position="below"');
});

it('persists field error message position on fields', function (): void {
    $form = formie()
        ->form(['title' => 'Field Error Position Persist'])
        ->singleLineTextField('fullName', [
            'errorMessagePosition' => BelowInput::class,
        ])
        ->create();

    $reloaded = Form::find()->id($form->id)->one();
    $field = $reloaded?->getFieldByHandle('fullName');

    expect($field?->errorMessagePosition)->toBe(BelowInput::class);
});
