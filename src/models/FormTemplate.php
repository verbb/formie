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

    /**
     * Returns the CP URL for editing the template.
     *
     * @return string
     */
    public function getCpEditUrl(): string
    {
        return UrlHelper::cpUrl('formie/settings/form-templates/edit/' . $this->id);
    }

    /**
     * Returns true if the template is allowed to be deleted.
     *
     * @return bool
     */
    public function canDelete(): bool
    {
        return !Form::find()->trashed(null)->template($this)->one();
    }

    /**
     * Returns the template's field layout.
     *
     * @return FieldLayout
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function getFieldLayout(): FieldLayout
    {
        if ($this->_fieldLayout !== null) {
            return $this->_fieldLayout;
        }

        /** @var FieldLayoutBehavior $behavior */
        $behavior = $this->getBehavior('fieldLayout');

        return $this->_fieldLayout = $behavior->getFieldLayout();
    }

    /**
     * Sets the template's field layout.
     *
     * @param FieldLayout $fieldLayout
     */
    public function setFieldLayout(FieldLayout $fieldLayout): void
    {
        /** @var FieldLayoutBehavior $behavior */
        $behavior = $this->getBehavior('fieldLayout');
        $behavior->setFieldLayout($fieldLayout);

        $this->_fieldLayout = $fieldLayout;
    }

    /**
     * @inheritDoc
     */
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();

        $behaviors['fieldLayout'] = [
            'class' => FieldLayoutBehavior::class,
            'elementType' => Form::class,
        ];

        return $behaviors;
    }

    /**
     * @inheritDoc
     */
    protected function getRecordClass(): string
    {
        return FormTemplateRecord::class;
    }
}
