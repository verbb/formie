<?php

use craft\helpers\StringHelper;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\elements\Form;
use verbb\formie\events\ModifyFormRenderOptionsEvent;
use verbb\formie\Formie;
use verbb\formie\models\FormTemplate;
use verbb\formie\services\Rendering;
use yii\base\Event;

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

it('leaves assets set to manual for the template to output', function(string $helper, string $options, bool $css, bool $js): void {
    $form = formie()->form()
        ->singleLineTextField('message')
        ->settings(['disableCaptchas' => true])
        ->create();

    $page = renderDeprecatedAssetPage($form, "{% do craft.formie.{$helper}(form, {$options}) %}", '');

    expect(str_contains($page, 'formie-base.css'))->toBe($css)
        ->and(str_contains($page, 'data-formie-startup'))->toBe($js);
})->with(['registerAssets', 'registerFormAssets'])->with([
    'both manual' => ['{ outputCssLocation: "manual", outputJsLocation: "manual" }', false, false],
    'styles manual' => ['{ outputCssLocation: "manual" }', false, true],
    'scripts manual' => ['{ outputJsLocation: "manual" }', true, false],
]);

it('leaves assets out when the form template sets them to manual', function(): void {
    $template = new FormTemplate([
        'name' => 'Manual assets',
        'handle' => 'manualAssets' . StringHelper::randomString(6),
        'outputCssLocation' => FormTemplate::MANUAL,
        'outputJsLocation' => FormTemplate::MANUAL,
    ]);
    expect(Formie::$plugin->getFormTemplates()->saveTemplate($template))->toBeTrue();

    try {
        $form = formie()->form(['templateId' => $template->id])
            ->singleLineTextField('message')
            ->settings(['disableCaptchas' => true])
            ->create();

        expect($form->getFrontendTemplateLocation('outputJsLocation'))->toBe(FormTemplate::MANUAL);

        $page = renderDeprecatedAssetPage($form, '{% do craft.formie.registerAssets(form) %}', '');

        expect($page)->not->toContain('formie-base.css')
            ->and($page)->not->toContain('data-formie-startup');
    } finally {
        Formie::$plugin->getFormTemplates()->deleteTemplateById($template->id);
    }
});

it('applies modifyFormRenderOptions to the deprecated register helpers', function(string $template): void {
    $form = formie()->form()
        ->singleLineTextField('message')
        ->settings(['disableCaptchas' => true])
        ->create();

    $handler = static function(ModifyFormRenderOptionsEvent $event): void {
        $event->renderOptions['outputCss'] = false;
        $event->renderOptions['outputJs'] = false;
    };
    Event::on(Rendering::class, Rendering::EVENT_MODIFY_FORM_RENDER_OPTIONS, $handler);

    try {
        $page = renderDeprecatedAssetPage($form, $template, '');
    } finally {
        Event::off(Rendering::class, Rendering::EVENT_MODIFY_FORM_RENDER_OPTIONS, $handler);
    }

    expect($page)->not->toContain('data-formie-startup')
        ->and($page)->not->toContain('formie-base.css');
})->with([
    'craft.formie.registerAssets' => ['{% do craft.formie.registerAssets(form) %}'],
    'craft.formie.registerFormAssets' => ['{% do craft.formie.registerFormAssets(form) %}'],
    'Rendering::registerAssets' => ['{% do rendering.registerAssets(form) %}'],
    'Rendering::registerFormAssets' => ['{% do rendering.registerFormAssets(form) %}'],
]);
