<?php
namespace verbb\formie\helpers;

use verbb\formie\base\FieldInterface;
use verbb\formie\Formie;

use Craft;
use craft\helpers\Html;
use craft\helpers\StringHelper;

class SchemaHelper
{
    // Static Methods
    // =========================================================================

    public static function textField(array $config = []): array
    {
        return array_merge([
            '$formkit' => 'text',
            'inputClass' => 'text fullwidth',
            'autocomplete' => 'off',
        ], $config);
    }

    public static function textareaField(array $config = []): array
    {
        return array_merge([
            '$formkit' => 'textarea',
            'inputClass' => 'text fullwidth',
        ], $config);
    }

    public static function selectField(array $config = []): array
    {
        return array_merge([
            '$formkit' => 'select',
        ], $config);
    }

    public static function multiSelectField(array $config = []): array
    {
        return array_merge([
            '$formkit' => 'multiSelect',
        ], $config);
    }

    public static function numberField(array $config = []): array
    {
        return array_merge([
            '$formkit' => 'number',
            'size' => '3',
            'inputClass' => 'text',
            'validation' => 'number|min:0',
        ], $config);
    }

    public static function dateField(array $config = []): array
    {
        return array_merge([
            '$formkit' => 'date',
            'inputClass' => 'text fullwidth',
        ], $config);
    }

    public static function checkboxSelectField(array $config = []): array
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

    public static function checkboxField(array $config = []): array
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

    public static function lightswitchField(array $config = []): array
    {
        return array_merge([
            '$formkit' => 'lightswitch',
            'labelPosition' => 'before',
        ], $config);
    }

    public static function toggleBlocks(array $config, array $children = []): array
    {
        return array_merge([
            '$formkit' => 'toggleBlocks',
            'validation' => 'minBlock:2',
            'children' => $children,
        ], $config);
    }

    public static function toggleBlock(array $config, array $children = []): array
    {
        return [
            '$cmp' => 'ToggleBlock',
            'props' => $config,
            'children' => $children,
        ];
    }

    public static function tableField(array $config = []): array
    {
        return array_merge([
            '$formkit' => 'table',
            'validation' => '+min:1|uniqueTableCellLabel|uniqueTableCellValue',
        ], $config);
    }

    public static function variableTextField(array $config = []): array
    {
        return array_merge([
            '$formkit' => 'variableText',
        ], $config);
    }

    public static function richTextField(array $config = []): array
    {
        return array_merge([
            '$formkit' => 'richText',
        ], $config);
    }

    public static function elementSelectField(array $config = []): array
    {
        return array_merge([
            '$formkit' => 'elementSelect',
        ], $config);
    }


    // Reusable
    // =========================================================================

    public static function labelField(array $config = []): array
    {
        return self::textField(array_merge([
            'label' => Craft::t('formie', 'Label'),
            'help' => Craft::t('formie', 'The label that describes this field.'),
            'name' => 'label',
            'validation' => 'required',
            'required' => true,
        ], $config));
    }

    public static function handleField(array $config = []): array
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

    public static function labelPosition(FieldInterface $field, array $config = []): array
    {
        return self::selectField(array_merge([
            'label' => Craft::t('formie', 'Label Position'),
            'help' => Craft::t('formie', 'How the label for the field should be positioned.'),
            'name' => 'labelPosition',
            'options' => array_merge(
                [['label' => Craft::t('formie', 'Form Default'), 'value' => '']],
                Formie::$plugin->getFields()->getLabelPositionsOptions($field)
            ),
        ], $config));
    }

    public static function subFieldLabelPosition(array $config = []): array
    {
        return self::selectField(array_merge([
            'label' => Craft::t('formie', 'Subfield Label Position'),
            'help' => Craft::t('formie', 'How the label for the subfields should be positioned.'),
            'name' => 'subFieldLabelPosition',
            'options' => array_merge(
                [['label' => Craft::t('formie', 'Form Default'), 'value' => '']],
                Formie::$plugin->getFields()->getLabelPositionsOptions()
            ),
        ], $config));
    }

    public static function instructions(array $config = []): array
    {
        return self::textareaField(array_merge([
            'label' => Craft::t('formie', 'Instructions'),
            'help' => Craft::t('formie', 'Instructions to guide the user when filling out this form.'),
            'name' => 'instructions',
            'rows' => '4',
        ], $config));
    }

    public static function instructionsPosition(FieldInterface $field, array $config = []): array
    {
        return self::selectField(array_merge([
            'label' => Craft::t('formie', 'Instructions Position'),
            'help' => Craft::t('formie', 'How the instructions for the field should be positioned.'),
            'name' => 'instructionsPosition',
            'options' => array_merge(
                [['label' => Craft::t('formie', 'Form Default'), 'value' => '']],
                Formie::$plugin->getFields()->getInstructionsPositionsOptions($field)
            ),
        ], $config));
    }

    public static function cssClasses(array $config = []): array
    {
        return self::textField(array_merge([
            'label' => Craft::t('formie', 'CSS Classes'),
            'help' => Craft::t('formie', 'Add classes to be outputted on this field’s container.'),
            'name' => 'cssClasses',
        ], $config));
    }

    public static function containerAttributesField(array $config = []): array
    {
        return self::tableField(array_merge([
            'label' => Craft::t('formie', 'Container Attributes'),
            'help' => Craft::t('formie', 'Add attributes to be outputted on this field’s container.'),
            'name' => 'containerAttributes',
            'validation' => '',
            'generateValue' => false,
            'newRowDefaults' => [
                'label' => '',
                'value' => '',
            ],
            'columns' => [
                [
                    'type' => 'label',
                    'label' => Craft::t('formie', 'Name'),
                    'class' => 'singleline-cell textual',
                ],
                [
                    'type' => 'value',
                    'label' => Craft::t('formie', 'Value'),
                    'class' => 'code singleline-cell textual',
                ],
            ],
        ], $config));
    }

