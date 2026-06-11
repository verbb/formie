<?php
namespace verbb\formie\fields\custom\adapters;

use verbb\formie\elements\Form;
use verbb\formie\Formie;
use verbb\formie\fields\CustomField;
use verbb\formie\fields\custom\AbstractCustomFieldAdapter;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\ClientModule;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\Notification;
use verbb\formie\models\SlotTag;
use verbb\formie\theme\context\RenderContext;
use verbb\formie\web\twig\Extension as FormieTwigExtension;

use Craft;
use craft\base\ElementInterface;
use craft\fields\Link as CraftLink;
use craft\fields\data\LinkData;
use craft\fields\linktypes\BaseLinkType;
use craft\fields\linktypes\Email;
use craft\fields\linktypes\Phone;
use craft\fields\linktypes\Sms;
use craft\fields\linktypes\Url;
use craft\helpers\Component;
use craft\helpers\Html;
use craft\helpers\StringHelper;
use craft\gql\GqlEntityRegistry;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

use yii\validators\StringValidator;

class LinkCustomFieldAdapter extends AbstractCustomFieldAdapter
{
    private const TYPE_CLASSES = [
        'url' => Url::class,
        'email' => Email::class,
        'tel' => Phone::class,
        'sms' => Sms::class,
    ];

    private const ADVANCED_FIELDS = [
        'urlSuffix',
        'target',
        'title',
        'class',
        'id',
        'rel',
        'ariaLabel',
        'download',
    ];

    // Static Methods
    // =========================================================================

    public static function handle(): string
    {
        return 'link';
    }

    public static function displayName(): string
    {
        return Craft::t('formie', 'Link');
    }

    public static function craftFieldClasses(): array
    {
        return [
            CraftLink::class,
        ];
    }


    // Public Methods
    // =========================================================================

    public function getDefaultSettings(): array
    {
        return [
            'allowedTypes' => ['url'],
            'defaultType' => 'url',
            'advancedFields' => [],
            'maxLength' => 255,
        ];
    }

