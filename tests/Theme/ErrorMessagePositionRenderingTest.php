<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use verbb\formie\elements\Form;
use verbb\formie\Formie;

// The browser only treats a present, empty attribute as "None". A missing attribute means the
// default position, so this checks real rendered output after a save and reload.
it('renders the saved error message position for the browser', function (string $position): void {
    $saved = formie()->form(['title' => 'Error message position'])
        ->settings(['errorMessagePosition' => $position])
        ->singleLineTextField('name')
        ->create();

    $form = Form::find()->id($saved->id)->one();

    expect($form->settings->errorMessagePosition)->toBe($position);

    $html = WebRequestTestHelper::withWebRequestContext(function () use ($form): string {
        return (string)Formie::$plugin->getRendering()->renderForm($form);
    });

    $document = new DOMDocument();
    @$document->loadHTML('<?xml encoding="utf-8" ?>' . $html);
    $node = $document->getElementsByTagName('form')->item(0);

    expect($node->hasAttribute('data-formie-error-message-position'))->toBeTrue()
        ->and($node->getAttribute('data-formie-error-message-position'))->toBe($position);
})->with([
    'none' => '',
    'top' => 'top-form',
    'bottom' => 'bottom-form',
]);
