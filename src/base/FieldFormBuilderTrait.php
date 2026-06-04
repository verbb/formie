<?php
namespace verbb\formie\base;

use verbb\formie\Formie;
use verbb\formie\compatibility\fields\FieldCompatibility;
use verbb\formie\events\ModifyFieldConfigEvent;
use verbb\formie\events\ModifyFieldSchemaEvent;
use verbb\formie\helpers\FileHelper;
use verbb\formie\helpers\SchemaHelper;

use Craft;
use craft\web\View;

trait FieldFormBuilderTrait
{
    // Public Methods
    // =========================================================================

    public function getFieldTypeConfig(bool $includeSchemaIndex = true): array
    {
        $compiledSchema = null;

        if ($includeSchemaIndex) {
            $compiledSchema = SchemaHelper::compileSchema($this->getFormBuilderSchema());
        }

        $preview = $this->getFormBuilderPreviewSchema();
        $newField = $this->getFormBuilderConfig();
        $configData = $this->getFieldTypeConfigData();

        $referenceConfig = $this->references()->toConfigArray();
        $variableSourceConfig = array_values(array_map(static function($source) {
            return $source->toArray();
        }, $this->variableSources()));
        $baseTypeDefinition = static::getFieldTypeDefinition();
        $config = [
            'icon' => $baseTypeDefinition['icon'],
            'type' => $baseTypeDefinition['type'],
            'label' => $baseTypeDefinition['label'],
            'preview' => $preview,
            'hasLabel' => $this->hasLabel(),
            'hasConditions' => $this->hasConditions(),
            'isCosmetic' => $baseTypeDefinition['isCosmetic'],
            'isSynced' => $this->getIsSynced(),
            'isParentField' => $baseTypeDefinition['isParentField'],
            'isFixedParentField' => $baseTypeDefinition['isFixedParentField'],
            'isContainerParentField' => $baseTypeDefinition['isContainerParentField'],
            'isRepeatableParentField' => $baseTypeDefinition['isRepeatableParentField'],
            'isChildField' => $baseTypeDefinition['isChildField'],
            'hasEditableFields' => $baseTypeDefinition['hasEditableFields'],
            'isPickable' => $baseTypeDefinition['isPickable'],
            'labelPositions' => Formie::$plugin->getFields()->getLabelPositionsOptions($this),
            'instructionsPositions' => Formie::$plugin->getFields()->getInstructionsPositionsOptions($this),
            'referenceConfig' => $referenceConfig,
            'variableSourceConfig' => $variableSourceConfig,

            // Load in the regular field data, but for a new field
            'newField' => $newField,

            // Add in any extra data the field settings require
            'data' => $configData,
        ];

        if ($includeSchemaIndex) {
            $config['schemaIndex'] = $compiledSchema;
        } else if (isset($config['data']['nestedLayoutBuilder']['editorSchemaByType'])) {
            unset($config['data']['nestedLayoutBuilder']['editorSchemaByType']);
        }

        return $config;
    }

    public function getFieldTypeListConfig(): array
    {
        return static::getFieldTypeDefinition();
    }

    public function getFieldTypePreviewConfig(): array
    {
        $preview = $this->getFormBuilderPreviewSchema();

        return [
            ...static::getFieldTypeDefinition(),
            'preview' => $preview,
            'hasLabel' => $this->hasLabel(),
        ];
    }

    public function getFieldTypeHydrationConfig(): array
    {
        $schemaIndex = SchemaHelper::compileSchema($this->getFormBuilderSchema());
        $configData = $this->getFieldTypeConfigData();

        return [
            'type' => get_class($this),
            'schemaIndex' => $schemaIndex,
            'data' => $configData,
        ];
    }

    public function getFormBuilderPreviewSchema(): array
    {
        $previewSchema = SchemaHelper::normalizePreviewSchema($this->defineFormBuilderPreviewSchema());

        if ($previewSchema !== []) {
            return $previewSchema;
        }

        $legacyPreview = trim($this->getFormBuilderPreviewHtml());

        if ($legacyPreview === '') {
            return [
                SchemaHelper::previewMessage(Craft::t('formie', 'Preview unavailable.')),
            ];
        }

        $isLegacyVueTemplate = $this->_isLegacyVuePreviewTemplate($legacyPreview);
        $deprecationMessage = $isLegacyVueTemplate
            ? 'Legacy Vue-based field preview templates are no longer supported in the form builder. Replace `getFormBuilderPreviewHtml()` with `defineFormBuilderPreviewSchema()`.'
            : 'Legacy field preview templates are no longer supported in the form builder. Replace `getFormBuilderPreviewHtml()` with `defineFormBuilderPreviewSchema()`.';

        Craft::$app->getDeprecator()->log(static::class . '::previewTemplate', $deprecationMessage);

        return [
            SchemaHelper::previewLegacyTemplateNotice([
                'message' => $isLegacyVueTemplate
                    ? Craft::t('formie', 'This field still uses a legacy Vue preview template. Replace it with `defineFormBuilderPreviewSchema()`.')
                    : Craft::t('formie', 'This field still uses a legacy template-string preview. Replace it with `defineFormBuilderPreviewSchema()`.'),
            ]),
        ];
    }

    public function getDefaultableSettingsSchema(): array
    {
        $names = $this->supportedDefaults();

        if ($names === []) {
            return [];
        }

        $tabSchemas = array_values(array_filter([
            $this->defineFormBuilderGeneralSchema(),
            $this->defineFormBuilderSettingsSchema(),
            $this->defineFormBuilderAppearanceSchema(),
            $this->defineFormBuilderAdvancedSchema(),
        ]));

        return SchemaHelper::extractSettingsSchema($tabSchemas, $names);
    }

