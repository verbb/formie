<?php
namespace verbb\formie\helpers;

use verbb\formie\base\FormFieldInterface;
use verbb\formie\Formie;

use Craft;

class SchemaHelper
{
    // Static Methods
    // =========================================================================

    public static function textField($config = []): array
    {
        return array_merge([
            '$formkit' => 'text',
            'inputClass' => 'text fullwidth',
            'autocomplete' => 'off',
        ], $config);
    }

    public static function textareaField($config = []): array
    {
        return array_merge([
            '$formkit' => 'textarea',
            'inputClass' => 'text fullwidth',
        ], $config);
    }

    public static function selectField($config = []): array
    {
        return array_merge([
            '$formkit' => 'select',
        ], $config);
    }

    public static function multiSelectField($config = []): array
    {
        return array_merge([
            '$formkit' => 'multiSelect',
        ], $config);
    }

    public static function numberField($config = []): array
    {
        return array_merge([
            '$formkit' => 'number',
            'size' => '3',
            'inputClass' => 'text',
            'validation' => 'number|min:0',
        ], $config);
    }

    public static function dateField($config = []): array
    {
        return array_merge([
            '$formkit' => 'date',
            'inputClass' => 'text fullwidth',
        ], $config);
    }

    public static function checkboxSelectField($config = []): array
    {
        // Might be a bug in Formulate, getting `Duplicate keys detected: 'formulate-global-2'.`
        if (isset($config['options'])) {
            foreach ($config['options'] as $key => $option) {
                $config['options'][$key]['id'] = $option['value'];
            }
        }

        return array_merge([
            '$formkit' => 'checkboxSelect',
        ], $config);
    }

    public static function checkboxField($config = []): array
    {
        // Might be a bug in Formulate, getting `Duplicate keys detected: 'formulate-global-2'.`
        if (isset($config['options'])) {
            foreach ($config['options'] as $key => $option) {
                $config['options'][$key]['id'] = $option['value'];
            }
        }

        return array_merge([
            '$formkit' => 'checkbox',
        ], $config);
    }

    public static function lightswitchField($config = []): array
    {
        return array_merge([
            '$formkit' => 'lightswitch',
            'labelPosition' => 'before',
        ], $config);
    }

    public static function toggleBlocks($config, $children = []): array
    {
        return array_merge([
            '$formkit' => 'toggleBlocks',
            'validation' => 'minBlock:2',
            'children' => $children,
        ], $config);
    }

    public static function toggleBlock($config, $children = []): array
    {
        return [
            '$cmp' => 'ToggleBlock',
            'props' => $config,
            'children' => $children,
        ];
    }

    public static function tableField($config = []): array
    {
        return array_merge([
            '$formkit' => 'table',
            'validation' => '+min:1|uniqueTableCellLabel|uniqueTableCellValue|requiredTableCellLabel',
        ], $config);
    }

    public static function variableTextField($config = []): array
    {
        return array_merge([
            '$formkit' => 'variableText',
        ], $config);
    }

    public static function richTextField($config = []): array
    {
        return array_merge([
            '$formkit' => 'richText',
        ], $config);
    }

    public static function elementSelectField($config = []): array
    {
        return array_merge([
            '$formkit' => 'elementSelect',
        ], $config);
    }


    // Reusable
    // =========================================================================

    public static function labelField($config = []): array
    {
        return self::textField(array_merge([
            'label' => Craft::t('formie', 'Label'),
            'help' => Craft::t('formie', 'The label that describes this field.'),
            'name' => 'label',
            'validation' => 'required',
            'required' => true,
        ], $config));
    }

    public static function handleField($config = []): array
    {
        return array_merge([
            '$formkit' => 'handle',
            'label' => Craft::t('formie', 'Handle'),
            'help' => Craft::t('formie', 'How you’ll refer to this field in your templates. Use the refresh icon to re-generate this from your field label.'),
            'warning' => Craft::t('formie', 'Changing this may result in your field not working as expected.'),
            'name' => 'handle',
            'validation' => 'required|uniqueHandle',
            'required' => true,
            'autocomplete' => 'off',
        ], $config);
    }

