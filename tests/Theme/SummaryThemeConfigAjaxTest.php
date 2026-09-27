<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\FieldsController;
use verbb\formie\Formie;
use verbb\formie\helpers\FieldAccess;
use verbb\formie\theme\context\RenderContext;

use craft\helpers\Json;

it('embeds browser classes but never executable theme config on the form element', function (): void {
    $form = formie()->form(['title' => 'Summary Theme Embed'])->singleLineTextField('fullName')->create();
    Formie::$plugin->getRendering()->pushRenderFrame($form, [
        'themeConfig' => ['fieldSummaryLabel' => ['class' => 'embedded-summary-label']],
    ]);

    try {
        $tag = WebRequestTestHelper::withWebRequestContext(fn() => Formie::$plugin->getFormSlotRegistry()->resolve('form', RenderContext::from([
            'form' => $form,
        ])));

        expect($tag?->coreAttributes['data']['formie-theme-config'] ?? null)->toBeNull()
            ->and($tag?->coreAttributes['data']['formie-frontend-theme'] ?? null)->toBeNull()
            ->and($tag?->coreAttributes['data']['formie-theme-classes'] ?? null)->toBeString();
    } finally {
        Formie::$plugin->getRendering()->popRenderFrame();
    }
});

it('binds Summary fragments to the issued immutable theme and ignores posted executable config', function (): void {
    $form = formie()
        ->form(['title' => 'Summary Theme Ajax'])
        ->singleLineTextField('fullName')
        ->summaryField('summary')
        ->create();
    $submission = formie()->submission($form)->with(['fullName' => 'Theme owner'])->save();
    $summaryField = $form->getFieldByHandle('summary');

    Formie::$plugin->getRendering()->pushRenderFrame($form, [
        'themeConfig' => [
            'fieldSummaryLabel' => [
                'class' => 'issued-summary-label',
                'append' => ['tag' => 'span', 'text' => '<safe-text>'],
            ],
        ],
    ]);

    try {
        $accessToken = FieldAccess::issueAccessToken($submission, (int)$summaryField->id);
    } finally {
        Formie::$plugin->getRendering()->popRenderFrame();
    }

    $html = WebRequestTestHelper::withWebRequestContext(function () use ($accessToken): string {
        Craft::$app->getRequest()->setBodyParams([
            'accessToken' => $accessToken,
            'frontendTheme' => 'none',
            'themeConfig' => Json::encode([
                'fieldSummaryLabel' => [
                    'tag' => 'script',
                    'attributes' => ['onclick' => 'alert(1)', 'class' => 'posted-summary-label'],
                    'append' => ['html' => '<img src=x onerror=alert(1)>'],
                ],
            ]),
        ]);

        return (new FieldsController('formie-fields-summary-theme', Craft::$app))->actionGetSummaryHtml();
    }, [
        'method' => 'POST',
    ]);

    expect($html)->toContain('issued-summary-label', '&lt;safe-text&gt;')
        ->and($html)->not->toContain('posted-summary-label', '<script', 'onclick=', 'onerror=');
});

it('rejects a tampered encrypted Summary theme snapshot', function (): void {
    $form = formie()
        ->form(['title' => 'Summary Theme Token'])
        ->singleLineTextField('fullName')
        ->summaryField('summary')
        ->create();
    $submission = formie()->submission($form)->with(['fullName' => 'Token owner'])->save();
    $summaryField = $form->getFieldByHandle('summary');
    $token = FieldAccess::issueAccessToken($submission, (int)$summaryField->id);

    $offset = intdiv(strlen((string)$token), 2);
    $replacement = $token[$offset] === 'A' ? 'B' : 'A';
    $tampered = substr_replace((string)$token, $replacement, $offset, 1);

    expect(FieldAccess::resolveAccessToken($tampered))->toBeNull();
});