    public static function inputAttributesField(array $config = []): array
    {
        return self::tableField(array_merge([
            'label' => Craft::t('formie', 'Input Attributes'),
            'help' => Craft::t('formie', 'Add attributes to be outputted on this field’s input.'),
            'name' => 'inputAttributes',
            'validation' => '',
            'generateValue' => false,
            'newRowDefaults' => [
                'label' => '',
                'value' => '',
            ],
            'columns' => [
                [
                    'type' => 'label',
                    'label' => Craft::t('formie', 'Name'),
                    'class' => 'singleline-cell textual',
                ],
                [
                    'type' => 'value',
                    'label' => Craft::t('formie', 'Value'),
                    'class' => 'code singleline-cell textual',
                ],
            ],
        ], $config));
    }

    public static function prePopulate(array $config = []): array
    {
        return self::textField(array_merge([
            'label' => Craft::t('formie', 'Pre-Populate Value'),
            'help' => Craft::t('formie', 'Specify a query parameter to pre-populate the value of this field.'),
            'name' => 'prePopulate',

            // Disable pre-population in fields nested in Repeater
            'if' => '$isInRepeater === false',
        ], $config));
    }

    public static function enableConditionsField(array $config = []): array
    {
        return self::lightswitchField(array_merge([
            'label' => Craft::t('formie', 'Enable Conditions'),
            'help' => Craft::t('formie', 'Whether to enable conditional logic to control how this field is shown.'),
            'name' => 'enableConditions',
        ], $config));
    }

    public static function conditionsField(array $config = []): array
    {
        return array_merge([
            '$formkit' => 'fieldConditions',
            'name' => 'conditions',
            'if' => '$get(enableConditions).value',
        ], $config);
    }

    public static function enableContentEncryptionField(array $config = []): array
    {
        return self::lightswitchField(array_merge([
            'label' => Craft::t('formie', 'Enable Content Encryption'),
            'help' => Craft::t('formie', 'Whether to encrypt the value saved for this field for data-security purposes.'),
            'name' => 'enableContentEncryption',
        ], $config));
    }

    public static function includeInEmailField(array $config = []): array
    {
        return self::lightswitchField(array_merge([
            'label' => Craft::t('formie', 'Include in Email Notifications'),
            'help' => Craft::t('formie', 'Whether the value of this field should be included in email notifications.'),
            'name' => 'includeInEmail',
        ], $config));
    }

    public static function emailNotificationValue(array $config = []): array
    {
        return self::selectField(array_merge([
            'label' => Craft::t('formie', 'Email Notification Value'),
            'help' => Craft::t('formie', 'Select what value to use for email notifications.'),
            'name' => 'emailValue',
            'options' => [
                ['label' => Craft::t('formie', 'Public URL'), 'value' => 'publicUrl'],
                ['label' => Craft::t('formie', 'Control Panel URL'), 'value' => 'cpUrl'],
            ],
        ], $config));
    }

    public static function visibility(array $config = []): array
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

    public static function columnTypeField(array $config = []): array
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

    public static function fieldSelectField(array $config = []): array
    {
        return array_merge([
            '$formkit' => 'fieldSelect',
            'excludeSelf' => true,
        ], $config);
    }

    public static function matchField(array $config = []): array
    {
        return array_merge([
            '$formkit' => 'fieldSelect',
            'label' => Craft::t('formie', 'Match Field'),
            'help' => Craft::t('formie', 'Select a field of the same type where its value must match this field.'),
            'name' => 'matchField',
            'excludeSelf' => true,
        ], $config);
    }

    public static function subFieldsConfigurationField(array $config = [], array $childConfig = []): array
    {
        return array_merge([
            '$formkit' => 'subFields',
            'label' => Craft::t('formie', 'Sub-Field Configuration'),
            'help' => Craft::t('formie', 'Configure the sub-fields for this field. Move to rearrange columns and rows, and click to edit sub-field settings.'),
            'name' => 'rows',
            'children' => [
                [
                    '$cmp' => 'SubFields',
                    'props' => array_merge([
                        'context' => '$node.context',
                    ], $childConfig),
                ],
            ],
        ], $config);
    }

    public static function customSettingsField(array $children = []): array
    {
        return [
            '$formkit' => 'group',
            'name' => 'customSettings',
            'children' => $children,
        ];
    }

    public static function tab(string $label, array $fields): array
    {
        return [
            'label' => $label,
            'fields' => static::extractFieldsFromSchema($fields),
        ];
    }

    public static function tabPanel(string $label, array $fields): array
    {
        return [
            '$cmp' => 'TabPanel',
            'attrs' => [
                'data-tab-panel' => $label,
            ],
            'children' => $fields,
        ];
    }

    public static function extractFieldsFromSchema(array $fieldSchema, array $names = []): array
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

    public static function setFieldAttributes(array &$fieldSchema): void
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

    public static function renderElementSelect(string $handle, array $elements, array $config): array
    {
        $view = Craft::$app->getView();

        $config['id'] = Html::id($handle . '-' . StringHelper::randomString(10));
        $config['name'] = $handle;
        $config['elements'] = $elements;

        $view->startJsBuffer();
        $html = $view->renderTemplate('_includes/forms/elementSelect', $config);
        $js = $view->clearJsBuffer();

        return [
            ('__elementSelectHtml_' . $handle) => $html,
            ('__elementSelectJs_' . $handle) => $js,
        ];
    }
}
