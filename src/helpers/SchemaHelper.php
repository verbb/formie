<?php
namespace verbb\formie\helpers;

use verbb\formie\base\FieldInterface;
use verbb\formie\compatibility\schema\SchemaCompatibility;
use verbb\formie\Formie;

use Craft;
use craft\helpers\Html;
use craft\helpers\StringHelper;

class SchemaHelper
{
    // Static Methods
    // =========================================================================

    public static function normalizeSchema($schema)
    {
        if (is_array($schema)) {
            if (array_is_list($schema)) {
                return array_map([self::class, 'normalizeSchema'], $schema);
            }

            $node = $schema;

            if (isset($node['children'])) {
                $children = $node['children'];
                if (!is_array($children) || !array_is_list($children)) {
                    $children = [$children];
                }
                $node['children'] = array_map([self::class, 'normalizeSchema'], $children);
            }

            if (isset($node['schema'])) {
                $schemaChildren = $node['schema'];
                if (!is_array($schemaChildren) || !array_is_list($schemaChildren)) {
                    $schemaChildren = [$schemaChildren];
                }
                $node['schema'] = array_map([self::class, 'normalizeSchema'], $schemaChildren);
            }

            if (isset($node['$field']) && isset($node['name']) && (isset($node['schema']) || isset($node['children'])) && !isset($node['schemaChildPrefix'])) {
                if (in_array($node['$field'], ['list', 'table', 'formieTableColumns', 'formieTableDefaults'], true)) {
                    $node['schemaChildPrefix'] = "{$node['name']}.*.";
                }

                if ($node['$field'] === 'group') {
                    $node['schemaChildPrefix'] = "{$node['name']}.";
                }
            }

            $node = self::applyPluginKitReactDefaults($node);
            $node = SchemaCompatibility::normalizeLegacyNode($node);

            return $node;
        }

        return $schema;
    }

    public static function compileSchema($schema): array
    {
        $normalized = self::normalizeSchema($schema);
        $entries = [];
        self::collectSchemaFields($normalized, '', $entries);

        return [
            'schema' => $normalized,
            'fieldEntries' => $entries,
        ];
    }

    public static function textField(array $config = []): array
    {
        return array_merge([
            '$field' => 'text',
            'autocomplete' => 'off',
        ], $config);
    }

    public static function textareaField(array $config = []): array
    {
        return array_merge([
            '$field' => 'textarea',
        ], $config);
    }

    public static function selectField(array $config = []): array
    {
        return array_merge([
            '$field' => 'select',
        ], $config);
    }

    public static function comboboxField(array $config = []): array
    {
        return array_merge([
            '$field' => 'combobox',
        ], $config);
    }

    public static function autocompleteField(array $config = []): array
    {
        return self::comboboxField(array_merge([
            'label' => Craft::t('formie', 'Autocomplete'),
            'instructions' => Craft::t('formie', 'Set the [HTML autocomplete attribute](https://developer.mozilla.org/en-US/docs/Web/HTML/Attributes/autocomplete) for this field’s input. Use “Default (browser)” to omit the attribute, or add sectioned values such as `billing email` via Input Attributes in Advanced.'),
            'name' => 'autocomplete',
            'placeholder' => Craft::t('formie', 'Select or type a value…'),
            'options' => HtmlAutocomplete::getOptions(),
        ], $config));
    }

    public static function numberField(array $config = []): array
    {
        return array_merge([
            '$field' => 'number',
            'size' => 5,
        ], $config);
    }

    public static function dateField(array $config = []): array
    {
        return array_merge([
            '$field' => 'date',
        ], $config);
    }

    public static function checkboxSelectField(array $config = []): array
    {
        return array_merge([
            '$field' => 'checkboxSelect',
        ], $config);
    }

    public static function checkboxField(array $config = []): array
    {
        return array_merge([
            '$field' => 'checkbox',
        ], $config);
    }

    public static function lightswitchField(array $config = []): array
    {
        return array_merge([
            '$field' => 'lightswitch',
        ], $config);
    }

    public static function inheritBooleanField(array $config = []): array
    {
        return array_merge([
            '$field' => 'select',
            'options' => [
                ['label' => Craft::t('formie', 'Inherit'), 'value' => ''],
                ['label' => Craft::t('app', 'Yes'), 'value' => '1'],
                ['label' => Craft::t('app', 'No'), 'value' => '0'],
            ],
        ], $config);
    }

    public static function colorField(array $config = []): array
    {
        return array_merge([
            '$field' => 'color',
        ], $config);
    }

    public static function tableField(array $config = []): array
    {
        $field = array_merge([
            '$field' => 'table',
        ], $config);

        if (isset($field['columns']) && !isset($field['schema'])) {
            $field['schema'] = self::tableSchemaFromColumns($field['columns']);
        }

        return $field;
    }

    public static function staticTableField(array $config = []): array
    {
        $field = array_merge([
            '$field' => 'staticTable',
        ], $config);

        if (isset($field['columns']) && !isset($field['schema'])) {
            $field['schema'] = self::tableSchemaFromColumns($field['columns']);
        }

        return $field;
    }

