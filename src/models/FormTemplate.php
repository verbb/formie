<?php
namespace verbb\formie\models;

use Craft;
use craft\behaviors\FieldLayoutBehavior;
use craft\helpers\FileHelper;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;

use yii\base\InvalidConfigException;
use yii\behaviors\AttributeTypecastBehavior;
use yii\validators\Validator;

use verbb\formie\elements\Form;
use verbb\formie\records\FormTemplate as FormTemplateRecord;

class FormTemplate extends BaseTemplate
{
    // Constants
    // =========================================================================

    const PAGE_HEADER = 'page-header';
    const PAGE_FOOTER = 'page-footer';
    const INSIDE_FORM = 'inside-form';
    const MANUAL = 'manual';

    // Public Properties
    // =========================================================================

    public $fieldLayoutId;
    public $useCustomTemplates = false;
    public $outputCssLayout = true;
    public $outputCssTheme = true;
    public $outputJsBase = true;
    public $outputJsTheme = true;
    public $outputCssLocation = self::PAGE_HEADER;
    public $outputJsLocation = self::PAGE_FOOTER;


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
        /** @var FieldLayoutBehavior $behavior */
        $behavior = $this->getBehavior('fieldLayout');
        return $behavior->getFieldLayout();
    }

    /**
     * Sets the template's field layout.
     *
     * @param FieldLayout $fieldLayout
     */
    public function setFieldLayout(FieldLayout $fieldLayout)
    {
        /** @var FieldLayoutBehavior $behavior */
        $behavior = $this->getBehavior('fieldLayout');
        return $behavior->setFieldLayout($fieldLayout);
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

        $typecastBehavior = $behaviors['typecast'];
        $typecastBehavior['attributeTypes']['outputCssLayout'] = AttributeTypecastBehavior::TYPE_BOOLEAN;
        $typecastBehavior['attributeTypes']['outputCssTheme'] = AttributeTypecastBehavior::TYPE_BOOLEAN;
        $typecastBehavior['attributeTypes']['outputJsBase'] = AttributeTypecastBehavior::TYPE_BOOLEAN;
        $typecastBehavior['attributeTypes']['outputJsTheme'] = AttributeTypecastBehavior::TYPE_BOOLEAN;

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
