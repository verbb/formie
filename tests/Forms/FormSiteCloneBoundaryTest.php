<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\models\RichText;

it('isolates localized layout settings and callbacks from the canonical form', function (): void {
    if (!Craft::$app->getIsMultiSite()) { test()->markTestSkipped('Requires the multisite runtime.'); }
    $service = Formie::$plugin->getFormSiteOverrides();
    $form = formie()->form()->singleLineTextField('message')->repeaterField('items', ['rows' => [['fields' => [[
        'type' => \verbb\formie\fields\SingleLineText::class, 'handle' => 'itemName', 'label' => 'Canonical item',
    ]]]]])->create();
    $sourceSite = $service->getSourceSiteId($form);
    $siteIds = Formie::$plugin->getFormSitePropagation()->resolveSiteIdsForForm($form);
    $secondary = array_values(array_filter($siteIds, fn($id) => (int)$id !== $sourceSite))[0];
    $form->settings->submitActionMessage = RichText::from('Canonical message');
    $page = $form->getPages()[0];
    $page->getPageSettings()->submitButtonLabel = 'Canonical submit';
    $service->saveOverrides($form->id, $secondary, [
        'settings' => ['submitActionMessage' => 'Localized message'],
        'pages' => [$page->uid => ['label' => 'Localized page', 'settings' => ['submitButtonLabel' => 'Localized submit']]],
    ]);
    $nested = $form->getFieldByHandle('items')->getFieldLayout()->getFields()[0];
    $nested->on('auditCallback', static function (): void {});
    $localized = $service->applyToForm($form, $secondary, true);
    expect($localized)->not->toBe($form);
    expect((string)$localized->settings->submitActionMessage)->toContain('Localized message');
    expect((string)$form->settings->submitActionMessage)->toContain('Canonical message');
    expect($localized->getPages()[0]->getPageSettings()->submitButtonLabel)->toBe('Localized submit');
    expect($page->getPageSettings()->submitButtonLabel)->toBe('Canonical submit');
    $localizedNested = $localized->getFieldByHandle('items')->getFieldLayout()->getFields()[0];
    expect($localizedNested)->not->toBe($nested);
    expect($localizedNested->fieldId)->toBe($nested->fieldId);
    $localizedNested->label = 'Changed local item';
    expect($nested->label)->toBe('Canonical item');
});