    public static function labelPosition(FormFieldInterface $field, $config = []): array
    {
        return self::selectField(array_merge([
            'label' => Craft::t('formie', 'Label Position'),
            'help' => Craft::t('formie', 'How the label for the field should be positioned.'),
            'name' => 'labelPosition',
            'options' => array_merge(
                [['label' => Craft::t('formie', 'Form Default'), 'value' => '']],
                Formie::$plugin->getFields()->getLabelPositionsArray($field)
            ),
        ], $config));
    }

    public static function subfieldLabelPosition($config = []): array
    {
        return self::selectField(array_merge([
            'label' => Craft::t('formie', 'Subfield Label Position'),
            'help' => Craft::t('formie', 'How the label for the subfields should be positioned.'),
            'name' => 'subfieldLabelPosition',
            'options' => array_merge(
                [['label' => Craft::t('formie', 'Form Default'), 'value' => '']],
                Formie::$plugin->getFields()->getLabelPositionsArray()
            ),
        ], $config));
    }

    public static function instructions($config = []): array
    {
        return self::textareaField(array_merge([
            'label' => Craft::t('formie', 'Instructions'),
            'help' => Craft::t('formie', 'Instructions to guide the user when filling out this form.'),
            'name' => 'instructions',
            'rows' => '4',
        ], $config));
    }

    public static function instructionsPosition(FormFieldInterface $field, $config = []): array
    {
        return self::selectField(array_merge([
            'label' => Craft::t('formie', 'Instructions Position'),
            'help' => Craft::t('formie', 'How the instructions for the field should be positioned.'),
            'name' => 'instructionsPosition',
            'options' => array_merge(
                [['label' => Craft::t('formie', 'Form Default'), 'value' => '']],
                Formie::$plugin->getFields()->getInstructionsPositionsArray($field)
            ),
        ], $config));
    }

    public static function cssClasses($config = []): array
    {
        return self::textField(array_merge([
            'label' => Craft::t('formie', 'CSS Classes'),
            'help' => Craft::t('formie', 'Add classes to be outputted on this field’s container.'),
            'name' => 'cssClasses',
        ], $config));
    }

    public static function containerAttributesField($config = []): array
    {
        return self::tableField(array_merge([
            'label' => Craft::t('formie', 'Container Attributes'),
            'help' => Craft::t('formie', 'Add attributes to be outputted on this field’s container.'),
            'validation' => 'min:0',
            'newRowDefaults' => [
                'label' => '',
                'value' => '',
            ],
            'generateValue' => false,
            'columns' => [
                [
                    'type' => 'label',
                    'label' => 'Name',
                    'class' => 'singleline-cell textual',
                ],
                [
                    'type' => 'value',
                    'label' => 'Value',
                    'class' => 'singleline-cell textual',
                ],
            ],
            'name' => 'containerAttributes',
        ], $config));
    }

    public static function inputAttributesField($config = []): array
    {
        return self::tableField(array_merge([
            'label' => Craft::t('formie', 'Input Attributes'),
            'help' => Craft::t('formie', 'Add attributes to be outputted on this field’s input.'),
            'validation' => 'min:0',
            'newRowDefaults' => [
                'label' => '',
                'value' => '',
            ],
            'generateValue' => false,
            'columns' => [
                [
                    'type' => 'label',
                    'label' => 'Name',
                    'class' => 'singleline-cell textual',
                ],
                [
                    'type' => 'value',
                    'label' => 'Value',
                    'class' => 'singleline-cell textual',
                ],
            ],
            'name' => 'inputAttributes',
        ], $config));
    }

    public static function prePopulate($config = []): array
    {
        return self::textField(array_merge([
            'label' => Craft::t('formie', 'Pre-Populate Value'),
            'help' => Craft::t('formie', 'Specify a query parameter to pre-populate the value of this field.'),
            'name' => 'prePopulate',
        ], $config));
    }

