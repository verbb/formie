<?php

declare(strict_types=1);

use verbb\formie\elements\Form;
use verbb\formie\fields\Table;
use verbb\formie\Formie;
use verbb\formie\models\FieldLayout;

it('renders table row identities matching the input names before rows are appended', function (array $settings, array $value, array $indices): void {
    $field = new Table(array_merge([
        'handle' => 'items',
        'label' => 'Items',
        'columns' => ['col1' => ['heading' => 'Item', 'handle' => 'item', 'type' => 'singleline']],
    ], $settings));
    $form = new Form(['title' => 'Table row identity', 'handle' => 'tableRowIdentity']);
    $form->setFormLayout(new FieldLayout(['pages' => [['rows' => [['fields' => [$field]]]]]]));
    $html = (string)Formie::$plugin->getRendering()->renderField($form, 'items', ['value' => $value]);
    $document = new DOMDocument();
    @$document->loadHTML($html);
    $rows = (new DOMXPath($document))->query('//tr[@data-formie-table-row]');
    expect($rows->length)->toBe(count($indices));
    foreach ($indices as $position => $index) {
        $row = $rows->item($position);
        expect($row->getAttribute('data-formie-table-row-id'))->toBe((string)$index)
            ->and($row->getElementsByTagName('input')->item(0)->getAttribute('name'))->toBe('fields[items][' . $index . '][col1]');
    }
})->with([
    'defaults' => [['defaults' => [['col1' => 'First'], ['col1' => 'Second']]], [], [0, 1]],
    'minimum rows' => [['defaults' => [], 'minRows' => 2], [], [0, 1]],
    'existing values' => [['defaults' => []], [2 => ['col1' => 'First'], 5 => ['col1' => 'Second']], [2, 5]],
]);
