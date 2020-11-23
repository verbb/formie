<?php
namespace verbb\formie\helpers;

use verbb\formie\base\FormFieldInterface;
use verbb\formie\Formie;

use Craft;

use Throwable;

class SchemaHelper
{
    // Public Methods
    // =========================================================================

    public static function textField($config = [])
    {
        return array_merge([
            'type' => 'text',
            'class' => 'text fullwidth',
            'autocomplete' => 'off',
        ], $config);
    }

    public static function textareaField($config = [])
    {
        return array_merge([
            'type' => 'textarea',
            'class' => 'text fullwidth',
        ], $config);
    }

    public static function selectField($config = [])
    {
        return array_merge([
            'type' => 'select',
        ], $config);
    }

    public static function multiSelectField($config = [])
    {
        return array_merge([
            'type' => 'multiSelect',
        ], $config);
    }

    public static function dateField($config = [])
    {
        return array_merge([
            'type' => 'date',
            'class' => 'text fullwidth',
        ], $config);
    }

    public static function checkboxSelectField($config = [])
    {
        // Might be a bug in Formulate, getting `Duplicate keys detected: 'formulate-global-2'.`
        if (isset($config['options'])) {
            foreach ($config['options'] as $key => $option) {
                $config['options'][$key]['id'] = $option['value'];
            }
        }

        return array_merge([
            'type' => 'checkboxSelect',
        ], $config);
    }

    public static function checkboxField($config = [])
    {
        // Might be a bug in Formulate, getting `Duplicate keys detected: 'formulate-global-2'.`
        if (isset($config['options'])) {
            foreach ($config['options'] as $key => $option) {
                $config['options'][$key]['id'] = $option['value'];
            }
        }

        return array_merge([
            'type' => 'checkbox',
        ], $config);
    }

    public static function lightswitchField($config = [])
    {
        return array_merge([
            'type' => 'lightswitch',
            'labelPosition' => 'before',
        ], $config);
    }

    public static function toggleContainer($toggleAttribute, $config = [])
    {
        return [
            'component' => 'toggle-group',
            'conditional' => $toggleAttribute,
            'children' => $config,
        ];
    }

    public static function toggleBlocks($config, $children = [])
    {
        return array_merge([
            'type' => 'toggleBlocks',
            'validation' => 'minBlock:2',
            'children' => $children,
        ], $config);
    }

    public static function toggleBlock($config, $children = [])
    {
        return array_merge([
            'type' => 'toggleBlock',
            'children' => $children,
        ], $config);
    }

    public static function tableField($config = [])
    {
        return array_merge([
            'component' => 'table-block',
            'validation' => 'min:1,length|uniqueLabels|uniqueValues|requiredLabels',
        ], $config);
    }

    public static function variableTextField($config = [])
    {
        return array_merge([
            'type' => 'variableText',
        ], $config);
    }

    public static function richTextField($config = [])
    {
        return array_merge([
            'type' => 'richText',
        ], $config);
    }


    // Reusable
    // =========================================================================

    public static function labelField($config = [])
    {
        return array_merge(self::textField([
            'label' => Craft::t('formie', 'Label'),
            'help' => Craft::t('formie', 'The label that describes this field.'),
            'name' => 'label',
            'validation' => 'required',
            'required' => true,
        ]), $config);
    }

    public static function handleField($config = [])
    {
        return array_merge([
            'label' => Craft::t('formie', 'Handle'),
            'help' => Craft::t('formie', 'How you’ll refer to this field in your templates. Use the refresh icon to re-generate this from your field label.'),
            'warning' => Craft::t('formie', 'Changing this may result in your field not working as expected.'),
            'type' => 'handle',
            'name' => 'handle',
            'validation' => 'required|uniqueHandle',
            'required' => true,
            'autocomplete' => 'off',
        ], $config);
    }

    public static function labelPosition(FormFieldInterface $field, $config = [])
    {
        return array_merge(self::selectField([
            'label' => Craft::t('formie', 'Label Position'),
            'help' => Craft::t('formie', 'How the label for the field should be positioned.'),
            'name' => 'labelPosition',
            'options' => array_merge(
                [['label' => Craft::t('formie', 'Form Default'), 'value' => '']],
                Formie::$plugin->getFields()->getLabelPositionsArray($field)
            ),
        ]), $config);
    }

    public static function subfieldLabelPosition($config = [])
    {
        return array_merge(self::selectField([
            'label' => Craft::t('formie', 'Subfield Label Position'),
            'help' => Craft::t('formie', 'How the label for the subfields should be positioned.'),
            'name' => 'subfieldLabelPosition',
            'options' => array_merge(
                [['label' => Craft::t('formie', 'Form Default'), 'value' => '']],
                Formie::$plugin->getFields()->getLabelPositionsArray()
            ),
        ]), $config);
    }

    public static function instructions($config = [])
    {
        return array_merge(self::textareaField([
            'label' => Craft::t('formie', 'Instructions'),
            'help' => Craft::t('formie', 'Instructions to guide the user when filling out this form.'),
            'name' => 'instructions',
        ]), $config);
    }

    public static function instructionsPosition(FormFieldInterface $field, $config = [])
    {
        return array_merge(self::selectField([
            'label' => Craft::t('formie', 'Instructions Position'),
            'help' => Craft::t('formie', 'How the instructions for the field should be positioned.'),
            'name' => 'instructionsPosition',
            'options' => array_merge(
                [['label' => Craft::t('formie', 'Form Default'), 'value' => '']],
                Formie::$plugin->getFields()->getInstructionsPositionsArray($field)
            ),
        ]), $config);
    }

    public static function cssClasses($config = [])
    {
        return array_merge(self::textField([
            'label' => Craft::t('formie', 'CSS Classes'),
            'help' => Craft::t('formie', 'Add classes to be outputted on this field’s container.'),
            'name' => 'cssClasses',
        ]), $config);
    }

    public static function containerAttributesField($config = [])
    {
        return array_merge(self::tableField([
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
                ]
            ],
            'name' => 'containerAttributes',
        ]), $config);
    }

    public static function inputAttributesField($config = [])
    {
        return array_merge(self::tableField([
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
                ]
            ],
            'name' => 'inputAttributes',
        ]), $config);
    }

    public static function prePopulate($config = [])
    {
        return array_merge(self::textField([
            'label' => Craft::t('formie', 'Pre-Populate Value'),
            'help' => Craft::t('formie', 'Specify a query parameter to pre-populate the value of this field.'),
            'name' => 'prePopulate',
        ]), $config);
    }

    public static function extractFieldsFromSchema($fieldSchema, $names = [])
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

    public static function extractFieldInfoFromSchema($fieldSchema, &$names = [])
    {
        foreach ($fieldSchema as $field) {
            if (isset($field['name'])) {
                $names[$field['name']] = $field;
            }

            if (isset($field['children'])) {
                self::extractFieldInfoFromSchema($field['children'], $names);
            }
        }

        return $names;
    }

    public static function setFieldValidationName(&$fieldSchema)
    {
        foreach ($fieldSchema as &$field) {
            if (isset($field['name']) && isset($field['label'])) {
                $field['validationName'] = $field['label'] ?? '';
            }

            if (isset($field['children'])) {
                self::setFieldValidationName($field['children']);
            }
        }
    }
}
