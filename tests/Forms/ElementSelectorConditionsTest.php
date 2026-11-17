<?php

declare(strict_types=1);

use craft\db\Table;
use craft\elements\Entry;
use craft\helpers\Db;
use verbb\formie\elements\Form;

function elementSelectorEntries(): array
{
    $seed = Entry::find()->status(null)->slug('formie-seed-entry')->one();
    $other = new Entry([
        'sectionId' => $seed->sectionId,
        'typeId' => $seed->typeId,
        'siteId' => $seed->siteId,
        'title' => 'Graduate Scheme',
        'slug' => 'formie-graduate-scheme',
        'enabled' => true,
    ]);

    expect(Craft::$app->getElements()->saveElement($other))->toBeTrue();

    // The test entry type has no title field, so store the titles directly. The test transaction rolls them back.
    foreach ([$seed->id => 'Formie Seed Entry', $other->id => 'Graduate Scheme'] as $id => $title) {
        Db::update(Table::ELEMENTS_SITES, ['title' => $title], ['elementId' => $id, 'siteId' => $seed->siteId]);
    }

    return [
        Entry::find()->status(null)->id($seed->id)->one(),
        Entry::find()->status(null)->id($other->id)->one(),
    ];
}

function elementSelectorForm(array $entries, string $token, string $operator = '=', string $value = 'Graduate Scheme'): Form
{
    return formie()->form()
        ->entriesField('scheme', ['displayType' => 'dropdown', 'sourceType' => 'elements', 'sourceElements' => array_map(static fn(Entry $entry): array => ['id' => $entry->id], $entries)])
        ->singleLineTextField('note', ['enableConditions' => true, 'conditions' => [
            'showRule' => 'show',
            'conditionRule' => 'all',
            'conditions' => [['id' => 'new1', 'field' => $token, 'condition' => $operator, 'value' => $value]],
        ]])
        ->create();
}

function elementSelectorRule(Form $form): array
{
    return $form->getFieldByHandle('note')->getBrowserConditions()['rules'][0];
}

it('sends the browser each selectable entry’s title by ID for a Title rule', function(): void {
    [$seed, $other] = elementSelectorEntries();
    $rule = elementSelectorRule(elementSelectorForm([$seed, $other], '{field:scheme:title}'));

    expect($rule['browserSafe'])->toBeTrue()
        ->and($rule['source']['elementValues'])->toBe([
            (string)$seed->id => 'Formie Seed Entry',
            (string)$other->id => 'Graduate Scheme',
        ]);
});

it('sends each selectable entry’s public URL by ID for a URL rule', function(): void {
    [$seed, $other] = elementSelectorEntries();
    $rule = elementSelectorRule(elementSelectorForm([$seed, $other], '{field:scheme:url}', 'notEmpty', ''));

    expect(array_keys($rule['source']['elementValues']))->toBe([$seed->id, $other->id])
        ->and($rule['source']['elementValues'][$seed->id])->toBe((string)$seed->getUrl());
});

it('sends no entry values for rules the browser reads directly', function(string $token): void {
    [$seed, $other] = elementSelectorEntries();
    $rule = elementSelectorRule(elementSelectorForm([$seed, $other], $token));

    expect($rule['source'])->not->toHaveKey('elementValues');
})->with([
    'the selected entry IDs' => ['{field:scheme}'],
    'Control Panel URLs, which stay off the front end' => ['{field:scheme:cpUrl}'],
]);

it('shows the field on the server when the selected entry’s title matches', function(string $selected, bool $hidden): void {
    [$seed, $other] = elementSelectorEntries();
    $form = elementSelectorForm([$seed, $other], '{field:scheme:title}');
    $submission = formie()->submission($form)->with(['scheme' => [$selected === 'other' ? $other->id : $seed->id]])->save();

    expect($form->getFieldByHandle('note')->isConditionallyHidden($submission))->toBe($hidden);
})->with([
    'matching entry' => ['other', false],
    'another entry' => ['seed', true],
]);