    public static function tableSchemaFromColumns(array $columns = []): array
    {
        return array_values(array_filter(array_map(function($column) {
            if (!is_array($column) || empty($column['name'])) {
                return null;
            }

            $type = $column['type'] ?? 'text';

            $field = [
                '$field' => $type,
                'name' => $column['name'],
                'label' => $column['label'] ?? $column['name'],
            ];

            if (!empty($column['required'])) {
                $field['required'] = true;
            }

            if (isset($column['options'])) {
                $field['options'] = $column['options'];
            }

            if (isset($column['validation'])) {
                $field['validation'] = $column['validation'];
            }

            return $field;
        }, $columns)));
    }

    public static function schemaNode(array $config = []): array
    {
        return self::normalizeSchema($config);
    }

    public static function normalizePreviewSchema(array $preview = []): array
    {
        if ($preview === []) {
            return [];
        }

        if (!array_is_list($preview)) {
            $preview = [$preview];
        }

        return array_values(array_map([self::class, 'normalizePreviewNode'], $preview));
    }

    public static function previewNode(string $componentName, array $config = []): array
    {
        return array_merge([
            '$cmp' => $componentName,
        ], $config);
    }

    public static function previewBind(string $path, mixed $fallback = null): array
    {
        return [
            '$bind' => $path,
            'fallback' => $fallback,
        ];
    }

    public static function previewInput(array $config = []): array
    {
        return self::previewNode('PreviewInput', array_merge([
            'type' => 'text',
            'placeholder' => self::previewBind('field.placeholder', ''),
            'value' => self::previewBind('field.defaultValue', ''),
            'icon' => self::previewBind('fieldType.icon', ''),
            'wrapperClassName' => 'formie-field-preview-control',
            'className' => 'formie-field-preview-input',
            'readOnly' => true,
        ], $config));
    }

    public static function previewTextarea(array $config = []): array
    {
        return self::previewNode('PreviewTextarea', array_merge([
            'placeholder' => self::previewBind('field.placeholder', ''),
            'value' => self::previewBind('field.defaultValue', ''),
            'icon' => self::previewBind('fieldType.icon', ''),
            'wrapperClassName' => 'formie-field-preview-control formie-field-preview-control--multiline',
            'className' => 'formie-field-preview-input formie-field-preview-textarea',
            'readOnly' => true,
        ], $config));
    }

    public static function previewFileInput(array $config = []): array
    {
        return self::previewNode('PreviewInput', array_merge([
            'type' => 'file',
            'value' => null,
            'icon' => self::previewBind('fieldType.icon', ''),
            'wrapperClassName' => 'formie-field-preview-control formie-field-preview-control--file',
            'className' => 'formie-field-preview-input formie-field-preview-file',
        ], $config));
    }

    public static function previewSelect(array $config = []): array
    {
        return self::previewNode('PreviewSelect', array_merge([
            'options' => self::previewBind('field.options', []),
            'placeholder' => self::previewBind('field.placeholder', ''),
            'value' => self::previewBind('field.defaultValue', null),
            'multiple' => self::previewBind('field.multi', false),
            'useOptionDefaults' => true,
            'showPlaceholderOption' => true,
            'className' => 'formie-field-preview-select',
        ], $config));
    }

    public static function previewChoiceList(string $choiceType, array $config = []): array
    {
        return self::previewNode('PreviewChoiceList', array_merge([
            'choiceType' => $choiceType,
            'options' => self::previewBind('field.options', []),
            'value' => self::previewBind('field.defaultValue', null),
            'layout' => self::previewBind('field.layout', 'vertical'),
            'visibleLimit' => 5,
            'useOptionDefaults' => true,
        ], $config));
    }

    public static function previewContainerParent(array $config = []): array
    {
        return self::previewNode('PreviewContainerParent', array_merge([
            'rows' => self::previewBind('field.rows', []),
            'showFallbackControl' => true,
        ], $config));
    }

    public static function previewElementField(array $config = []): array
    {
        return self::previewNode('PreviewElementField', $config);
    }

    public static function previewPhone(array $config = []): array
    {
        return self::previewNode('PreviewPhone', $config);
    }

    public static function previewPayment(array $config = []): array
    {
        return self::previewNode('PreviewPayment', $config);
    }

    public static function previewTable(array $config = []): array
    {
        return self::previewNode('PreviewTable', $config);
    }

    public static function previewRepeater(array $config = []): array
    {
        return self::previewMessage(Craft::t('formie', 'Repeater'), array_merge([
            'className' => 'formie-field-preview-input',
        ], $config));
    }

    public static function previewMessage(string $message, array $config = []): array
    {
        return self::previewNode('PreviewMessage', array_merge([
            'message' => $message,
            'className' => 'formie-field-preview-input',
        ], $config));
    }

    public static function previewRichText(array $config = []): array
    {
        return self::previewNode('PreviewRichText', array_merge([
            'value' => self::previewBind('field.description', ''),
        ], $config));
    }

