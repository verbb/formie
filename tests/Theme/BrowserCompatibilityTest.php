<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use verbb\formie\Formie;

it('passes global compatibility mode to rendered forms without retaining a previous value', function (): void {
    $form = formie()->form()->singleLineTextField('message')->create();

    WebRequestTestHelper::withWebRequestContext(function () use ($form): void {
        $view = Craft::$app->getView();
        $view->setTemplateMode($view::TEMPLATE_MODE_SITE);
        $settings = Formie::$plugin->getSettings();
        $rendering = Formie::$plugin->getRendering();

        foreach ([true, false, true] as $enabled) {
            $settings->compatibilityMode = $enabled;
            $html = (string)$rendering->renderForm($form);

            expect($html)->toContain('data-formie-compatibility="' . ($enabled ? 'true' : 'false') . '"');
        }
    });
});
