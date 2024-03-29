<?php
namespace verbb\formie\fields;

use verbb\formie\base\CosmeticField;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\models\HtmlTag;
use verbb\formie\models\Notification;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\Html;

use GraphQL\Type\Definition\Type;

class Section extends CosmeticField
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Section');
    }

    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/section/icon.svg';
    }


    // Properties
    // =========================================================================

    public ?string $borderStyle = 'solid';
    public ?int $borderWidth = 1;
    public ?string $borderColor = '#cccccc';


    // Public Methods
    // =========================================================================

    public function getPreviewInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/section/preview', [
            'field' => $this,
        ]);
    }

    public function getEmailHtml(Submission $submission, Notification $notification, mixed $value, array $renderOptions = []): string|null|bool
    {
        return Html::tag('hr');
    }

    public function afterCreateField(array $data): void
    {
        $this->label = $this->label ?? StringHelper::appendUniqueIdentifier(Craft::t('formie', 'Section Label '));
        $this->handle = $this->handle ?? StringHelper::appendUniqueIdentifier('sectionHandle');
    }

    public function getSettingGqlTypes(): array
    {
        return array_merge(parent::getSettingGqlTypes(), [
            'borderStyle' => [
                'name' => 'borderStyle',
                'type' => Type::string(),
            ],
            'borderWidth' => [
                'name' => 'borderWidth',
                'type' => Type::int(),
            ],
            'borderColor' => [
                'name' => 'borderColor',
                'type' => Type::string(),
            ],
        ]);
    }

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

    public function defineAdvancedSchema(): array
    {
        return [
            SchemaHelper::includeInEmailField(),
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
            return new HtmlTag('hr', [
                'class' => 'fui-hr',
                'style' => [
                    'border-top-style' => $this->borderStyle ? $this->borderStyle : false,
                    'border-top-width' => $this->borderWidth ? $this->borderWidth . 'px' : false,
                    'border-top-color' => $this->borderColor ? $this->borderColor : false,
                ],
            ], $this->getInputAttributes());
        }

        return parent::defineHtmlTag($key, $context);
    }


    // Protected Methods
    // =========================================================================

    protected function cpInputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/section/input', [
            'name' => $this->handle,
            'value' => $value,
            'field' => $this,
        ]);
    }
}