    public static function previewHtml(array $config = []): array
    {
        return self::previewNode('PreviewHtml', array_merge([
            'html' => self::previewBind('field.htmlContent', ''),
        ], $config));
    }

    public static function previewHeading(array $config = []): array
    {
        return self::previewNode('PreviewHeading', array_merge([
            'level' => self::previewBind('field.headingSize', 'h2'),
            'text' => self::previewBind('field.label', ''),
        ], $config));
    }

    public static function previewGroup(array $config = []): array
    {
        return self::previewNode('PreviewGroup', array_merge([
            'label' => self::previewBind('field.label', Craft::t('formie', 'Field Group')),
        ], $config));
    }

    public static function previewSection(array $config = []): array
    {
        return self::previewNode('PreviewSection', $config);
    }

    public static function previewSignature(array $config = []): array
    {
        return self::previewNode('PreviewSignature', $config);
    }

    public static function previewSummary(array $config = []): array
    {
        return self::previewNode('PreviewSummary', array_merge([
            'description' => self::previewBind('field.description', ''),
            'message' => Craft::t('formie', 'A summary of your field content will be displayed here.'),
        ], $config));
    }

    public static function previewAgree(array $config = []): array
    {
        return self::previewNode('PreviewAgree', array_merge([
            'checked' => self::previewBind('field.defaultValue', false),
            'description' => self::previewBind('field.description', ''),
        ], $config));
    }

    public static function previewRecipients(array $config = []): array
    {
        return self::previewNode('PreviewRecipients', array_merge([
            'displayType' => self::previewBind('field.displayType', 'hidden'),
            'placeholder' => self::previewBind('field.placeholder', Craft::t('formie', 'Recipient')),
            'options' => self::previewBind('field.options', []),
            'value' => self::previewBind('field.defaultValue', ''),
            'layout' => self::previewBind('field.layout', 'vertical'),
        ], $config));
    }

    public static function previewLegacyTemplateNotice(array $config = []): array
    {
        return self::previewNode('PreviewLegacyTemplateNotice', array_merge([
            'title' => Craft::t('formie', 'Legacy field preview requires migration.'),
            'message' => Craft::t('formie', 'This field uses a legacy preview template. Replace it with `defineFormBuilderPreviewSchema()` to restore builder previews.'),
        ], $config));
    }

    public static function variableTextField(array $config = []): array
    {
        return array_merge([
            '$field' => 'variablePicker',
        ], $config);
    }

    public static function richTextField(array $config = []): array
    {
        return array_merge([
            '$field' => 'richText',
            'rows' => 6,
        ], $config);
    }

    public static function calculationsField(array $config = []): array
    {
        return array_merge([
            '$field' => 'calculations',
            'rows' => 8,
        ], $config);
    }

    public static function elementSelectField(array $config = []): array
    {
        return array_merge([
            '$field' => 'elementSelect',
        ], $config);
    }

    public static function fieldSelectField(array $config = []): array
    {
        return array_merge([
            '$field' => 'fieldSelect',
        ], $config);
    }

    public static function integrationFieldMappingField(array $config = []): array
    {
        return array_merge([
            '$field' => 'integrationFieldMapping',
            'showRefreshButton' => true,
        ], $config);
    }

    public static function optionDynamicSettingsField(array $config = []): array
    {
        return array_merge([
            '$field' => 'optionDynamicSettings',
        ], $config);
    }

    public static function optionSourceSettingsField(array $config = []): array
    {
        return self::optionDynamicSettingsField($config);
    }

    public static function integrationRefreshSelectField(array $config = []): array
    {
        return array_merge([
            '$field' => 'integrationRefreshSelect',
        ], $config);
    }

    public static function integrationRefreshComboboxField(array $config = []): array
    {
        return array_merge([
            '$field' => 'integrationRefreshCombobox',
            'combobox' => true,
        ], $config);
    }

    public static function integrationRefreshButtonField(array $config = []): array
    {
        return array_merge([
            '$field' => 'integrationRefreshButton',
            'actionType' => 'refresh',
            'buttonLabel' => Craft::t('formie', 'Refresh Data'),
        ], $config);
    }

    public static function integrationSendTestPayloadButtonField(array $config = []): array
    {
        return array_merge([
            '$field' => 'integrationSendTestPayloadButton',
            'actionType' => 'testPayload',
            'buttonLabel' => Craft::t('formie', 'Send Test Payload'),
        ], $config);
    }

    public static function paymentProviderSettingsField(array $config = []): array
    {
        return array_merge([
            '$field' => 'paymentProviderSettings',
        ], $config);
    }

    public static function groupField(array $config = []): array
    {
        return array_merge([
            '$field' => 'group',
        ], $config);
    }

    public static function fieldWrap(array $config = []): array
    {
        $children = ArrayHelper::remove($config, 'children', []);

        return array_merge([
            '$cmp' => 'FieldWrap',
            'children' => [
                [
                    '$el' => 'div',
                    'attrs' => [
                        'style' => [
                            'display' => 'flex',
                            'alignItems' => 'baseline',
                            'gap' => '0.5rem',
                        ],
                    ],
                    'children' => $children,
                ],
            ],
        ], $config);
    }


