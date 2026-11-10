<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use verbb\formie\fields\SingleLineText;
use verbb\formie\Formie;

it('inherits the root namespace without flattening nested fields or leaking into later renders', function (string $method, string $namespace): void {
    $form = formie()->form()
        ->singleLineTextField('loginName')
        ->groupField('profile', ['rows' => [['fields' => [[
            'type' => SingleLineText::class,
            'handle' => 'firstName',
            'label' => 'First Name',
        ]]]]])
        ->repeaterField('people', ['minRows' => 1, 'rows' => [['fields' => [[
            'type' => SingleLineText::class,
            'handle' => 'name',
            'label' => 'Name',
        ]]]]])
        ->settings(['disableCaptchas' => true])
        ->create();

    WebRequestTestHelper::withWebRequestContext(function () use ($form, $method, $namespace): void {
        $rendering = Formie::$plugin->getRendering();
        $rendering->populateFormValues($form, ['people' => [['name' => 'Example']]]);
        $options = ['fieldNamespace' => $namespace];
        $render = static fn(array $options): string => (string)($method === 'page'
            ? $rendering->renderPage($form, null, $options)
            : $rendering->renderForm($form, $options));
        $prefix = $namespace === '' ? '' : $namespace . '[';
        $suffix = $namespace === '' ? '' : ']';
        $html = $render($options);

        expect($html)->toContain('name="' . $prefix . 'loginName' . $suffix . '"')
            ->toContain('name="' . $prefix . 'profile' . $suffix . '[firstName]"')
            ->toContain('name="' . $prefix . 'people' . $suffix . '[0][name]"');
        expect($render([]))->toContain('name="fields[loginName]"')
            ->toContain('name="fields[profile][firstName]"')
            ->toContain('name="fields[people][0][name]"');
        expect($rendering->getActiveRenderFrame())->toBeNull();
    });
})->with(['page', 'form'])->with(['', 'account']);

it('lets explicit field namespaces override the enclosing render and restores field context', function (): void {
    $form = formie()->form()->singleLineTextField('loginName')->create();

    WebRequestTestHelper::withWebRequestContext(function () use ($form): void {
        $rendering = Formie::$plugin->getRendering();
        $field = $form->getFieldByHandle('loginName');
        $rendering->pushRenderFrame($form, ['fieldNamespace' => 'account']);

        try {
            expect((string)$rendering->renderField($form, $field, ['fieldNamespace' => '']))->toContain('name="loginName"');
            expect((string)$rendering->renderField($form, $field))->toContain('name="account[loginName]"');
            expect($field->getNamespace())->toBe('fields');
        } finally {
            $rendering->popRenderFrame();
        }

        expect((string)$rendering->renderField($form, $field))->toContain('name="fields[loginName]"');
    });
});

it('renders the documented Craft action forms with strict Twig and unprefixed input names', function (string $path): void {
    $form = formie()->form()
        ->singleLineTextField('loginName')
        ->singleLineTextField('username')
        ->passwordField('password')
        ->singleLineTextField('firstName')
        ->singleLineTextField('lastName')
        ->emailField('email')
        ->settings(['disableCaptchas' => true])
        ->create();

    WebRequestTestHelper::withWebRequestContext(function () use ($form, $path): void {
        $view = Craft::$app->getView();
        $view->setTemplateMode($view::TEMPLATE_MODE_SITE);
        $twig = $view->getTwig();
        $strict = $twig->isStrictVariables();
        $twig->enableStrictVariables();
        $docs = file_get_contents(dirname(__DIR__, 2) . '/docs/' . $path);
        preg_match_all('/```twig\n(.*?)```/s', $docs, $matches);
        $examples = array_filter($matches[1], static fn(string $example): bool => str_contains($example, '<form'));
        expect($examples)->not->toBeEmpty();

        try {
            foreach ($examples as $template) {
                $template = preg_replace('/{% set form = craft.formie.forms.handle\(.*?%}\n/', '', $template);
                $html = $view->renderString($template, [
                    'form' => $form,
                    'user' => ['id' => 1, 'username' => 'example', 'email' => 'example@example.test', 'firstName' => 'Example', 'lastName' => 'User'],
                ]);
                $document = new DOMDocument();
                @$document->loadHTML($html);
                $xpath = new DOMXPath($document);

                expect($xpath->query('//form')->length)->toBe(1)
                    ->and($xpath->query('//form[@data-formie or @data-formie-form or @data-fui-form]')->length)->toBe(0)
                    ->and($xpath->query('//form[@data-formie-craft-form]')->length)->toBe(1)
                    ->and($xpath->query('//form[@novalidate]')->length)->toBe(0)
                    ->and($xpath->query('//input[@name="loginName"]')->length)->toBe(1)
                    ->and($xpath->query('//input[@name="password" and @type="password"]')->length)->toBe(1)
                    ->and($xpath->query('//input[@name="email"]')->length)->toBe(1)
                    ->and($xpath->query('//input[@name="' . Craft::$app->getRequest()->csrfParam . '"]')->length)->toBe(1);
                expect($xpath->query('//input[@name="action"]')->item(0)->getAttribute('value'))->toBeIn(['users/login', 'users/save-user']);
                $body = [];

                foreach ($xpath->query('//form//input[@name]') as $input) {
                    $body[$input->getAttribute('name')] = $input->getAttribute('value');
                }

                // Match the required body reads made by Craft's login action.
                Craft::$app->getRequest()->setBodyParams($body);
                expect(Craft::$app->getRequest()->getRequiredBodyParam('loginName'))->toBeString()
                    ->and(Craft::$app->getRequest()->getRequiredBodyParam('password'))->toBe('');
            }
        } finally {
            if (!$strict) {
                $twig->disableStrictVariables();
            }
        }
    });
})->with([
    'templates/craft-forms.md',
    'guides/templating-theming/create-a-content-managed-user-registration-form-with-formie.md',
]);
