<?php

use Tests\Support\WebRequestTestHelper;
use verbb\formie\elements\Form;
use verbb\formie\Formie;
use verbb\formie\services\Rendering;

// Render a whole site page as Craft does, so assets registered with the view are part of the output.
// String templates don't get Craft's automatic head and body tags, so the page has them itself.
function renderDeprecatedAssetPage(Form $form, string $head, string $body): string
{
    $previous = Formie::$plugin->getRendering();
    $rendering = new Rendering();
    Formie::$plugin->set('rendering', $rendering);

    try {
        return WebRequestTestHelper::withWebRequestContext(function() use ($form, $head, $body, $rendering): string {
            $view = Craft::$app->getView();
            $view->setTemplateMode($view::TEMPLATE_MODE_SITE);

            $level = ob_get_level();
            ob_start();
            ob_implicit_flush(false);

            try {
                $view->beginPage();
                echo $view->renderString("<!DOCTYPE html><html><head><title>Assets</title>{$head}{{ head() }}</head><body>{{ beginBody() }}{$body}{{ endBody() }}</body></html>", [
                    'form' => $form,
                    'rendering' => $rendering,
                ]);
                $view->endPage();

                return ob_get_clean();
            } finally {
                while (ob_get_level() > $level) {
                    ob_end_clean();
                }
            }
        }, ['requestUri' => '/']);
    } finally {
        Formie::$plugin->set('rendering', $previous);
    }
}

it('outputs Formie JavaScript once when a page registers form assets', function(string $head, string $body, int $forms): void {
    $form = formie()->form()
        ->singleLineTextField('message')
        ->settings(['disableCaptchas' => true])
        ->create();

    $page = renderDeprecatedAssetPage($form, $head, $body);

    expect(substr_count($page, 'data-formie-startup'))->toBe(1)
        ->and(substr_count($page, 'data-formie-translations'))->toBe(1)
        ->and(substr_count($page, '<form '))->toBe($forms);
})->with([
    'renderForm only' => ['', '{{ craft.formie.renderForm(form) }}', 1],
    'formAssets output' => ['{{ craft.formie.formAssets(form) }}', '{{ craft.formie.renderForm(form) }}', 1],
    'craft.formie.registerAssets' => ['{% do craft.formie.registerAssets(form) %}', '{{ craft.formie.renderForm(form) }}', 1],
    'craft.formie.registerFormAssets' => ['{% do craft.formie.registerFormAssets(form) %}', '{{ craft.formie.renderForm(form) }}', 1],
    'Rendering::registerAssets' => ['{% do rendering.registerAssets(form) %}', '{{ craft.formie.renderForm(form) }}', 1],
    'Rendering::registerFormAssets' => ['{% do rendering.registerFormAssets(form) %}', '{{ craft.formie.renderForm(form) }}', 1],
    // Sprig and Ajax-loaded forms register assets on the page that loads them, with no form render.
    'registerAssets without a form' => ['{% do craft.formie.registerAssets(form) %}', '', 0],
]);

it('registers form styles in the page head', function(string $helper): void {
    $form = formie()->form()
        ->singleLineTextField('message')
        ->settings(['disableCaptchas' => true])
        ->create();

    $page = renderDeprecatedAssetPage($form, "{% do craft.formie.{$helper}(form) %}", '');
    $head = explode('</head>', $page)[0];

    expect($head)->toContain('formie-base.css')
        ->and(substr_count($page, 'formie-base.css'))->toBe(1);
})->with(['registerAssets', 'registerFormAssets']);

it('leaves Formie JavaScript out when the form turns it off', function(): void {
    $form = formie()->form()
        ->singleLineTextField('message')
        ->settings(['disableCaptchas' => true])
        ->create();

    $page = renderDeprecatedAssetPage($form, '{% do craft.formie.registerAssets(form, { outputJs: false }) %}', '');

    expect($page)->not->toContain('data-formie-startup');
});