    public static function enableConditionsField($config = []): array
    {
        return self::lightswitchField(array_merge([
            'label' => Craft::t('formie', 'Enable Conditions'),
            'help' => Craft::t('formie', 'Whether to enable conditional logic to control how this field is shown.'),
            'name' => 'enableConditions',
        ], $config));
    }

    public static function conditionsField($config = []): array
    {
        return array_merge([
            '$formkit' => 'fieldConditions',
            'name' => 'conditions',
            'if' => '$get(enableConditions).value',
        ], $config);
    }

    public static function enableContentEncryptionField($config = []): array
    {
        return self::lightswitchField(array_merge([
            'label' => Craft::t('formie', 'Enable Content Encryption'),
            'help' => Craft::t('formie', 'Whether to encrypt the value saved for this field for data-security purposes.'),
            'name' => 'enableContentEncryption',
        ], $config));
    }

    public static function visibility($config = []): array
    {
        return self::selectField(array_merge([
            'label' => Craft::t('formie', 'Visibility'),
            'help' => Craft::t('formie', 'The visibility of the field on the front-end.'),
            'info' => Craft::t('formie', 'A “Hidden” field will be hidden from view, but still rendered. A “Disabled” field will not be rendered on the page at all.'),
            'name' => 'visibility',
            'options' => [
                ['label' => Craft::t('formie', 'Visible'), 'value' => ''],
                ['label' => Craft::t('formie', 'Hidden'), 'value' => 'hidden'],
                ['label' => Craft::t('formie', 'Disabled'), 'value' => 'disabled'],
            ],
        ], $config));
    }

    public static function columnTypeField($config = []): array
    {
        return self::selectField(array_merge([
            'label' => Craft::t('formie', 'Column Type'),
            'help' => Craft::t('formie', 'The type of column this field should get in the database.'),
            'warning' => Craft::t('formie', 'Changing this may result in data loss.'),
            'name' => 'columnType',
            'options' => [
                ['label' => Craft::t('formie', 'Automatic'), 'value' => ''],
                ['label' => Craft::t('formie', 'varchar (255B)'), 'value' => 'string'],
                ['label' => Craft::t('formie', 'text (~64KB)'), 'value' => 'text'],
                ['label' => Craft::t('formie', 'mediumtext (~16MB)'), 'value' => 'mediumtext'],
            ],
        ], $config));
    }

    public static function fieldSelectField($config = []): array
    {
        return array_merge([
            '$formkit' => 'fieldSelect',
            'excludeSelf' => true,
        ], $config);
    }

    public static function matchField($config = []): array
    {
        return array_merge([
            '$formkit' => 'fieldSelect',
            'label' => Craft::t('formie', 'Match Field'),
            'help' => Craft::t('formie', 'Select a field of the same type where its value must match this field.'),
            'name' => 'matchField',
            'excludeSelf' => true,
        ], $config);
    }

    public static function extractFieldsFromSchema($fieldSchema, $names = []): array
    {
        foreach ($fieldSchema as $field) {
            if (isset($field['name'])) {
                $names[] = $field['name'];
            }

            if (isset($field['children'])) {
                self::extractFieldsFromSchema($field['children'], $names);
            }
        }

        return $names;
    }

    public static function setFieldAttributes(&$fieldSchema)
    {
        // Automaticallty set the `id` and `key` attributes for fields, which FormKit needs
        foreach ($fieldSchema as &$field) {
            $name = $field['name'] ?? null;
            $id = $field['id'] ?? null;
            $key = $field['key'] ?? null;

            if ($name && !$id) {
                $field['id'] = $name;
            }

            if ($name && !$key) {
                $field['key'] = $name;
            }

            if (isset($field['children'])) {
                self::setFieldAttributes($field['children']);
            }
        }
    }
}
