<?php
namespace verbb\formie\fields;

use verbb\formie\base\CosmeticField;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\models\SlotTag;
use verbb\formie\models\Notification;

use verbb\formie\theme\context\RenderContext;

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

    public function defineFormBuilderPreviewSchema(): array
    {
        return [
            SchemaHelper::previewSection(),
        ];
    }

    public function getReferenceBlockHtml(Submission $submission, Notification $notification, mixed $value, array $renderOptions = []): string|null|bool
    {
        return Html::tag('hr');
    }

    public function isValueEmpty(mixed $value, ?ElementInterface $element): bool
    {
        return false;
    }

    public function afterCreateField(array $data): void
    {
        $this->label = $this->label ?? StringHelper::appendRandomString(Craft::t('formie', 'Summary '), 15);
        $this->handle = $this->handle ?? StringHelper::appendRandomString('summaryHandle', 15);
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

    public function defineFormBuilderAppearanceSchema(): array
    {
        return [
            SchemaHelper::visibility(),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Border'),
                'instructions' => Craft::t('formie', 'Add a border to this section.'),
                'name' => 'borderStyle',
                'options' => array_merge(
                    [['label' => Craft::t('formie', 'None'), 'value' => '']],
                    [['label' => Craft::t('formie', 'Solid'), 'value' => 'solid']],
                    [['label' => Craft::t('formie', 'Dashed'), 'value' => 'dashed']]
                ),
            ]),
            SchemaHelper::fieldWrap([
                'label' => Craft::t('formie', 'Border Width'),
                'instructions' => Craft::t('formie', 'Set the border width (in pixels).'),
                'if' => 'borderStyle',
                'children' => [
                    SchemaHelper::numberField([
                        'name' => 'borderWidth',
                    ]),
                    [
                        '$el' => 'span',
                        'attrs' => ['class' => 'text-sm text-gray-300'],
                        'children' => Craft::t('formie', 'px'),
                    ],
                ],
            ]),
            SchemaHelper::colorField([
                'label' => Craft::t('formie', 'Border Color'),
                'instructions' => Craft::t('formie', 'Set the border color.'),
                'name' => 'borderColor',
                'if' => 'borderStyle',
            ]),
        ];
    }

    public function defineFormBuilderAdvancedSchema(): array
    {
        return [
            SchemaHelper::includeInEmailFieldSummariesField(),
            SchemaHelper::cssClasses(),
            SchemaHelper::containerAttributesField(),
        ];
    }

    public function defineFormBuilderConditionsSchema(): array
    {
        return [
            SchemaHelper::enableConditionsField(),
            SchemaHelper::conditionsField(),
        ];
    }
    

    // Protected Methods
    // =========================================================================

    protected function defineFieldSlotTag(string $key, RenderContext $context): ?SlotTag
    {
        $form = $context->form;

        $id = $this->getHtmlId($form);
        $dataId = $this->getHtmlDataId($form);

        if ($key === 'fieldSection') {
            return SlotTag::make('hr')
                ->core([
                    'data-formie-section' => true,
                    'style' => [
                        'border-top-style' => $this->borderStyle ? $this->borderStyle : false,
                        'border-top-width' => $this->borderWidth ? $this->borderWidth . 'px' : false,
                        'border-top-color' => $this->borderColor ? $this->borderColor : false,
                    ],
                ])
                ->theme([
                    'class' => [
                        'formie-section',
                    ],
                ])
                ->instanceAttributes($this->getInputAttributes());
        }

        return parent::defineFieldSlotTag($key, $context);
    }

    protected function defineSubmissionHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/section/input', [
            'name' => $this->handle,
            'value' => $value,
            'field' => $this,
        ]);
    }
}
