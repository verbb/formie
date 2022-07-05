<?php
namespace verbb\formie\fields\formfields;

use verbb\formie\base\FormField;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\HtmlTag;
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

    public ?string $borderStyle = 'solid';
    public ?int $borderWidth = 1;
    public ?string $borderColor = '#cccccc';


    // Public Methods
    // =========================================================================

    public function getIsCosmetic(): bool
    {
        return true;
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
            'borderColor' => '#cccccc',
        ];
    }

    /**
     * @inheritDoc
     */
    public function getInputHtml(mixed $value, ?ElementInterface $element = null): string
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
            'field' => $this,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getEmailHtml(Submission $submission, Notification $notification, mixed $value, array $renderOptions = []): string|null|bool
    {
        return Html::tag('hr');
    }

    /**
     * @inheritDoc
     */
    public function afterCreateField(array $data): void
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
            SchemaHelper::visibility(),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Border'),
                'help' => Craft::t('formie', 'Add a border to this section.'),
                'name' => 'borderStyle',
                'options' => array_merge(
                    [['label' => Craft::t('formie', 'None'), 'value' => '']],
                    [['label' => Craft::t('formie', 'Solid'), 'value' => 'solid']],
                    [['label' => Craft::t('formie', 'Dashed'), 'value' => 'dashed']]
                ),
            ]),
            SchemaHelper::numberField([
                'label' => Craft::t('formie', 'Border Width'),
                'help' => Craft::t('formie', 'Set the border width (in pixels).'),
                'name' => 'borderWidth',
                'if' => '$get(borderStyle).value',
                'sections-schema' => [
                    'suffix' => [
                        '$el' => 'span',
                        'attrs' => ['class' => 'fui-suffix-text'],
                        'children' => Craft::t('formie', 'px'),
                    ],
                ],
            ]),
            SchemaHelper::textField([
                '$formkit' => 'color',
                'label' => Craft::t('formie', 'Border Color'),
                'help' => Craft::t('formie', 'Set the border color.'),
                'name' => 'borderColor',
                'size' => '4',
                'inputClass' => 'text fui-color-field',
                'if' => '$get(borderStyle).value',
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

    public function defineConditionsSchema(): array
    {
        return [
            SchemaHelper::enableConditionsField(),
            SchemaHelper::conditionsField(),
        ];
    }

    public function defineHtmlTag(string $key, array $context = []): ?HtmlTag
    {
        $form = $context['form'] ?? null;

        $id = $this->getHtmlId($form);
        $dataId = $this->getHtmlDataId($form);

        if ($key === 'fieldSection') {
            return new HtmlTag('hr', array_merge([
                'class' => 'fui-hr',
                'style' => [
                    'border-top-style' => $this->borderStyle ? $this->borderStyle : false,
                    'border-top-width' => $this->borderWidth ? $this->borderWidth . 'px' : false,
                    'border-top-color' => $this->borderColor ? $this->borderColor : false,
                ],
            ], $this->getInputAttributes()));
        }

        return parent::defineHtmlTag($key, $context);
    }
}
