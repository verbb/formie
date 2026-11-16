<?php

declare(strict_types=1);

use verbb\formie\elements\Form;
use verbb\formie\fields\SingleLineText;
use verbb\formie\Formie;
use verbb\formie\helpers\FormSerializer;
use verbb\formie\helpers\References;
use verbb\formie\integrations\elements\Entry;
use verbb\formie\models\IntegrationField;

// Builder saves send the whole layout, so deleting a field is a save without it.
function deletedFieldMappingSave(Form $form, array $removeHandles): Form
{
    $pages = $form->getFormLayout()->getFormBuilderConfig();
    $remove = static function(array $rows) use (&$remove, $removeHandles): array {
        foreach ($rows as $rowIndex => $row) {
            $fields = [];

            foreach ($row['fields'] as $field) {
                if (in_array($field['handle'], $removeHandles, true)) {
                    continue;
                }

                if (isset($field['rows'])) {
                    $field['rows'] = $remove($field['rows']);
                }

                $fields[] = $field;
            }

            $rows[$rowIndex]['fields'] = $fields;
        }

        return array_values(array_filter($rows, fn($row) => $row['fields'] !== []));
    };

    foreach ($pages as $pageIndex => $page) {
        $pages[$pageIndex]['rows'] = $remove($page['rows']);
    }

    $form->getFormLayout()->setPages((new FormSerializer())->hydrateBuilder($pages, $form));
    expect(Craft::$app->getElements()->saveElement($form))->toBeTrue(json_encode($form->getErrors()));
    Formie::$plugin->getFields()->resetFieldRegistryCache();
    Formie::$plugin->getForms()->invalidateFormCaches();

    return Form::find()->id($form->id)->status(null)->one();
}

it('clears integration mappings to fields deleted from the form', function(): void {
    $form = formie()->form()
        ->singleLineTextField('leadName')
        ->singleLineTextField('notes')
        ->groupField('details', ['rows' => [['fields' => [
            ['type' => SingleLineText::class, 'handle' => 'city', 'label' => 'City'],
            ['type' => SingleLineText::class, 'handle' => 'region', 'label' => 'Region'],
        ]]]])
        ->create();
    $lead = References::field($form->getFieldByHandle('leadName')->reference);
    $notes = References::field($form->getFieldByHandle('notes')->reference);
    $city = References::field($form->getFieldByHandle('details')->getFieldByHandle('city')->reference);
    $region = References::field($form->getFieldByHandle('details')->getFieldByHandle('region')->reference);

    $form->settings->integrations = ['issue3128' => [
        'enabled' => true,
        'attributeMapping' => [
            'title' => ['kind' => 'reference', 'value' => $lead],
            'slug' => ['kind' => 'reference', 'value' => $notes],
        ],
        'contactFieldMapping' => [
            'city' => ['kind' => 'reference', 'value' => $city],
            'region' => ['kind' => 'reference', 'value' => $region],
            // Text and literal values are visible in the builder as typed, so they stay as entered.
            'message' => ['kind' => 'text', 'value' => 'Lead: ' . $lead],
            'source' => ['kind' => 'literal', 'value' => $lead],
            // A handle path may still be converted by an upgrade migration, so only stable references are cleared.
            'legacy' => ['kind' => 'reference', 'value' => '{field:leadName}'],
        ],
    ]];
    expect(Craft::$app->getElements()->saveElement($form))->toBeTrue(json_encode($form->getErrors()));

    $form = deletedFieldMappingSave(Form::find()->id($form->id)->status(null)->one(), ['leadName', 'region']);
    $settings = $form->settings->integrations['issue3128'];

    expect($form->getFieldByHandle('leadName'))->toBeNull()
        ->and($settings['attributeMapping'])->toBe([
            'title' => ['kind' => 'reference', 'value' => ''],
            'slug' => ['kind' => 'reference', 'value' => $notes],
        ])
        ->and($settings['contactFieldMapping'])->toBe([
            'city' => ['kind' => 'reference', 'value' => $city],
            'region' => ['kind' => 'reference', 'value' => ''],
            'message' => ['kind' => 'text', 'value' => 'Lead: ' . $lead],
            'source' => ['kind' => 'literal', 'value' => $lead],
            'legacy' => ['kind' => 'reference', 'value' => '{field:leadName}'],
        ]);

    // The cleared mapping is left out of the payload, where the stale reference failed every send.
    $submission = formie()->submission($form)->with(['notes' => 'Kept', 'details' => ['city' => 'Hobart']])->save();
    $values = (new Entry())->getFieldMappingValues($submission, $settings['attributeMapping'], [
        new IntegrationField(['handle' => 'title', 'name' => 'Title']),
        new IntegrationField(['handle' => 'slug', 'name' => 'Slug']),
    ]);

    expect($values)->toBe(['slug' => 'Kept']);
});

it('keeps integration mappings when a form is saved without removing fields', function(): void {
    $form = formie()->form()->singleLineTextField('leadName')->create();
    $lead = References::field($form->getFieldByHandle('leadName')->reference);
    $form->settings->integrations = ['issue3128' => [
        'enabled' => true,
        'attributeMapping' => ['title' => ['kind' => 'reference', 'value' => $lead]],
    ]];
    expect(Craft::$app->getElements()->saveElement($form))->toBeTrue(json_encode($form->getErrors()));

    $form = deletedFieldMappingSave(Form::find()->id($form->id)->status(null)->one(), []);

    expect($form->settings->integrations['issue3128']['attributeMapping'])->toBe(['title' => ['kind' => 'reference', 'value' => $lead]]);
});
