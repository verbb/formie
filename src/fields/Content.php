<?php
namespace verbb\formie\fields;

use verbb\formie\base\CosmeticField;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\Gql as FormieGql;
use verbb\formie\helpers\RichTextHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\gql\types\Json as JsonType;
use verbb\formie\models\Notification;
use verbb\formie\models\RichText;
use verbb\formie\models\SlotTag;
use verbb\formie\positions\Hidden as HiddenPosition;

use verbb\formie\theme\context\RenderContext;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\Html as CraftHtml;
use craft\helpers\HTMLPurifier;
use craft\helpers\Template;

use GraphQL\Type\Definition\Type;

class Content extends CosmeticField
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Rich Text');
    }

    public static function translatableProperties(): array
    {
        return ['content'];
    }

    public static function translatableRichTextProperties(): array
    {
        return ['content'];
    }

    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/content/icon.svg';
    }

    public static function defineFieldType(): array
    {
        return array_merge(parent::defineFieldType(), [
            'hasLabel' => true,
        ]);
    }


    // Properties
    // =========================================================================

    public RichText $content;


    // Public Methods
    // =========================================================================

    public function __construct(array $config = [])
    {
        $config['labelPosition'] = $config['labelPosition'] ?? HiddenPosition::class;
        $config['content'] = RichText::from($config['content'] ?? null);

        parent::__construct($config);
    }

    public function hasLabel(): bool
    {
        return true;
    }

    public function setAttributes($values, $safeOnly = true): void
    {
        if (is_array($values) && array_key_exists('content', $values)) {
            $values['content'] = RichText::from($values['content']);
        }

        parent::setAttributes($values, $safeOnly);
    }

    public function getSettings(): array
    {
        $settings = parent::getSettings();
        $settings['content'] = $this->content->getSchema();

        return $settings;
    }

    public function getRenderedContentBlock(Form $form, mixed $value, ?ElementInterface $submission = null): string
    {
        if ($this->content->isEmpty()) {
            return '';
        }

        $submissionElement = $submission instanceof Submission ? $submission : null;
        $html = $this->content->toHtml($submissionElement, false);

        return HTMLPurifier::process($html);
    }

    public function defineFormBuilderPreviewSchema(): array
    {
        return [
            SchemaHelper::previewContent(),
        ];
    }

    public function getReferenceBlockHtml(Submission $submission, Notification $notification, mixed $value, array $renderOptions = []): string|null|bool
    {
        $form = $submission->getForm();

        if (!$form) {
            return false;
        }

        $html = $this->getRenderedContentBlock($form, $value, $submission);

        if ($html === '') {
            return false;
        }

        return Template::raw($html);
    }

    public function isValueEmpty(mixed $value, ?ElementInterface $element): bool
    {
        return $this->content->isEmpty();
    }

    public function getSettingGqlTypes(): array
    {
        return array_merge(parent::getSettingGqlTypes(), [
            'contentHtml' => [
                'name' => 'contentHtml',
                'type' => Type::string(),
                'resolve' => function(self $field) {
                    return $field->content->toHtml(null, false);
                },
            ],
            'contentJson' => [
                'name' => 'contentJson',
                'type' => JsonType::getType(),
                'resolve' => static fn(self $field) => FormieGql::resolveRichTextJson($field->content),
            ],
        ]);
    }

    public function defineFormBuilderGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField(),
            SchemaHelper::richTextField(array_merge([
                'label' => Craft::t('formie', 'Content'),
                'instructions' => Craft::t('formie', 'Enter formatted content to be rendered for this field.'),
                'name' => 'content',
                'validation' => 'requiredRichText',
                'required' => true,
            ], RichTextHelper::getRichTextConfig('fields.content'))),
            SchemaHelper::includeInEmailFieldSummariesField(),
        ];
    }

    public function defineFormBuilderAppearanceSchema(): array
    {
        return [
            SchemaHelper::visibility(),
            SchemaHelper::labelPosition($this),
            SchemaHelper::instructions(),
            SchemaHelper::instructionsPosition($this),
            SchemaHelper::errorMessagePosition($this),
        ];
    }

    public function defineFormBuilderAdvancedSchema(): array
    {
        return [
            SchemaHelper::handleField(),
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

    public function modifyFieldSettings(array $settings): array
    {
        $form = $this->getForm();

        if ($form) {
            $settings['_builderPreviewHtml'] = $this->getRenderedContentBlock($form, null, null);
        }

        return $settings;
    }


    // Protected Methods
    // =========================================================================

    protected function defineFieldSlotTag(string $key, RenderContext $context): ?SlotTag
    {
        if ($key === 'fieldLabel') {
            $labelPosition = $context->get('labelPosition');

            if ($labelPosition instanceof HiddenPosition) {
                return null;
            }

            return SlotTag::make('label')
                ->core([
                    'data-formie-label' => true,
                    'data-formie-field-label' => true,
                    'data-formie-content-field-label' => true,
                ])
                ->theme([
                    'class' => [
                        'formie-label',
                        'formie-field-label',
                        'formie-content-field-label',
                    ],
                ]);
        }

        return parent::defineFieldSlotTag($key, $context);
    }

    protected function defineSubmissionHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        $submission = $element instanceof Submission ? $element : null;
        $form = $submission?->getForm();

        if (!$form) {
            return '';
        }

        $html = $this->getRenderedContentBlock($form, $value, $submission);

        if ($html === '') {
            return CraftHtml::tag('p', Craft::t('formie', 'No content configured.'), [
                'class' => 'light',
            ]);
        }

        return CraftHtml::tag('div', $html, [
            'class' => ['formie-cp-cosmetic-field-preview'],
        ]);
    }
}