    // Reusable
    // =========================================================================

    public static function labelField(array $config = []): array
    {
        return self::textField(array_merge([
            'label' => Craft::t('formie', 'Label'),
            'instructions' => Craft::t('formie', 'The label that describes this field.'),
            'name' => 'label',
            'validation' => 'required',
            'required' => true,
        ], $config));
    }

    public static function handleField(array $config = []): array
    {
        return array_merge([
            '$field' => 'handle',
            'label' => Craft::t('formie', 'Handle'),
            'instructions' => Craft::t('formie', 'How you’ll refer to this field in your templates. Use the refresh icon to re-generate this from your field label.'),
            'warning' => Craft::t('formie', 'Changing this may result in your field not working as expected.'),
            'name' => 'handle',
            'validation' => 'required|handle|uniqueHandle',
            'required' => true,
            'source' => 'label',
            'syncFromSource' => true,
            'persistedIdPath' => 'id',
            'autocomplete' => 'off',
            'maxLength' => HandleHelper::getMaxFieldHandle(),
        ], $config);
    }

    public static function labelPosition(FieldInterface $field, array $config = []): array
    {
        return self::selectField(array_merge([
            'label' => Craft::t('formie', 'Label Position'),
            'instructions' => Craft::t('formie', 'How the label for the field should be positioned.'),
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
            'instructions' => Craft::t('formie', 'How the label for the subfields should be positioned.'),
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
            'instructions' => Craft::t('formie', 'Instructions to guide the user when filling out this form.'),
            'name' => 'instructions',
            'rows' => '4',
        ], $config));
    }

    public static function instructionsPosition(FieldInterface $field, array $config = []): array
    {
        return self::selectField(array_merge([
            'label' => Craft::t('formie', 'Instructions Position'),
            'instructions' => Craft::t('formie', 'How the instructions for the field should be positioned.'),
            'name' => 'instructionsPosition',
            'options' => array_merge(
                [['label' => Craft::t('formie', 'Form Default'), 'value' => '']],
                Formie::$plugin->getFields()->getInstructionsPositionsOptions($field)
            ),
        ], $config));
    }

    public static function errorMessagePosition(FieldInterface $field, array $config = []): array
    {
        return self::selectField(array_merge([
            'label' => Craft::t('formie', 'Field Error Position'),
            'instructions' => Craft::t('formie', 'How validation error messages for this field should be positioned relative to the input.'),
            'name' => 'errorMessagePosition',
            'options' => array_merge(
                [['label' => Craft::t('formie', 'Form Default'), 'value' => '']],
                Formie::$plugin->getFields()->getErrorMessagePositionsOptions($field)
            ),
        ], $config));
    }

    public static function cssClasses(array $config = []): array
    {
        return self::textField(array_merge([
            'label' => Craft::t('formie', 'CSS Classes'),
            'instructions' => Craft::t('formie', 'Add classes to be outputted on this field’s container.'),
            'name' => 'cssClasses',
        ], $config));
    }

    public static function containerAttributesField(array $config = []): array
    {
        return self::tableField(array_merge([
            'label' => Craft::t('formie', 'Container Attributes'),
            'instructions' => Craft::t('formie', 'Add attributes to be outputted on this field’s container.'),
            'name' => 'containerAttributes',
            'columns' => [
                [
                    'type' => 'text',
                    'name' => 'label',
                    'label' => Craft::t('formie', 'Name'),
                    'required' => true,
                ],
                [
                    'type' => 'value',
                    'name' => 'value',
                    'label' => Craft::t('formie', 'Value'),
                ],
            ],
            'schemaChildPrefix' => 'containerAttributes.*.',
        ], $config));
    }

    public static function inputAttributesField(array $config = []): array
    {
        return self::tableField(array_merge([
            'label' => Craft::t('formie', 'Input Attributes'),
            'instructions' => Craft::t('formie', 'Add attributes to be outputted on this field’s input.'),
            'name' => 'inputAttributes',
            'columns' => [
                [
                    'type' => 'text',
                    'name' => 'label',
                    'label' => Craft::t('formie', 'Name'),
                    'required' => true,
                ],
                [
                    'type' => 'value',
                    'name' => 'value',
                    'label' => Craft::t('formie', 'Value'),
                ],
            ],
            'schemaChildPrefix' => 'inputAttributes.*.',
        ], $config));
    }

    public static function prePopulate(array $config = []): array
    {
        return self::textField(array_merge([
            'label' => Craft::t('formie', 'Prefill Query Parameter'),
            'instructions' => Craft::t('formie', 'Specify the query parameter name used to prefill this field’s initial value.'),
            'name' => 'prePopulate',

            // Disable pre-population in fields nested in Repeater
            // 'if' => '$isInRepeater === false',
        ], $config));
    }

