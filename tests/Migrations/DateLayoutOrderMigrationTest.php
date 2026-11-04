<?php

declare(strict_types=1);

use craft\db\Query;
use craft\helpers\Json;
use verbb\formie\elements\Form;
use verbb\formie\Formie;
use verbb\formie\helpers\Table;
use verbb\formie\migrations\m261104_030000_date_layout_order;

function dateOrderForm(array $config = []): Form
{
    $form = formie()->form()->dateField('when', ['displayType' => 'dropdowns', 'dateFormat' => 'd-m-Y', ...$config])->create();

    return Form::find()->id($form->id)->one();
}

function dateOrderReload(Form $form): Form
{
    Formie::$plugin->getFields()->resetFieldRegistryCache();
    Craft::$app->getElements()->invalidateCachesForElementType(Form::class);

    return Form::find()->id($form->id)->one();
}

function dateOrderSettings(Form $form): array
{
    return Json::decode((new Query())->select('settings')->from(Table::FORMIE_FIELDS)->where(['id' => $form->getFieldByHandle('when')->definitionId])->scalar());
}

// Rearranges rendered placements directly, as the earlier format-order repair did.
function dateOrderPlace(Form $form, array $order): void
{
    $field = $form->getFieldByHandle('when');

    foreach ($order as $sortOrder => $handle) {
        $child = $field->getFieldByHandle($handle);
        Craft::$app->getDb()->createCommand()->update(Table::FORMIE_FORM_FIELDS, ['sortOrder' => $sortOrder], ['id' => $child->id])->execute();
    }
}

function dateOrderRendered(Form $form): array
{
    $field = $form->getFieldByHandle('when');
    $document = new DOMDocument();
    @$document->loadHTML('<?xml encoding="UTF-8">' . Formie::$plugin->getRendering()->renderField($form, $field));
    $names = [];

    foreach ((new DOMXPath($document))->query('//input[@name]|//select[@name]') as $node) {
        if (preg_match('/\[([A-Za-z]+)\]$/', $node->getAttribute('name'), $match)) {
            $names[] = $match[1];
        }
    }

    return $names;
}

function dateOrderRun(): void
{
    expect((new m261104_030000_date_layout_order())->safeUp())->toBeTrue();
}

const DATE_ORDER_FORMAT = ['day', 'month', 'year', 'hour', 'minute', 'second', 'ampm'];

it('keeps the format order of date parts through an unchanged save', function(string $display, array $order): void {
    $form = dateOrderForm(['displayType' => $display]);
    dateOrderPlace($form, $order);
    $form = dateOrderReload($form);
    $before = dateOrderRendered($form);
    expect(array_slice($before, 0, 3))->toBe(array_slice($order, 0, 3));

    dateOrderRun();
    dateOrderRun();

    $form = dateOrderReload($form);
    expect(dateOrderRendered($form))->toBe($before);

    foreach ([1, 2] as $save) {
        expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();
        $form = dateOrderReload($form);

        expect(dateOrderRendered($form))->toBe($before)
            ->and(array_column(dateOrderSettings($form)['layouts'][$display][0]['fields'], 'handle'))->toBe($order);
    }
})->with([
    'dropdowns day first' => ['dropdowns', DATE_ORDER_FORMAT],
    'inputs day first' => ['inputs', DATE_ORDER_FORMAT],
    'dropdowns month first' => ['dropdowns', ['month', 'day', 'year', 'hour', 'minute', 'second', 'ampm']],
]);

it('only reorders the stored parts and leaves their settings alone', function(): void {
    $form = dateOrderForm();
    $field = $form->getFieldByHandle('when');
    $stored = dateOrderSettings($form);
    $byHandle = array_column($stored['layouts']['dropdowns'][0]['fields'], null, 'handle');
    dateOrderPlace($form, DATE_ORDER_FORMAT);

    dateOrderRun();

    $repaired = dateOrderSettings($form);
    $repairedByHandle = array_column($repaired['layouts']['dropdowns'][0]['fields'], null, 'handle');
    unset($stored['layouts'], $repaired['layouts']);

    expect($repairedByHandle)->toEqual($byHandle)
        ->and($repaired)->toEqual($stored)
        ->and($field->displayType)->toBe('dropdowns');
});

it('leaves date fields it has no single order for untouched', function(callable $arrange): void {
    $form = dateOrderForm();
    $arrange($form);
    $id = $form->getFieldByHandle('when')->definitionId;
    $read = fn() => (new Query())->select('settings')->from(Table::FORMIE_FIELDS)->where(['id' => $id])->scalar();
    $before = $read();

    dateOrderRun();

    expect($read())->toBe($before);
})->with([
    'stored order already matches' => [fn(Form $form) => null],
    'stored parts differ from the layout' => [function(Form $form): void {
        $settings = dateOrderSettings($form);
        array_pop($settings['layouts']['dropdowns'][0]['fields']);
        Craft::$app->getDb()->createCommand()->update(Table::FORMIE_FIELDS, ['settings' => Json::encode($settings)], ['id' => $form->getFieldByHandle('when')->definitionId])->execute();
        dateOrderPlace($form, DATE_ORDER_FORMAT);
    }],
    'stored parts span several rows' => [function(Form $form): void {
        $settings = dateOrderSettings($form);
        $fields = $settings['layouts']['dropdowns'][0]['fields'];
        $settings['layouts']['dropdowns'] = [['fields' => array_slice($fields, 0, 3)], ['fields' => array_slice($fields, 3)]];
        Craft::$app->getDb()->createCommand()->update(Table::FORMIE_FIELDS, ['settings' => Json::encode($settings)], ['id' => $form->getFieldByHandle('when')->definitionId])->execute();
        dateOrderPlace($form, DATE_ORDER_FORMAT);
    }],
]);

it('leaves a field without a stored variant and calendar fields alone', function(): void {
    $calendar = dateOrderForm(['displayType' => 'calendar']);
    $bare = dateOrderForm();
    $settings = dateOrderSettings($bare);
    unset($settings['layouts']);
    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_FIELDS, ['settings' => Json::encode($settings)], ['id' => $bare->getFieldByHandle('when')->definitionId])->execute();
    dateOrderPlace($bare, DATE_ORDER_FORMAT);
    $calendarBefore = dateOrderSettings($calendar);
    $bareBefore = dateOrderSettings($bare);

    dateOrderRun();

    expect(dateOrderSettings($calendar))->toEqual($calendarBefore)
        ->and(dateOrderSettings($bare))->toEqual($bareBefore);

    // A field with no stored variant takes its order from the saved layout, so it already survives a save.
    $bare = dateOrderReload($bare);
    expect(Craft::$app->getElements()->saveElement($bare))->toBeTrue();
    expect(array_slice(dateOrderRendered(dateOrderReload($bare)), 0, 3))->toBe(['day', 'month', 'year']);
});

it('keeps a hand-arranged order through a builder reload', function(): void {
    $form = dateOrderForm();
    $order = ['month', 'year', 'day', 'hour', 'minute', 'second', 'ampm'];
    dateOrderPlace($form, $order);
    dateOrderRun();
    $form = dateOrderReload($form);
    $settings = $form->getFieldByHandle('when')->getFormBuilderSettings();

    expect(array_map(fn($field) => $field['handle'] ?? $field['settings']['handle'], $settings['rows'][0]['fields']))->toBe($order)
        ->and(array_map(fn($field) => $field['handle'] ?? $field['settings']['handle'], $settings['layouts']['dropdowns'][0]['fields']))->toBe($order);
});
