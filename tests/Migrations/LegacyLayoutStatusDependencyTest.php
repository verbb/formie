<?php

use verbb\formie\Formie;
use verbb\formie\fields;
use verbb\formie\helpers\Table;
use verbb\formie\models\FieldLayout;

it('saves legacy layouts before form statuses have been migrated', function (string $type, array $settings): void {
    $db = Craft::$app->getDb();
    $temporaryTable = '{{%legacy_layout_status_backup}}';
    $db->createCommand()->renameTable(Table::FORMIE_FORM_STATUSES, $temporaryTable)->execute();
    Formie::$plugin->getFormStatuses()->invalidateCaches();
    Formie::$plugin->getForms()->invalidateFormCaches();

    try {
        $field = new $type(['label' => 'Legacy field', 'handle' => 'legacyField', ...$settings]);
        $layout = new FieldLayout(['pages' => [['label' => 'Page', 'rows' => [['fields' => [$field]]]]]]);
        expect(Formie::$plugin->getFields()->saveLayout($layout), json_encode($layout->getErrorsTree()))->toBeTrue();
    } finally {
        $db->createCommand()->renameTable($temporaryTable, Table::FORMIE_FORM_STATUSES)->execute();
        Formie::$plugin->getFormStatuses()->invalidateCaches();
        Formie::$plugin->getForms()->invalidateFormCaches();
    }
})->with([
    'text' => [fields\SingleLineText::class, []],
    'date' => [fields\Date::class, []],
    'hidden template' => [fields\Hidden::class, ['valueSource' => 'template', 'defaultTemplate' => '{{ 1 + 1 }}']],
    'forms' => [fields\Forms::class, []],
    'group html' => [fields\Group::class, ['rows' => [['fields' => [['type' => fields\Html::class, 'label' => 'Notice', 'handle' => 'notice', 'htmlContent' => '<p>Notice</p>']]]]]],
]);
