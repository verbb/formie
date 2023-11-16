<?php
namespace verbb\formie\models;

use craft\behaviors\FieldLayoutBehavior;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;

use verbb\formie\elements\Form;
use verbb\formie\records\FormTemplate as FormTemplateRecord;

class FormTemplate extends BaseTemplate
{
    // Constants
    // =========================================================================

    public const PAGE_HEADER = 'page-header';
    public const PAGE_FOOTER = 'page-footer';
    public const INSIDE_FORM = 'inside-form';
    public const MANUAL = 'manual';

    // Properties
    // =========================================================================

    public ?string $fieldLayoutId = null;
    public bool $useCustomTemplates = false;
    public bool $outputCssLayout = true;
    public bool $outputCssTheme = true;
    public bool $outputJsBase = true;
    public bool $outputJsTheme = true;
    public string $outputCssLocation = self::PAGE_HEADER;
    public string $outputJsLocation = self::PAGE_FOOTER;

    private ?FieldLayout $_fieldLayout = null;


    // Public Methods
    // =========================================================================

    public function getCpEditUrl(): ?string
    {
        return UrlHelper::cpUrl('formie/settings/form-templates/edit/' . $this->id);
    }

    public function canDelete(): bool
    {
        return !Form::find()->trashed(null)->template($this)->one();
    }

    public function getFieldLayout(): FieldLayout
    {
        if ($this->_fieldLayout !== null) {
            return $this->_fieldLayout;
        }

        /** @var FieldLayoutBehavior $behavior */
        $behavior = $this->getBehavior('fieldLayout');

        return $this->_fieldLayout = $behavior->getFieldLayout();
    }

    public function setFieldLayout(FieldLayout $fieldLayout): void
    {
        /** @var FieldLayoutBehavior $behavior */
        $behavior = $this->getBehavior('fieldLayout');
        $behavior->setFieldLayout($fieldLayout);

        $this->_fieldLayout = $fieldLayout;
    }

    public function getConfig(): array
    {
        $config = [
            'name' => $this->name,
            'handle' => $this->handle,
            'template' => $this->template,
            'useCustomTemplates' => $this->useCustomTemplates,
            'outputCssTheme' => $this->outputCssTheme,
            'outputCssLayout' => $this->outputCssLayout,
            'outputJsBase' => $this->outputJsBase,
            'outputJsTheme' => $this->outputJsTheme,
            'outputCssLocation' => $this->outputCssLocation,
            'outputJsLocation' => $this->outputJsLocation,
            'sortOrder' => $this->sortOrder,
        ];

        if (($fieldLayout = $this->getFieldLayout()) && ($fieldLayoutConfig = $fieldLayout->getConfig())) {
            $config['fieldLayouts'] = [
                $fieldLayout->uid => $fieldLayoutConfig,
            ];
        }

        return $config;
    }


    // Protected Methods
    // =========================================================================

    protected function getRecordClass(): string
    {
        return FormTemplateRecord::class;
    }

    protected function defineBehaviors(): array
    {
        $behaviors = parent::defineBehaviors();

        $behaviors['fieldLayout'] = [
            'class' => FieldLayoutBehavior::class,
            'elementType' => Form::class,
        ];

        return $behaviors;
    }
}
