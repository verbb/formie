<?php

declare(strict_types=1);

use craft\helpers\FileHelper;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\Formie;
use verbb\formie\models\FormTemplate;

beforeEach(function (): void {
    $this->overridePath = 'formie-render-options-' . bin2hex(random_bytes(6));
    $this->overrideDirectory = Craft::$app->getPath()->getSiteTemplatesPath() . '/' . $this->overridePath;
    FileHelper::createDirectory($this->overrideDirectory);
    file_put_contents($this->overrideDirectory . '/form.html', 'FORM[{{ renderOptions.templateVars.variant }}]{% for page in form.getPages() %}{{ craft.formie.renderPage(form, page) }}{% endfor %}');
    file_put_contents($this->overrideDirectory . '/page.html', 'PAGE[{{ renderOptions.templateVars.variant }}]{% for row in page.getRows() %}{% for field in row.getFields() %}{{ craft.formie.renderField(form, field) }}{% endfor %}{% endfor %}');
    file_put_contents($this->overrideDirectory . '/field.html', 'FIELD[{{ field.handle }}:{{ renderOptions.templateVars.variant }}]');
});

afterEach(function (): void {
    FileHelper::removeDirectory($this->overrideDirectory);
});

it('passes render options to page and field template overrides', function (string $method, array $expected): void {
    $form = formie()->form()->singleLineTextField('name')->settings(['disableCaptchas' => true])->create();
    $form->setTemplate(new FormTemplate(['name' => 'Overrides', 'handle' => 'overrides', 'template' => $this->overridePath, 'useCustomTemplates' => true]));

    WebRequestTestHelper::withWebRequestContext(function () use ($form, $method, $expected): void {
        $rendering = Formie::$plugin->getRendering();
        $view = Craft::$app->getView();
        $originalMode = $view->getTemplateMode();
        $view->setTemplateMode($view::TEMPLATE_MODE_SITE);
        $twig = $view->getTwig();
        $strict = $twig->isStrictVariables();
        $twig->enableStrictVariables();
        $options = ['templateVars' => ['variant' => 'compact']];

        try {
            $html = (string)match ($method) {
                'form' => $rendering->renderForm($form, $options),
                'page' => $rendering->renderPage($form, null, $options),
                'field' => $rendering->renderField($form, 'name', $options),
            };

            foreach ($expected as $marker) {
                expect($html)->toContain($marker);
            }

            expect($rendering->getActiveRenderFrame())->toBeNull();
        } finally {
            $strict ? $twig->enableStrictVariables() : $twig->disableStrictVariables();
            $view->setTemplateMode($originalMode);
        }
    });
})->with([
    'form render' => ['form', ['FORM[compact]', 'PAGE[compact]', 'FIELD[name:compact]']],
    'standalone page' => ['page', ['PAGE[compact]', 'FIELD[name:compact]']],
    'standalone field' => ['field', ['FIELD[name:compact]']],
]);
