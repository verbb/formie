<?php
namespace verbb\formie\fields;

use verbb\formie\Formie;
use verbb\formie\base\ContainerParentFieldInterface;
use verbb\formie\base\Field;
use verbb\formie\base\Integration;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\base\ContainerParentField;
use verbb\formie\base\ParentFieldInterface;
use verbb\formie\elements\Submission;
use verbb\formie\gql\resolvers\elements\NestedFieldRowResolver;
use verbb\formie\gql\types\generators\NestedFieldGenerator;
use verbb\formie\gql\types\input\GroupInputType;
use verbb\formie\fields\definitions\FieldReferences;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\Variables;
use verbb\formie\models\DynamicModel;
use verbb\formie\models\SlotTag;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\Notification;
use verbb\formie\positions\Hidden as HiddenPosition;

use verbb\formie\theme\context\RenderContext;

use Craft;
use craft\base\EagerLoadingFieldInterface;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\gql\GqlEntityRegistry;
use craft\helpers\Gql;
use craft\helpers\Template;

use Faker\Generator as FakerFactory;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

use yii\validators\Validator;

class Group extends ContainerParentField implements ContainerParentFieldInterface
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Group');
    }

    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/group/icon.svg';
    }

    public static function gqlTypeNameByContext(mixed $context): string
    {
        return ($context->getForm()->handle ?? '') . '_' . $context->handle . '_FormieGroupField';
    }

    public static function supportsGqlConfigProvider(): bool
    {
        return true;
    }

    public static function gqlContentMutationArgumentTypeFromConfig(array $config): Type|array
    {
        return GroupInputType::getTypeFromConfig($config);
    }

    public static function gqlContentTypeFromConfig(array $config): array|Type
    {
        $fieldsService = Formie::$plugin->getFields();
        $typeName = $fieldsService->getFieldConfigGqlTypeName($config, 'GroupField');
        $typeArray = [NestedFieldGenerator::generateTypeFromConfig($config)];

        return [
            'name' => $config['handle'] ?? '',
            'type' => Gql::getUnionType($typeName, $typeArray),
            'resolve' => function($submission) use ($config) {
                $value = $submission->getFieldValue($config['handle'] ?? '');

                return new DynamicModel(is_array($value) ? $value : []);
            },
        ];
    }
    

    // Public Methods
    // =========================================================================

    public function defineFormBuilderPreviewSchema(): array
    {
        return [
            SchemaHelper::previewGroup(),
        ];
    }

    public function getConfigJson(): ?string
    {
        // Group fields themselves should not contain the inner field's JS
        return null;
    }

    public function defineFormBuilderGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField(),
        ];
    }

    public function defineFormBuilderAppearanceSchema(): array
    {
        return [
            SchemaHelper::visibility(),
            SchemaHelper::labelPosition($this),
            SchemaHelper::instructions(),
            SchemaHelper::instructionsPosition($this),
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

    public function getContentGqlMutationArgumentType(): Type|array
    {
        return GroupInputType::getType($this);
    }

    public function getContentGqlType(): array|Type
    {
        $typeArray = NestedFieldGenerator::generateTypes($this);
        $typeName = self::gqlTypeNameByContext($this);

        return [
            'name' => $this->handle,
            'type' => Gql::getUnionType($typeName, $typeArray),
            'resolve' => function($submission) {
                // Some fields like the in-built elements (Assets, Entries, etc) will assume the value of a repeater row
                // in an element, but it's not. Instead make it a dynamic model that'll work for the most part.
                $value = $submission->getFieldValue($this->valueKey());

                return new DynamicModel($value);
            },
        ];
    }

    // Protected Methods
    // =========================================================================

    protected function defineFieldSlotTag(string $key, RenderContext $context): ?SlotTag
    {
        $form = $context->form;

        $id = $this->getHtmlId($form);

        if ($key === 'fieldLayout') {
            return SlotTag::make('fieldset')
                ->core([
                    'data-formie-field-layout' => true,
                    'data-formie-group-field-layout' => true,
                    'aria-describedby' => $this->instructions ? "{$id}-instructions" : null,
                ])
                ->theme([
                    'class' => [
                        'formie-field-layout',
                        'formie-group-field-layout',
                    ],
                ]);
        }

        if ($key === 'fieldLabel') {
            $labelPosition = $context->get('labelPosition');

            return SlotTag::make('legend')
                ->core([
                    'data-formie-label' => true,
                    'data-formie-field-label' => true,
                    'data-formie-group-field-label' => true,
                    'data-formie-sr-only' => $labelPosition instanceof HiddenPosition ? true : false,
                ])
                ->theme([
                    'class' => [
                        'formie-label',
                        'formie-field-label',
                        'formie-group-field-label',
                        $labelPosition instanceof HiddenPosition ? 'formie-sr-only' : false,
                    ],
                ]);
        }

        if ($key === 'nestedFieldContainer') {
            return SlotTag::make('div')
                ->core([
                    'data-formie-nested-field-container' => true,
                    'data-formie-group-container' => true,
                ])
                ->theme([
                    'class' => [
                        'formie-nested-field-container',
                        'formie-group-container',
                    ],
                ]);
        }

        return parent::defineFieldSlotTag($key, $context);
    }

    protected function defineSubmissionHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/group/input', [
            'element' => $element,
            'name' => $this->handle,
            'value' => $value,
            'field' => $this,
        ]);
    }

    protected function defineValueForEmailPreview(FakerFactory $faker): mixed
    {
        return Formie::$plugin->getSubmissions()->getFakeFieldContent($this->getFields());
    }

    protected function getNestedLayoutBuilderDisallowedFieldTypes(): array
    {
        return [
            self::class,
            Repeater::class,
        ];
    }

    protected function defineAllowPrimaryReference(): bool
    {
        return false;
    }

    protected function defineAllowNestedReference(): bool
    {
        return true;
    }

    protected function defineNestedReferenceMode(): string
    {
        return 'childrenOnly';
    }
}
