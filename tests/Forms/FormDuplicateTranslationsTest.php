<?php

declare(strict_types=1);

use verbb\formie\elements\Form;
use verbb\formie\Formie;

beforeEach(function (): void {
    if (!Craft::$app->getIsMultiSite()) {
        test()->markTestSkipped('Requires the multisite runtime; covered by the default suite.');
    }
});

function duplicateTranslationsSecondarySite(Form $form): craft\models\Site
{
    $sourceSiteId = Formie::$plugin->getFormSiteOverrides()->getSourceSiteId($form);
    $siteId = current(array_filter(
        Formie::$plugin->getFormSitePropagation()->resolveSiteIdsForForm($form),
        static fn(int $siteId): bool => $siteId !== $sourceSiteId,
    ));

    expect($siteId)->not->toBeFalse();

    return Craft::$app->getSites()->getSiteById((int)$siteId);
}

function duplicateTranslationsSourceForm(): array
{
    $siteOverrides = Formie::$plugin->getFormSiteOverrides();
    $form = formie()
        ->form(['title' => 'Enquiry', 'handle' => 'enquiry' . bin2hex(random_bytes(6))])
        ->singleLineTextField('name', ['label' => 'Name', 'required' => false])
        ->create();
    $form = Formie::$plugin->getForms()->getFormById((int)$form->id, $siteOverrides->getSourceSiteId($form));
    $site = duplicateTranslationsSecondarySite($form);
    $field = $form->getFieldByHandle('name');
    $page = $form->getPages()[0];

    $siteOverrides->saveTranslationBundle((int)$form->id, (int)$site->id, [
        'title' => 'Demande',
        'settings' => ['errorMessage' => 'Erreur'],
        'pages' => [$page->uid => ['label' => 'Page française']],
        'fieldOverrides' => [(string)$field->definitionId => ['label' => 'Nom']],
        'fieldInstanceOverrides' => [$field->reference => ['required' => true]],
    ]);

    return [$form, $site];
}

function duplicateTranslationsSiteTitle(Form $form, int $siteId): ?string
{
    $title = (new craft\db\Query())
        ->select(['title'])
        ->from([craft\db\Table::ELEMENTS_SITES])
        ->where(['elementId' => $form->id, 'siteId' => $siteId])
        ->scalar();

    return is_string($title) ? $title : null;
}

it('duplicates every site override onto the copied layout', function (): void {
    [$form, $site] = duplicateTranslationsSourceForm();
    $siteOverrides = Formie::$plugin->getFormSiteOverrides();
    $sourceField = $form->getFieldByHandle('name');

    $copy = Craft::$app->getElements()->duplicateElement($form, $form->getDuplicateAttributes());
    $copy = Formie::$plugin->getForms()->getFormById((int)$copy->id, $siteOverrides->getSourceSiteId($copy));
    $field = $copy->getFieldByHandle('name');
    $page = $copy->getPages()[0];
    $overrides = $siteOverrides->getOverrides((int)$copy->id, (int)$site->id);
    $fieldOverride = Formie::$plugin->getFieldSiteOverrides()->getOverride((int)$field->definitionId, (int)$site->id);
    $translated = $siteOverrides->applyToForm($copy, (int)$site->id, true);
    $expectedTitle = Craft::t('formie', '{title} Copy', ['title' => 'Demande'], $site->language);

    expect($field->definitionId)->not->toBe($sourceField->definitionId)
        ->and($field->reference)->not->toBe($sourceField->reference)
        ->and($overrides['title'] ?? null)->toBe($expectedTitle)
        ->and($overrides['settings']['errorMessage'] ?? null)->toBe('Erreur')
        ->and($overrides['pages'][$page->uid]['label'] ?? null)->toBe('Page française')
        ->and($overrides['fieldInstanceOverrides'][$field->reference]['required'] ?? null)->toBeTrue()
        ->and($fieldOverride['label'] ?? null)->toBe('Nom')
        ->and($translated->getFieldByHandle('name')->label)->toBe('Nom')
        ->and($translated->getFieldByHandle('name')->required)->toBeTrue()
        ->and($copy->title)->toBe(Craft::t('formie', '{title} Copy', ['title' => 'Enquiry']))
        ->and($copy->getFieldByHandle('name')->label)->toBe('Name')
        ->and($copy->getFieldByHandle('name')->required)->toBeFalse()
        ->and(duplicateTranslationsSiteTitle($copy, (int)$site->id))->toBe($expectedTitle)
        ->and($siteOverrides->getOverrides((int)$form->id, (int)$site->id)['title'] ?? null)->toBe('Demande');
});

it('keeps the source title when duplicating a form loaded for another site', function (): void {
    [$form, $site] = duplicateTranslationsSourceForm();
    $siteOverrides = Formie::$plugin->getFormSiteOverrides();
    // The Forms index duplicates the element loaded for its selected site.
    $siteForm = Form::find()->id($form->id)->siteId($site->id)->status(null)->one();

    expect($siteForm->title)->toBe('Demande');

    $copy = Craft::$app->getElements()->duplicateElement($siteForm, $siteForm->getDuplicateAttributes());

    expect(duplicateTranslationsSiteTitle($copy, $siteOverrides->getSourceSiteId($copy)))->toBe(Craft::t('formie', '{title} Copy', ['title' => 'Enquiry']))
        ->and(duplicateTranslationsSiteTitle($copy, (int)$site->id))->toBe(Craft::t('formie', '{title} Copy', ['title' => 'Demande'], $site->language));
});

it('applies unsaved builder translations to the copy using its own identities', function (): void {
    [$form, $site] = duplicateTranslationsSourceForm();
    $siteOverrides = Formie::$plugin->getFormSiteOverrides();
    $sourceField = $form->getFieldByHandle('name');
    $sourcePage = $form->getPages()[0];

    $copy = Craft::$app->getElements()->duplicateElement($form, $form->getDuplicateAttributes());
    Formie::$plugin->getStencils()->materializeTranslationsForCopy($form, $copy, (int)$site->id, [
        'title' => 'Demande modifiée',
        'pages' => [$sourcePage->uid => ['label' => 'Page modifiée']],
        'fieldOverrides' => [(string)$sourceField->definitionId => ['label' => 'Prénom']],
        'fieldInstanceOverrides' => [],
    ]);

    $copy = Formie::$plugin->getForms()->getFormById((int)$copy->id, $siteOverrides->getSourceSiteId($copy));
    $field = $copy->getFieldByHandle('name');
    $overrides = $siteOverrides->getOverrides((int)$copy->id, (int)$site->id);

    expect($overrides['title'] ?? null)->toBe(Craft::t('formie', '{title} Copy', ['title' => 'Demande modifiée'], $site->language))
        ->and($overrides['pages'][$copy->getPages()[0]->uid]['label'] ?? null)->toBe('Page modifiée')
        ->and($overrides['settings']['errorMessage'] ?? null)->toBe('Erreur')
        ->and($overrides)->not->toHaveKey('fieldInstanceOverrides')
        ->and(Formie::$plugin->getFieldSiteOverrides()->getOverride((int)$field->definitionId, (int)$site->id)['label'] ?? null)->toBe('Prénom')
        ->and(Formie::$plugin->getFieldSiteOverrides()->getOverride((int)$sourceField->definitionId, (int)$site->id)['label'] ?? null)->toBe('Nom')
        ->and($siteOverrides->getOverrides((int)$form->id, (int)$site->id)['pages'][$sourcePage->uid]['label'] ?? null)->toBe('Page française');
});