    public function getFormBuilderSettingsSchema(CustomField $field): array
    {
        $typeOptions = $this->getTypeOptions();

        return [
            SchemaHelper::checkboxSelectField([
                'label' => Craft::t('formie', 'Allowed Link Types'),
                'instructions' => Craft::t('formie', 'Choose which link types can be submitted from the front-end form.'),
                'name' => $this->settingName('allowedTypes'),
                'options' => $typeOptions,
                'required' => true,
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Default Link Type'),
                'instructions' => Craft::t('formie', 'Choose the link type shown by default when multiple types are enabled.'),
                'name' => $this->settingName('defaultType'),
                'options' => $typeOptions,
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Placeholder'),
                'instructions' => Craft::t('formie', 'The text that will be shown if the field doesn’t have a value.'),
                'name' => $this->settingName('placeholder'),
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Default Value'),
                'instructions' => Craft::t('formie', 'Set a default link value for the field.'),
                'name' => $this->settingName('defaultValue'),
            ]),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Allow Root-Relative URLs'),
                'name' => $this->settingName('allowRootRelativeUrls'),
                'if' => 'customFieldAdapterSettings.allowedTypes && customFieldAdapterSettings.allowedTypes.includes("url")',
            ]),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Allow Anchors'),
                'name' => $this->settingName('allowAnchors'),
                'if' => 'customFieldAdapterSettings.allowedTypes && customFieldAdapterSettings.allowedTypes.includes("url")',
            ]),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Allow Custom URL Schemes'),
                'name' => $this->settingName('allowCustomSchemes'),
                'if' => 'customFieldAdapterSettings.allowedTypes && customFieldAdapterSettings.allowedTypes.includes("url")',
            ]),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Show the “Label” Field'),
                'name' => $this->settingName('showLabelField'),
            ]),
            SchemaHelper::checkboxSelectField([
                'label' => Craft::t('formie', 'Advanced Fields'),
                'name' => $this->settingName('advancedFields'),
                'options' => [
                    ['label' => Craft::t('app', 'URL Suffix'), 'value' => 'urlSuffix'],
                    ['label' => Craft::t('app', 'Target'), 'value' => 'target'],
                    ['label' => Craft::t('app', 'Title Text'), 'value' => 'title'],
                    ['label' => Craft::t('app', 'Class Name'), 'value' => 'class'],
                    ['label' => Craft::t('app', 'ID'), 'value' => 'id'],
                    ['label' => Craft::t('app', 'Relation'), 'value' => 'rel'],
                    ['label' => Craft::t('app', 'ARIA Label'), 'value' => 'ariaLabel'],
                    ['label' => Craft::t('app', 'Download'), 'value' => 'download'],
                ],
            ]),
            SchemaHelper::numberField([
                'label' => Craft::t('formie', 'Max Length'),
                'instructions' => Craft::t('formie', 'The maximum length in bytes for the submitted link value.'),
                'name' => $this->settingName('maxLength'),
            ]),
        ];
    }

    public function getFormBuilderPreviewSchema(CustomField $field): array
    {
        return [
            SchemaHelper::previewInput([
                'placeholder' => SchemaHelper::previewBind('field.customFieldAdapterSettings.placeholder', ''),
                'value' => SchemaHelper::previewBind('field.customFieldAdapterSettings.defaultValue', ''),
            ]),
        ];
    }

    public function getSettingGqlTypes(CustomField $field): array
    {
        return [
            'allowedTypes' => [
                'name' => 'allowedTypes',
                'type' => Type::listOf(Type::string()),
                'resolve' => fn() => $this->getAllowedTypes($field),
            ],
            'advancedFields' => [
                'name' => 'advancedFields',
                'type' => Type::listOf(Type::string()),
                'resolve' => fn() => $this->getAdvancedFields($field),
            ],
        ];
    }

    public function getContentGqlType(CustomField $field): Type|array
    {
        return [
            'type' => Type::string(),
            'value' => Type::string(),
            'label' => Type::string(),
            'urlSuffix' => Type::string(),
            'url' => Type::string(),
            'target' => Type::string(),
            'title' => Type::string(),
            'class' => Type::string(),
            'id' => Type::string(),
            'rel' => Type::string(),
            'ariaLabel' => Type::string(),
            'download' => Type::boolean(),
            'filename' => Type::string(),
        ];
    }

    public function getContentGqlMutationArgumentType(CustomField $field): Type|array
    {
        $typeName = 'FormieCustomLinkFieldInput';

        return [
            'name' => $field->handle,
            'type' => GqlEntityRegistry::getOrCreate($typeName, fn() => new InputObjectType([
                'name' => $typeName,
                'fields' => [
                    'type' => Type::string(),
                    'value' => Type::string(),
                    'label' => Type::string(),
                    'urlSuffix' => Type::string(),
                    'target' => Type::string(),
                    'title' => Type::string(),
                    'class' => Type::string(),
                    'id' => Type::string(),
                    'rel' => Type::string(),
                    'ariaLabel' => Type::string(),
                    'download' => Type::boolean(),
                    'filename' => Type::string(),
                ],
            ])),
            'description' => $field->instructions->isEmpty() ? null : $field->instructions->toPlainText(),
        ];
    }

    public function getClientInput(CustomField $field): array
    {
        return [
            'inputType' => 'link',
            'placeholder' => Craft::t('site', $this->getPlaceholder($field)) ?: null,
            'linkSettings' => [
                'allowedTypes' => $this->getAllowedTypes($field),
                'defaultType' => $this->getDefaultType($field),
                'showLabelField' => $this->getBooleanSetting($field, 'showLabelField'),
                'advancedFields' => $this->getAdvancedFields($field),
            ],
        ];
    }

    public function getClientModules(CustomField $field): array
    {
        if (count($this->getAllowedTypes($field)) <= 1) {
            return [];
        }

        return [
            new ClientModule([
                'id' => 'custom-link',
                'renderTargets' => [ClientModule::RENDER_TARGET_FRONTEND],
            ]),
        ];
    }

    public function getValueClass(CustomField $field): ?string
    {
        return null;
    }

    public function normalizeValue(mixed $value, CustomField $field, ?ElementInterface $element): mixed
    {
        $value = parent::normalizeValue($value, $field, $element);

        if ($value instanceof LinkData || $value === null || $value === '') {
            return $value ?: null;
        }

        if (!is_array($value)) {
            $value = [
                'type' => $this->resolveType((string)$value, $field),
                'value' => $value,
            ];
        }

        $typeId = $this->normalizeTypeId($value['type'] ?? $this->getDefaultType($field), $field);
        $rawValue = $value['value'] ?? $value[$typeId]['value'] ?? '';
        $rawValue = is_string($rawValue) ? trim($rawValue) : '';

        if ($rawValue === '') {
            return null;
        }

        $linkType = $this->createLinkType($typeId, $field);
        $config = $this->normalizeLinkConfig($value, $field);

        return new LinkData($linkType->normalizeValue($rawValue), $linkType, $config);
    }

    public function serializeValue(mixed $value, CustomField $field, ?ElementInterface $element): mixed
    {
        $value = $this->normalizeValue($value, $field, $element);

        return $value instanceof LinkData ? $value->serialize() : null;
    }

    public function isValueEmpty(mixed $value, CustomField $field, ?ElementInterface $element): bool
    {
        return !$this->normalizeValue($value, $field, $element) instanceof LinkData;
    }

    public function validateValue(ElementInterface $element, CustomField $field): void
    {
        $value = $this->normalizeValue($element->getFieldValue($field->valueKey()), $field, $element);

        if (!$value instanceof LinkData) {
            return;
        }

        if (!in_array($value->type, $this->getAllowedTypes($field), true)) {
            $element->addError($field->errorKey(), Craft::t('formie', '{attribute} no longer allows {type} links.', [
                'attribute' => $field->label,
                'type' => $value->type,
            ]));

            return;
        }

        $linkType = $this->createLinkType($value->type, $field);
        $serialized = $value->serialize();
        $rawValue = (string)($serialized['value'] ?? '');
        $error = null;

        if (!$linkType->validateValue($rawValue, $error)) {
            $element->addError($field->errorKey(), $error ?? Craft::t('formie', '{attribute} must be a valid link.', [
                'attribute' => $field->label,
            ]));

            return;
        }

        $validator = new StringValidator(['max' => $this->getMaxLength($field)]);

        if (!$validator->validate($rawValue, $error)) {
            $element->addError($field->errorKey(), $error);
        }
    }

    public function getInputHtml(CustomField $field, Form $form, mixed $value): string
    {
        $value = $this->normalizeValue($value, $field, $form->getCurrentSubmission());

        return $this->renderInputs(
            field: $field,
            value: $value,
            form: $form,
            name: $field->getHtmlName(),
            typeName: $field->getHtmlName('type'),
            valueName: $field->getHtmlName('value'),
            labelName: $field->getHtmlName('label'),
            id: $field->getHtmlId($form),
            dataId: $field->getHtmlDataId($form),
        );
    }

    public function getCpInputHtml(CustomField $field, mixed $value, ?ElementInterface $element, bool $inline): string
    {
        $value = $this->normalizeValue($value, $field, $element);

        return $this->createCraftLinkField($field)->getInputHtml($value, $element);
    }

    public function getPreviewHtml(CustomField $field, mixed $value, ElementInterface $element): string
    {
        $value = $this->normalizeValue($value, $field, $element);

        if (!$value instanceof LinkData) {
            return '';
        }

        return $this->createCraftLinkField($field)->getPreviewHtml($value, $element);
    }

    public function getValueAsString(mixed $value, CustomField $field, ?ElementInterface $element = null): string
    {
        $value = $this->normalizeValue($value, $field, $element);

        return $value instanceof LinkData ? $value->getUrl() : '';
    }

    public function getValueAsArray(mixed $value, CustomField $field, ?ElementInterface $element = null): mixed
    {
        $value = $this->normalizeValue($value, $field, $element);

        if (!$value instanceof LinkData) {
            return [];
        }

        return array_merge($value->serialize(), [
            'url' => $value->getUrl(),
            'label' => $value->getLabel(),
        ]);
    }

    public function getValueForExport(mixed $value, CustomField $field, ?ElementInterface $element = null): mixed
    {
        return $this->getValueAsString($value, $field, $element);
    }

    public function getValueForIntegration(mixed $value, CustomField $field, IntegrationField $integrationField, IntegrationInterface $integration, ?ElementInterface $element = null, string $fieldKey = ''): mixed
    {
        return $integrationField->getType() === IntegrationField::TYPE_ARRAY
            ? $this->getValueAsArray($value, $field, $element)
            : $this->getValueAsString($value, $field, $element);
    }

    public function getValueForReferenceBlock(mixed $value, CustomField $field, Notification $notification, ?ElementInterface $element = null): mixed
    {
        $value = $this->normalizeValue($value, $field, $element);

        return $value instanceof LinkData ? $value->getLink() : '';
    }


    // Protected Methods
    // =========================================================================

    protected function renderInputs(CustomField $field, ?LinkData $value, ?Form $form, string $name, string $typeName, string $valueName, string $labelName, string $id, string $dataId): string
    {
        $allowedTypes = $this->getAllowedTypes($field);
        $typeId = $value?->type ?: $this->getDefaultType($field);
        $serialized = $value?->serialize() ?? [];
        $rawValue = (string)($serialized['value'] ?? '');
        $placeholder = Craft::t('site', $this->getPlaceholder($field)) ?: null;
        $valueInputAttributes = $this->getValueInputAttributes($typeId, $field);
        $typeInput = count($allowedTypes) > 1 ? Html::dropDownList($typeName, $typeId, $this->getTypeLabelMap($allowedTypes), [
            'id' => $id . '-type',
            'class' => ['formie-input', 'formie-select', 'formie-custom-link-type-input'],
            'style' => '--formie-link-type-width: max-content; width: var(--formie-link-type-width); min-width: 8rem; flex: 0 0 auto;',
            'aria-label' => Craft::t('formie', 'Link Type'),
            'data-formie-custom-link-type' => true,
        ]) : Html::hiddenInput($typeName, $typeId);

        $html = Html::tag('div',
            $typeInput .
            $this->renderValueInput($field, $form, [
                'type' => $valueInputAttributes['type'],
                'id' => $id,
                'name' => $valueName,
                'value' => $rawValue,
                'placeholder' => $placeholder,
                'required' => $field->required ? true : null,
                'maxlength' => $this->getMaxLength($field),
                'inputmode' => $valueInputAttributes['inputmode'],
                'autocomplete' => $valueInputAttributes['autocomplete'],
                'data-formie-input' => true,
                'data-formie-custom-field-input' => true,
                'data-formie-custom-link-value' => true,
                'data-formie-custom-link-default-placeholder' => $placeholder,
                'data-formie-input-id' => $dataId,
                'data-formie-input-type' => 'link',
            ]),
            [
                'data-formie-custom-link' => true,
                'data-formie-custom-link-name' => $name,
                'class' => ['formie-custom-link-inputs'],
                'style' => count($allowedTypes) > 1 ? 'display: flex; align-items: stretch; gap: 0.5rem; width: 100%;' : null,
                'data-formie-custom-link-allow-root-relative' => $this->getBooleanSetting($field, 'allowRootRelativeUrls') ? '1' : '0',
                'data-formie-custom-link-allow-anchors' => $this->getBooleanSetting($field, 'allowAnchors') ? '1' : '0',
                'data-formie-custom-link-allow-custom-schemes' => $this->getBooleanSetting($field, 'allowCustomSchemes') ? '1' : '0',
            ]
        );

        if ($this->getBooleanSetting($field, 'showLabelField')) {
            $html .= Html::textInput($labelName, $serialized['label'] ?? '', [
                'class' => ['formie-input', 'formie-custom-link-label-input'],
                'placeholder' => Craft::t('formie', 'Label'),
                'data-formie-custom-link-label' => true,
            ]);
        }

        foreach ($this->getAdvancedFields($field) as $advancedField) {
            $advancedName = $name . '[' . $advancedField . ']';
            $advancedId = $id . '-' . StringHelper::toKebabCase($advancedField);

            $html .= match ($advancedField) {
                'target' => Html::checkbox($advancedName, ($serialized['target'] ?? null) === '_blank', [
                    'id' => $advancedId,
                    'value' => '_blank',
                    'label' => Craft::t('app', 'Open in a new tab'),
                ]),
                'download' => Html::checkbox($advancedName, !empty($serialized['download']), [
                    'id' => $advancedId,
                    'value' => '1',
                    'class' => ['formie-checkbox', 'formie-custom-link-download-input'],
                    'label' => Craft::t('app', 'Download'),
                ]) . Html::textInput($name . '[filename]', $serialized['filename'] ?? '', [
                    'id' => $id . '-filename',
                    'class' => ['formie-input', 'formie-custom-link-attribute-input'],
                    'placeholder' => Craft::t('app', 'Filename'),
                    'data-formie-custom-link-attribute' => 'filename',
                ]),
                default => Html::textInput($advancedName, $serialized[$advancedField] ?? '', [
                    'id' => $advancedId,
                    'class' => ['formie-input', 'formie-custom-link-attribute-input'],
                    'placeholder' => $this->advancedFieldLabel($advancedField),
                    'data-formie-custom-link-attribute' => $advancedField,
                ]),
            };
        }

        return $html;
    }

    protected function renderValueInput(CustomField $field, ?Form $form, array $attributes): string
    {
        $tag = SlotTag::make('input')
            ->core($attributes)
            ->theme([
                'class' => [
                    'formie-input',
                    'formie-custom-link-value-input',
                ],
                'style' => 'flex: 1 1 auto; min-width: 0;',
            ])
            ->instanceAttributes($field->getInputAttributes());

        if (!$form) {
            return Html::tag($tag->tag, null, $tag->attributes);
        }

        $context = RenderContext::from([
            'form' => $form,
            'field' => $field,
        ]);

        $tag = Formie::$plugin->getThemeConfigService()->applyFieldTagConfig($field, $form, 'fieldInput', $tag, $context);

        return FormieTwigExtension::formatSlotTagHtml('fieldInput', $tag, $context->toArray());
    }

    protected function getAllowedTypes(CustomField $field): array
    {
        $allowedTypes = $this->getSetting($field, 'allowedTypes', ['url']);
        $allowedTypes = is_array($allowedTypes) ? $allowedTypes : ['url'];
        $allowedTypes = array_values(array_intersect($allowedTypes, array_keys(self::TYPE_CLASSES)));

        return $allowedTypes ?: ['url'];
    }

    protected function getDefaultType(CustomField $field): string
    {
        return $this->normalizeTypeId((string)$this->getSetting($field, 'defaultType', 'url'), $field);
    }

    protected function getAdvancedFields(CustomField $field): array
    {
        $advancedFields = $this->getSetting($field, 'advancedFields', []);
        $advancedFields = is_array($advancedFields) ? $advancedFields : [];

        return array_values(array_intersect($advancedFields, self::ADVANCED_FIELDS));
    }

    protected function getBooleanSetting(CustomField $field, string $name): bool
    {
        return (bool)$this->getSetting($field, $name, false);
    }

    protected function getMaxLength(CustomField $field): int
    {
        return max((int)$this->getSetting($field, 'maxLength', 255), 10);
    }

    protected function getValueInputAttributes(string $typeId, CustomField $field): array
    {
        return match ($typeId) {
            'email' => [
                'type' => 'email',
                'inputmode' => 'email',
                'autocomplete' => 'email',
            ],
            'tel', 'sms' => [
                'type' => 'tel',
                'inputmode' => 'tel',
                'autocomplete' => 'tel',
            ],
            default => [
                'type' => $this->shouldUseNativeUrlInput($field) ? 'url' : 'text',
                'inputmode' => 'url',
                'autocomplete' => 'url',
            ],
        };
    }

    protected function shouldUseNativeUrlInput(CustomField $field): bool
    {
        return !$this->getBooleanSetting($field, 'allowRootRelativeUrls')
            && !$this->getBooleanSetting($field, 'allowAnchors')
            && !$this->getBooleanSetting($field, 'allowCustomSchemes');
    }

    protected function normalizeTypeId(string $typeId, CustomField $field): string
    {
        return in_array($typeId, $this->getAllowedTypes($field), true) ? $typeId : $this->getAllowedTypes($field)[0];
    }

    protected function resolveType(string $value, CustomField $field): string
    {
        foreach ($this->getAllowedTypes($field) as $typeId) {
            if ($this->createLinkType($typeId, $field)->supports($value)) {
                return $typeId;
            }
        }

        return $this->getDefaultType($field);
    }

    protected function createLinkType(string $typeId, CustomField $field): BaseLinkType
    {
        $class = self::TYPE_CLASSES[$typeId] ?? Url::class;
        $settings = [];

        if ($class === Url::class) {
            $settings = [
                'allowRootRelativeUrls' => $this->getBooleanSetting($field, 'allowRootRelativeUrls'),
                'allowAnchors' => $this->getBooleanSetting($field, 'allowAnchors'),
                'allowCustomSchemes' => $this->getBooleanSetting($field, 'allowCustomSchemes'),
            ];
        }

        return Component::createComponent([
            'type' => $class,
            ...$settings,
        ], BaseLinkType::class);
    }

    protected function createCraftLinkField(CustomField $field): CraftLink
    {
        $typeSettings = [];

        if (in_array('url', $this->getAllowedTypes($field), true)) {
            $typeSettings['url'] = [
                'allowRootRelativeUrls' => $this->getBooleanSetting($field, 'allowRootRelativeUrls'),
                'allowAnchors' => $this->getBooleanSetting($field, 'allowAnchors'),
                'allowCustomSchemes' => $this->getBooleanSetting($field, 'allowCustomSchemes'),
            ];
        }

        return new CraftLink([
            'handle' => $field->handle,
            'name' => $field->label,
            'types' => $this->getAllowedTypes($field),
            'typeSettings' => $typeSettings,
            'showLabelField' => $this->getBooleanSetting($field, 'showLabelField'),
            'advancedFields' => $this->getAdvancedFields($field),
            'maxLength' => $this->getMaxLength($field),
        ]);
    }

    protected function normalizeLinkConfig(array $value, CustomField $field): array
    {
        $advancedFields = $this->getAdvancedFields($field);
        $config = [];

        if (!empty($value['label']) && $this->getBooleanSetting($field, 'showLabelField')) {
            $config['label'] = $value['label'];
        }

        foreach ($advancedFields as $advancedField) {
            if (!array_key_exists($advancedField, $value) || $value[$advancedField] === '' || $value[$advancedField] === null) {
                continue;
            }

            $config[$advancedField] = match ($advancedField) {
                'urlSuffix' => str_starts_with((string)$value[$advancedField], '#') ? $value[$advancedField] : StringHelper::ensureLeft((string)$value[$advancedField], '?'),
                'target' => $value[$advancedField] ? '_blank' : null,
                'class', 'id', 'rel' => implode(' ', array_map(fn(string $part): string => Html::id($part), explode(' ', (string)$value[$advancedField]))),
                'download' => (bool)$value[$advancedField],
                default => $value[$advancedField],
            };
        }

        if (in_array('download', $advancedFields, true) && !empty($value['filename'])) {
            $config['filename'] = $value['filename'];
        }

        return array_filter($config, static fn(mixed $item): bool => $item !== null && $item !== '');
    }

    protected function getTypeOptions(): array
    {
        return array_map(fn(string $typeId, string $class): array => [
            'label' => $class::displayName(),
            'value' => $typeId,
        ], array_keys(self::TYPE_CLASSES), self::TYPE_CLASSES);
    }

    protected function getTypeLabelMap(array $typeIds): array
    {
        $labels = [];

        foreach ($typeIds as $typeId) {
            $class = self::TYPE_CLASSES[$typeId] ?? null;

            if ($class) {
                $labels[$typeId] = $class::displayName();
            }
        }

        return $labels;
    }

    protected function advancedFieldLabel(string $field): string
    {
        return match ($field) {
            'urlSuffix' => Craft::t('app', 'URL Suffix'),
            'title' => Craft::t('app', 'Title Text'),
            'class' => Craft::t('app', 'Class Name'),
            'id' => Craft::t('app', 'ID'),
            'rel' => Craft::t('app', 'Relation'),
            'ariaLabel' => Craft::t('app', 'ARIA Label'),
            default => $field,
        };
    }
}
