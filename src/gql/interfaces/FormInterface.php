<?php
namespace verbb\formie\gql\interfaces;

use verbb\formie\elements\Form;
use verbb\formie\gql\types\generators\FormGenerator;
use verbb\formie\gql\arguments\FieldArguments;
use verbb\formie\gql\arguments\FormArguments;
use verbb\formie\gql\interfaces\FieldInterface;
use verbb\formie\gql\interfaces\PageInterface;
use verbb\formie\gql\interfaces\RowInterface;
use verbb\formie\gql\interfaces\FormInterface as FormInterfaceLocal;

use craft\gql\base\InterfaceType as BaseInterfaceType;
use craft\gql\interfaces\Element;
use craft\gql\types\DateTime;
use craft\gql\TypeLoader;
use craft\gql\TypeManager;
use craft\gql\GqlEntityRegistry;
use craft\helpers\Gql;

use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;

class FormInterface extends Element
{
    // Public Methods
    // =========================================================================

    public static function getTypeGenerator(): string
    {
        return FormGenerator::class;
    }

    public static function getType($fields = null): Type
    {
        if ($type = GqlEntityRegistry::getEntity(self::getName())) {
            return $type;
        }

        $type = GqlEntityRegistry::createEntity(self::getName(), new InterfaceType([
            'name' => static::getName(),
            'fields' => self::class . '::getFieldDefinitions',
            'description' => 'This is the interface implemented by all forms.',
            'resolveType' => function(Form $value) {
                return $value->getGqlTypeName();
            },
        ]));

        FormGenerator::generateTypes();

        return $type;
    }

    public static function getName(): string
    {
        return 'FormInterface';
    }

    public static function getFieldDefinitions(): array
    {
        return TypeManager::prepareFieldDefinitions(array_merge(parent::getFieldDefinitions(), [
            'handle' => [
                'name' => 'handle',
                'type' => Type::string(),
                'description' => 'The form’s handle.'
            ],
            'pages' => [
                'name' => 'pages',
                'type' => Type::listOf(PageInterface::getType()),
                'description' => 'The form’s pages.'
            ],
            'rows' => [
                'name' => 'rows',
                'type' => Type::listOf(RowInterface::getType()),
                'description' => 'The form’s rows.'
            ],
            'fields' => [
                'name' => 'fields',
                'type' => Type::listOf(FieldInterface::getType()),
                'description' => 'The form’s fields.'
            ],
        ]), self::getName());
    }
}
