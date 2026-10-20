<?php

declare(strict_types=1);

use verbb\formie\elements\Form;
use verbb\formie\Formie;
use verbb\formie\helpers\FormSerializer;
use yii\base\InvalidConfigException;

it('persists date child edits from the active builder variant', function (string $displayType, string $collectMode, string $layoutKey): void {
    $form = formie()->form()->dateField('appointment', [
        'displayType' => $displayType,
        'collectMode' => $collectMode,
    ])->create();
    $identity = static fn($child) => [$child->id, $child->definitionId, $child->uid, $child->reference];
    $identities = array_map($identity, $form->getFieldByHandle('appointment')->getFields());
    $pages = $form->getFormLayout()->getFormBuilderConfig();
    $field = &$pages[0]['rows'][0]['fields'][0];
    $field['label'] = 'Afspraak';
    $labels = [];

    // The editor changes layouts.<variant>; rows still contains the original children.
    foreach ($field['layouts'][$layoutKey] as &$row) {
        foreach ($row['fields'] as &$child) {
            $labels[$child['handle']] = match ($child['handle']) {
                'date' => 'Datum',
                'time' => 'Tijd',
                default => 'Aangepast ' . $child['handle'],
            };
            $child['label'] = $labels[$child['handle']];
            $child['placeholder'] = 'Kies ' . $child['handle'];
        }
        unset($child);
    }
    unset($row, $field);

    for ($save = 0; $save < 2; $save++) {
        $form->getFormLayout()->setPages((new FormSerializer())->hydrateBuilder($pages, $form));
        expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();
        Formie::$plugin->getFields()->resetFieldRegistryCache();
        $form = Form::find()->id($form->id)->one();
        $date = $form->getFieldByHandle('appointment');

        expect($date->label)->toBe('Afspraak')
            ->and(array_map($identity, $date->getFields()))->toBe($identities);

        foreach ($date->getFields() as $child) {
            expect($child->label)->toBe($labels[$child->handle])
                ->and($child->placeholder)->toBe('Kies ' . $child->handle);
        }

        if ($displayType === 'calendar') {
            $document = new DOMDocument();
            @$document->loadHTML((string)Formie::$plugin->getRendering()->renderField($form, 'appointment'));
            $xpath = new DOMXPath($document);

            foreach (['date', 'time'] as $handle) {
                $input = $xpath->query('//input[@type="' . $handle . '"]')->item(0);
                expect($input)->not->toBeNull();
                $label = $xpath->query('//label[@for="' . $input->getAttribute('id') . '"]')->item(0);
                expect($label)->not->toBeNull()
                    ->and(trim($label->textContent))->toBe($labels[$handle]);
            }
        }

        // A subsequent unrelated form edit must retain the saved child configuration.
        $form->title .= ' edited';
        $pages = $form->getFormLayout()->getFormBuilderConfig();
    }
})->with([
    'simple calendar' => ['calendar', 'single', 'calendar'],
    'advanced calendar' => ['datePicker', 'single', 'calendar'],
    'calendar range' => ['datePicker', 'range', 'calendarRange'],
    'text inputs' => ['inputs', 'single', 'inputs'],
    'dropdowns' => ['dropdowns', 'single', 'dropdowns'],
]);

it('keeps edited date variants when switching display type and switching back', function (): void {
    $form = formie()->form()->dateField('appointment', ['displayType' => 'calendar'])->create();

    foreach (['inputs', 'calendar', 'inputs'] as $index => $displayType) {
        $pages = $form->getFormLayout()->getFormBuilderConfig();
        $field = &$pages[0]['rows'][0]['fields'][0];
        $field['displayType'] = $displayType;

        if ($index < 2) {
            $field['layouts'][$displayType][0]['fields'][0]['label'] = $displayType === 'calendar' ? 'Datum' : 'Jaar';
        }

        unset($field);
        $form->getFormLayout()->setPages((new FormSerializer())->hydrateBuilder($pages, $form));

        expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();
        Formie::$plugin->getFields()->resetFieldRegistryCache();
        $form = Form::find()->id($form->id)->one();
        $date = $form->getFieldByHandle('appointment');

        expect($date->displayType)->toBe($displayType)
            ->and($date->getFields()[0]->handle)->toBe($displayType === 'calendar' ? 'date' : 'year')
            ->and($date->getFields()[0]->label)->toBe($displayType === 'calendar' ? 'Datum' : 'Jaar');
    }
});

it('accepts date builder rows when no variant map is supplied', function (): void {
    $form = formie()->form()->dateField('appointment', ['displayType' => 'calendar'])->create();
    $pages = $form->getFormLayout()->getFormBuilderConfig();
    $field = &$pages[0]['rows'][0]['fields'][0];
    unset($field['layouts']);
    $field['rows'][0]['fields'][0]['label'] = 'Datum';
    unset($field);
    $form->getFormLayout()->setPages((new FormSerializer())->hydrateBuilder($pages, $form));

    expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();
    Formie::$plugin->getFields()->resetFieldRegistryCache();
    $reloaded = Form::find()->id($form->id)->one();

    expect($reloaded->getFieldByHandle('appointment')->getFieldByHandle('date')->label)->toBe('Datum');
});

it('rejects date variant children belonging to another parent', function (): void {
    $form = formie()->form()
        ->dateField('appointment', ['displayType' => 'calendar'])
        ->dateField('otherAppointment', ['displayType' => 'calendar'])
        ->create();
    $pages = $form->getFormLayout()->getFormBuilderConfig();
    $foreignChild = $form->getFieldByHandle('otherAppointment')->getFieldByHandle('date')->getFormBuilderConfig();
    $pages[0]['rows'][0]['fields'][0]['layouts']['calendar'][0]['fields'][0] = $foreignChild;

    expect(fn() => (new FormSerializer())->hydrateBuilder($pages, $form))
        ->toThrow(InvalidConfigException::class, 'different parent');
});
