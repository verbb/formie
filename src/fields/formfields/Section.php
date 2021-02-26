<?php
namespace verbb\formie\fields\formfields;

use verbb\formie\base\FormField;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\Notification;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\Html;
use craft\helpers\StringHelper;

class Section extends FormField
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Section');
    }

    /**
     * @inheritDoc
     */
    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/section/icon.svg';
    }

    /**
     * @inheritDoc
     */
    public static function hasContentColumn(): bool
    {
        return false;
    }


    // Properties
    // =========================================================================

    public $borderStyle;
    public $borderWidth;
    public $borderColor;


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function renderLabel(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function hasLabel(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getFieldDefaults(): array
    {
        return [
            'borderStyle' => 'solid',
            'borderWidth' => 1,
            'borderColor' => '#ccc',
        ];
    }

    /**
     * @inheritDoc
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/section/input', [
            'name' => $this->handle,
            'value' => $value,
            'field' => $this,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getPreviewInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/section/preview', [
            'field' => $this
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getEmailHtml(Submission $submission, Notification $notification, $value, array $options = null)
    {
        return Html::tag('hr');
    }

    /**
     * @inheritDoc
     */
    public function afterCreateField(array $data)
    {
        $this->name = StringHelper::appendUniqueIdentifier(Craft::t('formie', 'Section Label '));
        $this->handle = StringHelper::appendUniqueIdentifier(Craft::t('formie', 'sectionHandle'));
    }

    /**
     * @inheritDoc
     */
    public function defineAppearanceSchema(): array
    {
        return [
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Border'),
                'help' => Craft::t('formie', 'Add a border to this section.'),
                'name' => 'borderStyle',
                'options' => array_merge(
                    [[ 'label' => Craft::t('formie', 'None'), 'value' => '' ]],
                    [[ 'label' => Craft::t('formie', 'Solid'), 'value' => 'solid' ]],
                    [[ 'label' => Craft::t('formie', 'Dashed'), 'value' => 'dashed' ]]
                ),
            ]),
            SchemaHelper::toggleContainer('settings.borderStyle', [
                SchemaHelper::textField([
                    'label' => Craft::t('formie', 'Border Width'),
                    'help' => Craft::t('formie', 'Set the border width (in pixels).'),
                    'name' => 'borderWidth',
                    'size' => '3',
                    'class' => 'text',
                    'type' => 'textWithSuffix',
                    'suffix' => Craft::t('formie', 'px'),
                ]),
                SchemaHelper::textField([
                    'label' => Craft::t('formie', 'Border Color'),
                    'help' => Craft::t('formie', 'Set the border color.'),
                    'name' => 'borderColor',
                    'size' => '4',
                    'type' => 'color',
                    'class' => 'text fui-color-field',
                ]),
            ]),
        ];
    }

    /**
     * @inheritDoc
     */
    public function defineAdvancedSchema(): array
    {
        return [
            SchemaHelper::cssClasses(),
            SchemaHelper::containerAttributesField(),
        ];
    }
}
