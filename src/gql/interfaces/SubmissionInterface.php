<?php
namespace verbb\formie\gql\interfaces;

use verbb\formie\elements\Submission;
use verbb\formie\gql\types\generators\SubmissionGenerator;
use verbb\formie\gql\arguments\FieldArguments;
use verbb\formie\gql\arguments\SubmissionArguments;
use verbb\formie\gql\interfaces\FieldInterface;
use verbb\formie\gql\interfaces\PageInterface;
use verbb\formie\gql\interfaces\RowInterface;
use verbb\formie\gql\interfaces\SubmissionInterface as SubmissionInterfaceLocal;

use craft\gql\base\InterfaceType as BaseInterfaceType;
use craft\gql\interfaces\Element;
use craft\gql\types\DateTime;
use craft\gql\TypeLoader;
use craft\gql\TypeManager;
use craft\gql\GqlEntityRegistry;
use craft\helpers\Gql;

use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;

class SubmissionInterface extends Element
{
    // Public Methods
    // =========================================================================

    public static function getTypeGenerator(): string
    {
        return SubmissionGenerator::class;
    }

    public static function getType($fields = null): Type
    {
        if ($type = GqlEntityRegistry::getEntity(self::getName())) {
            return $type;
        }

        $type = GqlEntityRegistry::createEntity(self::getName(), new InterfaceType([
            'name' => static::getName(),
            'fields' => self::class . '::getFieldDefinitions',
            'description' => 'This is the interface implemented by all submissions.',
            'resolveType' => function(Submission $value) {
                return $value->getGqlTypeName();
            },
        ]));

        SubmissionGenerator::generateTypes();

        return $type;
    }

    public static function getName(): string
    {
        return 'SubmissionInterface';
    }

    public static function getFieldDefinitions(): array
    {
        return TypeManager::prepareFieldDefinitions(array_merge(parent::getFieldDefinitions(), [
            'status' => [
                'name' => 'status',
                'type' => Type::string(),
                'description' => 'The submission’s status.'
            ],
            'statusId' => [
                'name' => 'statusId',
                'type' => Type::int(),
                'description' => 'The submission’s status ID.'
            ],
        ]), self::getName());
    }
}
