<?php
namespace verbb\formie\fields;

use verbb\formie\elements\Form;
use verbb\formie\elements\db\FormQuery;
use verbb\formie\gql\arguments\FormArguments;
use verbb\formie\gql\interfaces\FormInterface;
use verbb\formie\gql\resolvers\FormResolver;

use Craft;
use craft\fields\BaseRelationField;

use GraphQL\Type\Definition\Type;

class Forms extends BaseRelationField
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Forms (Formie)');
    }

    /**
     * @inheritdoc
     */
    public static function elementType(): string
    {
        return Form::class;
    }

    /**
     * @inheritdoc
     */
    public static function defaultSelectionLabel(): string
    {
        return Craft::t('formie', 'Add a form');
    }

    /**
     * @inheritdoc
     */
    public static function valueType(): string
    {
        return FormQuery::class;
    }


    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getContentGqlType(): array|Type
    {
        return [
            'name' => $this->handle,
            'type' => Type::listOf(FormInterface::getType()),
            'args' => FormArguments::getArguments(),
            'resolve' => FormResolver::class . '::resolve',
        ];
    }
}
