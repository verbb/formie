<?php

declare(strict_types=1);

use craft\web\View;
use verbb\formie\Formie;

function renderFormHtmlForCsrfTests($form, array $renderOptions = []): string
{
    $view = Craft::$app->getView();
    $oldTemplateMode = $view->getTemplateMode();
    $view->setTemplateMode(View::TEMPLATE_MODE_SITE);

    try {
        return (string)Formie::$plugin->getRendering()->renderForm($form, $renderOptions);
    } finally {
        $view->setTemplateMode($oldTemplateMode);
    }
}

it('renders the default csrf input when csrfInput is not provided', function (): void {
    $form = formie()
        ->form(['title' => 'CSRF Default'])
        ->singleLineTextField('message')
        ->create();

    $csrfParam = Craft::$app->getConfig()->getGeneral()->csrfTokenName;
    $html = renderFormHtmlForCsrfTests($form);

    expect($html)->toContain('name="' . $csrfParam . '"')
        ->toContain('data-formie-csrf')
        ->toContain('data-formie-csrf-param="' . $csrfParam . '"');
})->group('security');

it('omits the csrf input when csrfInput is false', function (): void {
    $form = formie()
        ->form(['title' => 'CSRF Omitted'])
        ->singleLineTextField('message')
        ->create();

    $csrfParam = Craft::$app->getConfig()->getGeneral()->csrfTokenName;
    $html = renderFormHtmlForCsrfTests($form, ['csrfInput' => false]);

    expect($html)->not->toContain('name="' . $csrfParam . '"')
        ->toContain('name="requestToken"');
})->group('security');

it('passes csrfInput options through to Craft csrfInput', function (): void {
    $form = formie()
        ->form(['title' => 'CSRF Async'])
        ->singleLineTextField('message')
        ->create();

    $html = renderFormHtmlForCsrfTests($form, [
        'csrfInput' => ['async' => true],
    ]);

    expect($html)->toContain('data-csrf-token-value');
})->group('security');
