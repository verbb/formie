<?php

use verbb\formie\Formie;
use verbb\formie\fields;
use verbb\formie\helpers\Table;
use verbb\formie\models\FieldLayout;

use craft\db\Query;
use craft\helpers\Json;

it('saves legacy layouts before form statuses have been migrated', function (string $type, array $settings): void {
    $db = Craft::$app->getDb();
    $temporaryTable = '{{%legacy_layout_status_' . bin2hex(random_bytes(4)) . '}}';
    $db->createCommand()->renameTable(Table::FORMIE_FORM_STATUSES, $temporaryTable)->execute();
    Formie::$plugin->getFormStatuses()->invalidateCaches();
    Formie::$plugin->getForms()->invalidateFormCaches();
    Formie::$plugin->getSettings()->allowHiddenFieldTemplates = true;

    try {
        $field = new $type(['label' => 'Legacy field', 'handle' => 'legacyField', ...$settings]);
        $layout = new FieldLayout(['pages' => [['label' => 'Page', 'rows' => [['fields' => [$field]]]]]]);
        $saved = Formie::$plugin->getFields()->saveLayout($layout);
        expect($saved)->toBeTrue(json_encode([$layout->getErrors(), $layout->getErrorsTree()]));

        $stored = Json::decode((new Query())->select('settings')->from(Table::FORMIE_FIELDS)->where(['id' => $field->definitionId])->scalar());
        expect($stored['defaultValue'])->toBe($field->defaultValue);
    } finally {
        $db->createCommand()->renameTable($temporaryTable, Table::FORMIE_FORM_STATUSES)->execute();
        Formie::$plugin->getFormStatuses()->invalidateCaches();
        Formie::$plugin->getForms()->invalidateFormCaches();
    }
})->with([
    'text' => [fields\SingleLineText::class, []],
    'date picker' => [fields\Date::class, ['displayType' => 'datePicker', 'defaultOption' => 'date', 'defaultValue' => '2024-06-15 14:30:00']],
    'calendar' => [fields\Date::class, ['displayType' => 'calendar', 'defaultOption' => 'date', 'defaultValue' => '2024-06-15 14:30:00']],
    'date inputs' => [fields\Date::class, ['displayType' => 'inputs']],
    'date dropdowns' => [fields\Date::class, ['displayType' => 'dropdowns']],
    'hidden template' => [fields\Hidden::class, ['valueSource' => 'template', 'defaultTemplate' => '{{ 1 + 1 }}']],
    'group html' => [fields\Group::class, ['rows' => [['fields' => [['type' => fields\Html::class, 'label' => 'Notice', 'handle' => 'notice', 'htmlContent' => '<p>Notice</p>']]]]]],
]);