    public static function enableConditionsField(array $config = []): array
    {
        return self::lightswitchField(array_merge([
            'label' => Craft::t('formie', 'Enable Conditions'),
            'instructions' => Craft::t('formie', 'Whether to enable conditional logic to control how this field is shown.'),
            'name' => 'enableConditions',
        ], $config));
    }

    public static function conditionsField(array $config = []): array
    {
        return array_merge([
            '$field' => 'fieldConditions',
            'name' => 'conditions',
            'if' => 'enableConditions',
            'fieldOptions' => ConditionsHelper::getConditionFieldOptions(),
            'conditionOptions' => ConditionsHelper::getConditionOptions(),
        ], $config);
    }

    public static function enableContentEncryptionField(array $config = []): array
    {
        return self::lightswitchField(array_merge([
            'label' => Craft::t('formie', 'Enable Content Encryption'),
            'instructions' => Craft::t('formie', 'Whether to encrypt the value saved for this field for data-security purposes.'),
            'name' => 'enableContentEncryption',
        ], $config));
    }

    public static function includeInEmailFieldSummariesField(array $config = []): array
    {
        return self::lightswitchField(array_merge([
            'label' => Craft::t('formie', 'Include in Email Field Summaries'),
            'instructions' => Craft::t('formie', 'Whether this field should be included when using "All Form Fields", "All Non Empty Fields", or "All Visible Fields" in email notifications.'),
            'name' => 'includeInEmailFieldSummaries',
        ], $config));
    }

    public static function includeInEmailField(array $config = []): array
    {
        return self::includeInEmailFieldSummariesField($config);
    }

    public static function emailFieldSummaryValue(array $config = []): array
    {
        return self::selectField(array_merge([
            'label' => Craft::t('formie', 'Email Field Summary Value'),
            'instructions' => Craft::t('formie', 'Choose which value should be used for this field in email field summaries.'),
            'name' => 'emailFieldSummaryValue',
            'options' => [
                ['label' => Craft::t('formie', 'Public URL'), 'value' => 'publicUrl'],
                ['label' => Craft::t('formie', 'Control Panel URL'), 'value' => 'cpUrl'],
            ],
        ], $config));
    }

    public static function emailNotificationValue(array $config = []): array
    {
        return self::emailFieldSummaryValue($config);
    }

    public static function visibility(array $config = []): array
    {
        return self::selectField(array_merge([
            'label' => Craft::t('formie', 'Visibility'),
            'instructions' => Craft::t('formie', 'The visibility of the field on the front-end.'),
            'info' => Craft::t('formie', 'A “Hidden” field will be hidden from view, but still rendered. A “Disabled” field will not be rendered on the page at all.'),
            'name' => 'visibility',
            'options' => [
                ['label' => Craft::t('formie', 'Visible'), 'value' => ''],
                ['label' => Craft::t('formie', 'Hidden'), 'value' => 'hidden'],
                ['label' => Craft::t('formie', 'Disabled'), 'value' => 'disabled'],
            ],
        ], $config));
    }

    public static function matchField(array $config = []): array
    {
        return self::fieldSelectField(array_merge([
            'label' => Craft::t('formie', 'Match Field'),
            'instructions' => Craft::t('formie', 'Select a field of the same type where its value must match this field.'),
            'name' => 'matchField',
            'referenceContext' => 'client',
            'excludeSelf' => true,
        ], $config));
    }

    public static function validationMessageField(array $config = []): array
    {
        $tokens = $config['tokens'] ?? ValidationMessagesHelper::allowedTokens();
        $messageKey = ArrayHelper::remove($config, 'messageKey');
        unset($config['tokens']);

        $label = $config['label'] ?? null;

        if ($label === null && is_string($messageKey) && $messageKey !== '') {
            $label = ValidationMessagesHelper::builderLabel($messageKey);
        }

        if ($label === null) {
            $label = Craft::t('formie', 'Error Message');
        }

        return self::textField(array_merge([
            'label' => $label,
            'instructions' => ValidationMessagesHelper::tokenInstructions($tokens),
        ], $config));
    }

    public static function requiredField(array $config = []): array
    {
        return self::lightswitchField(array_merge([
            'label' => Craft::t('formie', 'Required Field'),
            'instructions' => Craft::t('formie', 'Whether this field should be required when filling out the form.'),
            'name' => 'required',
        ], $config));
    }

    public static function requiredValidationMessage(array $config = []): array
    {
        return self::validationMessageField(array_merge([
            'messageKey' => ValidationMessagesHelper::KEY_REQUIRED,
            'name' => 'validationMessages.required',
            'if' => 'required',
            'tokens' => ['label'],
        ], $config));
    }

    public static function uniqueValueField(array $config = []): array
    {
        return self::lightswitchField(array_merge([
            'label' => Craft::t('formie', 'Unique Value'),
            'instructions' => Craft::t('formie', 'Whether to limit user input to unique values only. This will require that a value entered in this field does not already exist in a submission for this field and form.'),
            'name' => 'uniqueValue',
        ], $config));
    }

