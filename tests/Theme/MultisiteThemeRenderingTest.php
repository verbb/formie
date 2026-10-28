<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use verbb\formie\elements\Form;
use verbb\formie\fields\SingleLineText;
use verbb\formie\Formie;

it('preserves render-time theme overrides through localized page and nested field rendering', function (int $siteIndex, bool $translated, string $entryPoint): void {
    if (!Craft::$app->getIsMultiSite()) {
        test()->markTestSkipped('Requires the default multisite runtime.');
    }

    $form = formie()->form(['title' => 'Multisite theme'])
        ->singleLineTextField('message', ['label' => 'Original message'])
        ->groupField('details', ['rows' => [['fields' => [[
            'type' => SingleLineText::class,
            'handle' => 'nested',
            'label' => 'Nested message',
        ]]]]])->create();
    $site = Craft::$app->getSites()->getAllSites()[$siteIndex];
    $sourceSiteId = Formie::$plugin->getFormSiteOverrides()->getSourceSiteId($form);

    if ($translated) {
        Formie::$plugin->getFormSiteOverrides()->saveTranslationBundle((int)$form->id, (int)$site->id, [
            'fieldOverrides' => [(string)$form->getFieldByHandle('message')->definitionId => ['label' => 'Translated message']],
        ]);
    }

    $html = WebRequestTestHelper::withWebRequestContext(function () use ($form, $site, $entryPoint): string {
        Craft::$app->getSites()->setCurrentSite($site);
        Formie::$plugin->getForms()->invalidateFormCaches();
        $stored = Form::find()->id($form->id)->siteId($site->id)->one();
        expect($stored)->not->toBeNull();
        $options = [
            'themeConfig' => ['field' => ['attributes' => ['class' => 'test-class']]],
            'renderCss' => false,
            'renderJs' => false,
        ];
        $rendering = Formie::$plugin->getRendering();

        $html = match ($entryPoint) {
            'handle' => Craft::$app->getView()->renderString('{{ craft.formie.renderForm(handle, options) }}', ['handle' => $stored->handle, 'options' => $options]),
            'form' => (string)$rendering->renderForm($stored, $options),
            'page' => (string)$rendering->renderPage($stored, null, $options),
            'field' => (string)$rendering->renderField($stored, 'details', $options),
        };

        expect($rendering->getActiveRenderFrame())->toBeNull();

        return $html;
    });

    $document = new DOMDocument();
    @$document->loadHTML('<?xml encoding="utf-8" ?>' . $html);
    $wrappers = (new DOMXPath($document))->query('//*[@data-formie-field]');
    expect($wrappers->length)->toBe($entryPoint === 'field' ? 2 : 3);

    foreach ($wrappers as $wrapper) {
        expect(explode(' ', $wrapper->getAttribute('class')))->toContain('test-class');
    }

    if ($entryPoint !== 'field') {
        expect($html)->toContain($translated ? 'Translated message' : 'Original message');
    }

    $canonical = Form::find()->id($form->id)->siteId($sourceSiteId)->one();
    expect($canonical->getFieldByHandle('message')->label)->toBe('Original message');
})->with([
    'primary' => [0, false],
    'secondary without translations' => [1, false],
    'secondary with translations' => [1, true],
    'other secondary with translations' => [2, true],
])->with(['handle', 'form', 'page', 'field']);