    public function getSupportedDefaults(): array
    {
        return $this->supportedDefaults();
    }

    public function defineFormBuilderPreviewSchema(): array
    {
        return [];
    }

    public function getFormBuilderPreviewHtml(): string
    {
        return '';
    }

    public function getFieldTypeConfigData(): array
    {
        return [];
    }

    public function getPreviewTemplateHtml(string $templatePath): string
    {
        if (isset(self::$_previewTemplateCache[$templatePath])) {
            return self::$_previewTemplateCache[$templatePath];
        }

        $templateContents = FileHelper::readTemplateContents($templatePath, View::TEMPLATE_MODE_CP, __METHOD__);
        self::$_previewTemplateCache[$templatePath] = $templateContents ?? '';

        return self::$_previewTemplateCache[$templatePath];
    }

    public function getFormBuilderConfig(): array
    {
        $config = $this->getFormBuilderSettings();

        // Allow fields to modify the settings
        $config = $this->modifyFieldSettings($config);

        // Fire a 'modifyFieldConfig' event
        $event = new ModifyFieldConfigEvent([
            'config' => $config,
        ]);
        $this->trigger(self::EVENT_MODIFY_FIELD_CONFIG, $event);

        return $event->config;
    }

    public function getFormBuilderSettings(): array
    {
        $settings = $this->getSettings();
        $settings['id'] = $this->id;
        $settings['fieldId'] = $this->fieldId;
        $settings['layoutId'] = $this->layoutId;
        $settings['pageId'] = $this->pageId;
        $settings['rowId'] = $this->rowId;
        $settings['syncId'] = $this->getIsSynced() ? ($this->fieldId ?? $this->syncId) : null;
        $settings['label'] = $this->label;
        $settings['handle'] = $this->handle;
        $settings['reference'] = $this->reference;
        $settings['type'] = get_class($this);

        return $settings;
    }

    public function modifyFieldSettings(array $settings): array
    {
        return $settings;
    }

    public function getFormBuilderSchema(): array
    {
        $tabs = [
            [
                'handle' => 'general',
                'label' => Craft::t('formie', 'General'),
                'content' => $this->defineFormBuilderGeneralSchema(),
            ],
            [
                'handle' => 'settings',
                'label' => Craft::t('formie', 'Settings'),
                'content' => $this->defineFormBuilderSettingsSchema(),
            ],
            [
                'handle' => 'appearance',
                'label' => Craft::t('formie', 'Appearance'),
                'content' => $this->defineFormBuilderAppearanceSchema(),
            ],
            [
                'handle' => 'advanced',
                'label' => Craft::t('formie', 'Advanced'),
                'content' => $this->defineFormBuilderAdvancedSchema(),
            ],
            [
                'handle' => 'conditions',
                'label' => Craft::t('formie', 'Conditions'),
                'content' => $this->defineFormBuilderConditionsSchema(),
            ],
        ];

        // Filter out tabs with empty content
        $tabs = array_values(array_filter($tabs, function ($tab) {
            return $tab['content'];
        }));

        $tabs = array_map(function($tab) {
            if (isset($tab['content'])) {
                $tab['content'] = SchemaHelper::schemaNode($tab['content']);
            }

            return $tab;
        }, $tabs);

        // Fire a 'modifyFieldSchema' event
        $event = new ModifyFieldSchemaEvent([
            'tabs' => $tabs,
        ]);
        $this->trigger(self::EVENT_MODIFY_FIELD_SCHEMA, $event);

        return SchemaHelper::normalizeSchema(SchemaHelper::modalTabs($event->tabs));
    }

    public function defineFormBuilderGeneralSchema(): array
    {
        return FieldCompatibility::resolveLegacySchema($this, 'defineGeneralSchema', 'defineFormBuilderGeneralSchema');
    }

    public function defineFormBuilderSettingsSchema(): array
    {
        return FieldCompatibility::resolveLegacySchema($this, 'defineSettingsSchema', 'defineFormBuilderSettingsSchema');
    }

    public function defineFormBuilderAppearanceSchema(): array
    {
        return FieldCompatibility::resolveLegacySchema($this, 'defineAppearanceSchema', 'defineFormBuilderAppearanceSchema');
    }

    public function defineFormBuilderAdvancedSchema(): array
    {
        return FieldCompatibility::resolveLegacySchema($this, 'defineAdvancedSchema', 'defineFormBuilderAdvancedSchema');
    }

    public function defineFormBuilderConditionsSchema(): array
    {
        return FieldCompatibility::resolveLegacySchema($this, 'defineConditionsSchema', 'defineFormBuilderConditionsSchema');
    }


    // Protected Methods
    // =========================================================================

    protected function supportedDefaults(): array
    {
        return [];
    }


    // Private Methods
    // =========================================================================

    private function _isLegacyVuePreviewTemplate(string $template): bool
    {
        return str_contains($template, '${ ')
            || str_contains($template, ':value')
            || str_contains($template, 'v-if')
            || str_contains($template, 'v-show')
            || str_contains($template, 'v-text')
            || str_contains($template, 'v-html')
            || str_contains($template, 'v-model')
            || str_contains($template, 'v-for')
            || str_contains($template, 'v-bind:')
            || str_contains($template, 'field.settings.');
    }
}