    public static function uniqueValidationMessage(array $config = []): array
    {
        return self::validationMessageField(array_merge([
            'messageKey' => ValidationMessagesHelper::KEY_UNIQUE,
            'name' => 'validationMessages.unique',
            'if' => 'uniqueValue',
            'tokens' => ['label'],
        ], $config));
    }

    public static function matchValidationMessage(array $config = []): array
    {
        return self::validationMessageField(array_merge([
            'messageKey' => ValidationMessagesHelper::KEY_MATCH,
            'name' => 'validationMessages.match',
            'if' => 'matchField',
            'tokens' => ['label', 'value'],
        ], $config));
    }

    public static function limitValueField(array $config = []): array
    {
        return self::lightswitchField(array_merge([
            'label' => Craft::t('formie', 'Limit Value'),
            'instructions' => Craft::t('formie', 'Whether to limit the value of this field.'),
            'name' => 'limit',
        ], $config));
    }

    public static function textLimitMinFields(array $config = []): array
    {
        return self::fieldWrap(array_merge([
            'label' => Craft::t('formie', 'Min Value'),
            'instructions' => Craft::t('formie', 'Set a minimum value that users must enter.'),
            'if' => 'limit',
            'children' => [
                self::numberField([
                    'name' => 'min',
                ]),
                self::selectField([
                    'name' => 'minType',
                    'options' => [
                        ['label' => Craft::t('formie', 'Characters'), 'value' => 'characters'],
                        ['label' => Craft::t('formie', 'Words'), 'value' => 'words'],
                    ],
                ]),
            ],
        ], $config));
    }

    public static function textLimitMaxFields(array $config = []): array
    {
        return self::fieldWrap(array_merge([
            'label' => Craft::t('formie', 'Max Value'),
            'instructions' => Craft::t('formie', 'Set a maximum value that users must enter.'),
            'if' => 'limit',
            'children' => [
                self::numberField([
                    'name' => 'max',
                ]),
                self::selectField([
                    'name' => 'maxType',
                    'options' => [
                        ['label' => Craft::t('formie', 'Characters'), 'value' => 'characters'],
                        ['label' => Craft::t('formie', 'Words'), 'value' => 'words'],
                    ],
                ]),
            ],
        ], $config));
    }

    public static function minCharactersValidationMessage(array $config = []): array
    {
        return self::validationMessageField(array_merge([
            'messageKey' => ValidationMessagesHelper::KEY_MIN_CHARACTERS,
            'name' => 'validationMessages.minCharacters',
            'if' => 'limit && min && minType == "characters"',
            'tokens' => ['label', 'limit', 'min'],
        ], $config));
    }

    public static function minWordsValidationMessage(array $config = []): array
    {
        return self::validationMessageField(array_merge([
            'messageKey' => ValidationMessagesHelper::KEY_MIN_WORDS,
            'name' => 'validationMessages.minWords',
            'if' => 'limit && min && minType == "words"',
            'tokens' => ['label', 'limit', 'min'],
        ], $config));
    }

    public static function maxCharactersValidationMessage(array $config = []): array
    {
        return self::validationMessageField(array_merge([
            'messageKey' => ValidationMessagesHelper::KEY_MAX_CHARACTERS,
            'name' => 'validationMessages.maxCharacters',
            'if' => 'limit && max && maxType == "characters"',
            'tokens' => ['label', 'limit', 'max'],
        ], $config));
    }

    public static function maxWordsValidationMessage(array $config = []): array
    {
        return self::validationMessageField(array_merge([
            'messageKey' => ValidationMessagesHelper::KEY_MAX_WORDS,
            'name' => 'validationMessages.maxWords',
            'if' => 'limit && max && maxType == "words"',
            'tokens' => ['label', 'limit', 'max'],
        ], $config));
    }

    public static function limitOptionsField(array $config = []): array
    {
        return self::lightswitchField(array_merge([
            'label' => Craft::t('formie', 'Limit Options'),
            'instructions' => Craft::t('formie', 'Whether to limit the options users can choose for this field.'),
            'name' => 'limitOptions',
        ], $config));
    }

    public static function optionsLimitMinField(array $config = []): array
    {
        return self::numberField(array_merge([
            'label' => Craft::t('formie', 'Min Value'),
            'instructions' => Craft::t('formie', 'Set the minimum options that users must select.'),
            'name' => 'min',
            'if' => 'limitOptions',
        ], $config));
    }

    public static function optionsLimitMaxField(array $config = []): array
    {
        return self::numberField(array_merge([
            'label' => Craft::t('formie', 'Max Value'),
            'instructions' => Craft::t('formie', 'Set the maximum options that users must select.'),
            'name' => 'max',
            'if' => 'limitOptions',
        ], $config));
    }

    public static function minOptionsValidationMessage(array $config = []): array
    {
        return self::validationMessageField(array_merge([
            'messageKey' => ValidationMessagesHelper::KEY_MIN_OPTIONS,
            'name' => 'validationMessages.minOptions',
            'if' => 'limitOptions && min',
            'tokens' => ['label', 'min'],
        ], $config));
    }

