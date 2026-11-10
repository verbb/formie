<?php

use Tests\Support\WebRequestTestHelper;
use verbb\formie\Formie;

it('provides only Craft form validation attributes and preserves render context', function (): void {
    $form = formie()->form()->singleLineTextField('loginName')->passwordField('password')
        ->settings(['validationOnFocus' => true, 'validationOnSubmit' => false])->create();

    WebRequestTestHelper::withWebRequestContext(function () use ($form): void {
        $rendering = Formie::$plugin->getRendering();
        $attributes = $rendering->craftFormAttributes($form, [
            'initJs' => false,
            'themeConfig' => [
                'fieldError' => ['attributes' => ['class' => ['custom-login-error']]],
                'form' => ['attributes' => ['class' => ['custom-login']], 'cssVars' => ['--formie-color-danger' => '#123456']],
            ],
        ]);
        $classes = json_decode($attributes['data-formie-theme-classes'], true, flags: JSON_THROW_ON_ERROR);
        expect($attributes['id'])->toBe($form->getRenderId())
            ->and($attributes['class'])->toContain('formie-form', 'custom-login')
            ->and($attributes['style']['--formie-color-danger'])->toBe('#123456')
            ->and($attributes['data-formie-craft-form'])->toBeTrue()
            ->and($attributes['data-formie-init'])->toBe('false')
            ->and($attributes['data-formie-validation-on-focus'])->toBe('true')
            ->and($attributes['data-formie-validation-on-submit'])->toBe('false')
            ->and($classes['fieldError'])->toContain('custom-login-error')
            ->and($attributes)->not->toHaveKeys(['data-formie', 'data-formie-form', 'data-formie-modules', 'data-formie-submit-endpoint', 'novalidate', 'action'])
            ->and($rendering->getActiveRenderFrame())->toBeNull();

        $ordinary = $rendering->craftFormAttributes($form->handle);
        expect($ordinary['id'])->not->toBe($attributes['id'])
            ->and(json_decode($ordinary['data-formie-theme-classes'], true)['fieldError'])->not->toContain('custom-login-error');
        expect($rendering->craftFormAttributes($form, ['theme' => 'none'])['class'] ?? [])->not->toContain('formie-form');
        expect((string)$rendering->renderPage($form, null, ['fieldNamespace' => '']))->toContain('name="loginName"', 'name="password"');
    });
});

it('returns no Craft form attributes for a missing form', function (): void {
    expect(Formie::$plugin->getRendering()->craftFormAttributes(null))->toBe([]);
});
