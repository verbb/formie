<?php

declare(strict_types=1);

use Craft;
use ReflectionMethod;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\FieldsController;
use verbb\formie\Formie;
use verbb\formie\theme\context\RenderContext;

use craft\helpers\Json;

it('embeds render themeConfig on the form element when a render frame is active', function (): void {
    $form = formie()
        ->form(['title' => 'Summary Theme Embed'])
        ->singleLineTextField('fullName')
        ->create();

    Formie::$plugin->getRendering()->pushRenderFrame($form, [
        'themeConfig' => [
            'fieldSummaryLabel' => [
                'attributes' => [
                    'class' => ['embedded-summary-label'],
                ],
            ],
        ],
    ]);

    try {
        $tag = Formie::$plugin->getFormSlotRegistry()->resolve('form', RenderContext::from([
            'form' => $form,
        ]));

        $encodedThemeConfig = $tag?->coreAttributes['data']['formie-theme-config'] ?? null;

        expect($encodedThemeConfig)
            ->toBeString()
            ->and($encodedThemeConfig)->toContain('fieldSummaryLabel');
    } finally {
        Formie::$plugin->getRendering()->popRenderFrame();
    }
});

it('applies posted themeConfig to the form during summary ajax refresh', function (): void {
    $form = formie()
        ->form(['title' => 'Summary Theme Ajax'])
        ->singleLineTextField('fullName')
        ->summaryField('summary')
        ->create();

    $summaryField = $form->getFieldByHandle('summary');
    $themeConfig = [
        'fieldSummaryLabel' => [
            'attributes' => [
                'class' => ['custom-ajax-summary-label'],
            ],
        ],
    ];

    WebRequestTestHelper::withWebRequestContext(function () use ($form, $summaryField, $themeConfig): void {
        Craft::$app->getRequest()->setBodyParams([
            'themeConfig' => Json::encode($themeConfig),
        ]);

        $controller = new FieldsController('formie-fields-summary-theme', Craft::$app);
        $method = new ReflectionMethod($controller, '_applySummaryRenderContext');
        $method->setAccessible(true);
        $method->invoke($controller, $form);

        $context = RenderContext::from([
            'form' => $form,
            'field' => $summaryField,
        ]);

        $tag = $summaryField->renderSlotTag('fieldSummaryLabel', $context);

        expect($tag?->attributes['class'] ?? [])->toContain('custom-ajax-summary-label');
    }, [
        'method' => 'POST',
    ]);
});

it('applies posted frontendTheme to the form during summary ajax refresh', function (): void {
    $form = formie()
        ->form(['title' => 'Summary Frontend Theme Ajax'])
        ->singleLineTextField('fullName')
        ->summaryField('summary')
        ->create();

    WebRequestTestHelper::withWebRequestContext(function () use ($form): void {
        Craft::$app->getRequest()->setBodyParams([
            'frontendTheme' => 'tailwind',
        ]);

        $controller = new FieldsController('formie-fields-summary-theme', Craft::$app);
        $method = new ReflectionMethod($controller, '_applySummaryRenderContext');
        $method->setAccessible(true);
        $method->invoke($controller, $form);

        expect($form->getFrontendTheme())->toBe('tailwind');
    }, [
        'method' => 'POST',
    ]);
});