    public static function maxOptionsValidationMessage(array $config = []): array
    {
        return self::validationMessageField(array_merge([
            'messageKey' => ValidationMessagesHelper::KEY_MAX_OPTIONS,
            'name' => 'validationMessages.maxOptions',
            'if' => 'limitOptions && max',
            'tokens' => ['label', 'max'],
        ], $config));
    }

    public static function minRowsField(array $config = []): array
    {
        return self::numberField(array_merge([
            'label' => Craft::t('formie', 'Minimum instances'),
            'instructions' => Craft::t('formie', 'The minimum required number of rows that must be completed.'),
            'name' => 'minRows',
        ], $config));
    }

    public static function maxRowsField(array $config = []): array
    {
        return self::numberField(array_merge([
            'label' => Craft::t('formie', 'Maximum instances'),
            'instructions' => Craft::t('formie', 'The maximum required number of rows that must be completed.'),
            'name' => 'maxRows',
        ], $config));
    }

    public static function nestedFieldsConfigurationField(array $config = [], array $childConfig = []): array
    {
        return array_merge([
            '$field' => 'nestedLayout',
            'label' => Craft::t('formie', 'Field Configuration'),
            'instructions' => Craft::t('formie', 'Configure nested fields. Move to rearrange columns and rows, and click to edit field settings.'),
            'name' => 'rows',
            'parentType' => $childConfig['parentType'] ?? null,
            'layoutKey' => $childConfig['layoutKey'] ?? 'rows',
            'children' => [],
        ], $config);
    }

    public static function modalTabs(array $tabs = []): array
    {
        // Filter tabs that have content
        $tabsWithContent = array_filter($tabs, function($tab) {
            return !empty($tab['content']);
        });

        // If no tabs have content, return empty structure
        if (empty($tabsWithContent)) {
            return [];
        }

        $tabSchema = array_merge(...array_map(function($tab) {
            $content = $tab['content'] ?? [];
            if (!is_array($content)) {
                return [];
            }
            if (array_is_list($content)) {
                return $content;
            }
            return [$content];
        }, $tabsWithContent));

        return self::schemaNode([
            '$cmp' => 'ModalTabs',
            'props' => [
                'defaultValue' => $tabsWithContent[0]['handle'] ?? '',
            ],
            'schema' => $tabSchema,
            'children' => [
                [
                    '$cmp' => 'ModalTabsList',
                    'children' => array_map(function($tab) {
                        return [
                            '$cmp' => 'ModalTabsTrigger',
                            'props' => [
                                'value' => $tab['handle'],
                            ],
                            'children' => $tab['label'],
                        ];
                    }, $tabsWithContent),
                ],
                ...array_map(function($tab) {
                    return [
                        '$cmp' => 'ModalTabsContent',
                        'props' => array_merge([
                            'value' => $tab['handle'],
                        ], $tab['props'] ?? []),
                        'children' => $tab['content'],  
                    ];
                }, $tabsWithContent),
            ],
        ]);
    }

    // public static function customSettingsField(array $children = []): array
    // {
    //     return [
    //         '$field' => 'group',
    //         'name' => 'customSettings',
    //         'children' => $children,
    //     ];
    // }

    // public static function extractFieldsFromSchema(array $fieldSchema, array $names = []): array
    // {
    //     foreach ($fieldSchema as $field) {
    //         if (isset($field['name'])) {
    //             $names[] = $field['name'];
    //         }

    //         if (isset($field['children'])) {
    //             self::extractFieldsFromSchema($field['children'], $names);
    //         }
    //     }

    //     return $names;
    // }

    // public static function setFieldAttributes(array &$fieldSchema): void
    // {
    //     // Automaticallty set the `id` and `key` attributes for fields, which FormKit needs
    //     foreach ($fieldSchema as &$field) {
    //         $name = $field['name'] ?? null;
    //         $id = $field['id'] ?? null;
    //         $key = $field['key'] ?? null;

    //         if ($name && !$id) {
    //             $field['id'] = $name;
    //         }

    //         if ($name && !$key) {
    //             $field['key'] = $name;
    //         }

    //         if (isset($field['children'])) {
    //             self::setFieldAttributes($field['children']);
    //         }
    //     }
    // }

    // public static function renderElementSelect(string $handle, array $elements, array $config): array
    // {
    //     $view = Craft::$app->getView();

    //     $config['id'] = Html::id($handle . '-' . StringHelper::randomString(10));
    //     $config['name'] = $handle;
    //     $config['elements'] = $elements;

    //     $view->startJsBuffer();
    //     $html = $view->renderTemplate('_includes/forms/elementSelect', $config);
    //     $js = $view->clearJsBuffer();

    //     return [
    //         ('__elementSelectHtml_' . $handle) => $html,
    //         ('__elementSelectJs_' . $handle) => $js,
    //     ];
    // }

