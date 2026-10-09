<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use verbb\formie\Formie;

it('renders the manual initialization opt-out without leaking it into another form', function (): void {
    $form = formie()->form()->singleLineTextField('message')->create();
    WebRequestTestHelper::withWebRequestContext(function () use ($form): void {
        $view = Craft::$app->getView();
        $view->setTemplateMode($view::TEMPLATE_MODE_SITE);
        $rendering = Formie::$plugin->getRendering();
        $manual = (string)$rendering->renderForm($form, ['initJs' => false]);
        $automatic = (string)$rendering->renderForm($form);
        expect($manual)->toContain('data-formie-init="false"')
            ->and($automatic)->not->toContain('data-formie-init="false"');
    });
});