    private static function collectSchemaFields($node, string $prefix, array &$entries): void
    {
        if (is_array($node)) {
            if (array_is_list($node)) {
                foreach ($node as $child) {
                    self::collectSchemaFields($child, $prefix, $entries);
                }
                return;
            }

            if (isset($node['$field']) && isset($node['name'])) {
                $entries[] = [
                    'path' => $prefix . $node['name'],
                    'field' => self::sanitizeFieldEntryNode($node),
                ];
            }

            if (isset($node['schema'])) {
                $childPrefix = $node['schemaChildPrefix'] ?? '';
                self::collectSchemaFields($node['schema'], $prefix . $childPrefix, $entries);
            } else if (isset($node['children'])) {
                $childPrefix = $node['schemaChildPrefix'] ?? '';
                self::collectSchemaFields($node['children'], $prefix . $childPrefix, $entries);
            }
        }
    }

    private static function applyPluginKitReactDefaults(array $node): array
    {
        $fieldType = $node['$field'] ?? null;

        if ($fieldType === 'elementSelect') {
            $node['elementSelectOptionsAction'] ??= 'formie/fields/get-element-select-options';
            $node['elementSelectStorageKeyPrefix'] ??= 'FormieElementSelectField';
        }

        if ($fieldType === 'table') {
            $node['bulkOptionsAction'] ??= 'formie/fields/get-predefined-options';
        }

        if ($fieldType === 'richText') {
            $node['linkSelectorStorageKeyPrefix'] ??= 'FormieInput.LinkTo';
        }

        return $node;
    }

    private static function sanitizeFieldEntryNode(array $node): array
    {
        $allowedKeys = [
            '$field',
            'name',
            'label',
            'validation',
            'required',
            'if',
            '_scopePath',
            '_data',
            'reservedHandles',
            'uniqueHandleScope',
            'uniqueHandleScopePath',
        ];

        $sanitized = [];

        foreach ($allowedKeys as $key) {
            if (array_key_exists($key, $node)) {
                $sanitized[$key] = $node[$key];
            }
        }

        return $sanitized;
    }

    private static function normalizePreviewNode(array $node): array
    {
        if (isset($node['children']) && is_array($node['children'])) {
            $node['children'] = self::normalizePreviewSchema($node['children']);
        }

        return $node;
    }

    public static function extractSettingsSchema(mixed $schema, array $names): array
    {
        if ($names === []) {
            return [];
        }

        $normalized = self::normalizeSchema($schema);
        $found = [];
        self::_collectExtractableSettingsFields($normalized, $names, $found, null);

        $extracted = [];

        foreach ($names as $name) {
            if (!isset($found[$name])) {
                continue;
            }

            $extracted[] = self::_prepareDefaultsSchemaNode($found[$name]);
        }

        return $extracted;
    }

    public static function extractDefaultsSchema(mixed $schema, array $fields): array
    {
        if ($fields === []) {
            return [];
        }

        $schemaNames = array_values($fields);
        $normalized = self::normalizeSchema($schema);
        $found = [];
        self::_collectExtractableSettingsFields($normalized, $schemaNames, $found, null);

        $extracted = [];

        foreach ($fields as $outputName => $schemaName) {
            if (!isset($found[$schemaName])) {
                continue;
            }

            $node = $found[$schemaName];
            $node['name'] = $outputName;
            $extracted[] = self::_prepareDefaultsSchemaNode($node);
        }

        return $extracted;
    }

    private static function _collectExtractableSettingsFields(mixed $node, array $names, array &$found, ?array $fieldWrap): void
    {
        if (!is_array($node)) {
            return;
        }

        if (array_is_list($node)) {
            foreach ($node as $child) {
                self::_collectExtractableSettingsFields($child, $names, $found, $fieldWrap);
            }

            return;
        }

        $nextFieldWrap = (($node['$cmp'] ?? null) === 'FieldWrap') ? $node : $fieldWrap;

        if (isset($node['$field'], $node['name']) && in_array($node['name'], $names, true) && !isset($found[$node['name']])) {
            $found[$node['name']] = self::_mergeDefaultsFieldContext($node, $fieldWrap);
        }

        if (isset($node['schema'])) {
            self::_collectExtractableSettingsFields($node['schema'], $names, $found, $nextFieldWrap);

            return;
        }

        if (isset($node['children'])) {
            self::_collectExtractableSettingsFields($node['children'], $names, $found, $nextFieldWrap);
        }
    }

    private static function _mergeDefaultsFieldContext(array $node, ?array $fieldWrap): array
    {
        if (!$fieldWrap) {
            return $node;
        }

        if (empty($node['label']) && !empty($fieldWrap['label'])) {
            $node['label'] = $fieldWrap['label'];
        }

        if (empty($node['instructions']) && !empty($fieldWrap['instructions'])) {
            $node['instructions'] = $fieldWrap['instructions'];
        }

        return $node;
    }

    private static function _prepareDefaultsSchemaNode(array $node): array
    {
        unset($node['required'], $node['validation']);

        if (isset($node['defaults']) && is_array($node['defaults'])) {
            foreach ($node['defaults'] as $key => $value) {
                $node[$key] = $value;
            }
        }

        unset($node['defaults']);

        return $node;
    }
}
