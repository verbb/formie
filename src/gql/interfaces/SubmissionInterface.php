<?php
namespace verbb\formie\gql\interfaces;

use verbb\formie\elements\Submission;
use verbb\formie\gql\types\generators\SubmissionGenerator;

use Craft;
use craft\gql\interfaces\Element;
use craft\gql\GqlEntityRegistry;

use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;

class SubmissionInterface extends Element
{
    // Static Methods
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
        return Craft::$app->getGql()->prepareFieldDefinitions(array_merge(parent::getFieldDefinitions(), [
            'status' => [
                'name' => 'status',
                'type' => Type::string(),
                'description' => 'The submission’s status.',
            ],
            'statusId' => [
                'name' => 'statusId',
                'type' => Type::int(),
                'description' => 'The submission’s status ID.',
            ],
            'ipAddress' => [
                'name' => 'ipAddress',
                'type' => Type::string(),
                'description' => 'The submission’s IP Address.',
            ],
            'isIncomplete' => [
                'name' => 'isIncomplete',
                'type' => Type::boolean(),
                'description' => 'Whether the submission is incomplete.',
            ],
            'isSpam' => [
                'name' => 'isSpam',
                'type' => Type::boolean(),
                'description' => 'Whether the submission is spam.',
            ],
            'spamReason' => [
                'name' => 'spamReason',
                'type' => Type::string(),
                'description' => 'The submission’s spam reason.',
            ],
            'spamClass' => [
                'name' => 'spamClass',
                'type' => Type::string(),
                'description' => 'The submission’s spam type.',
            ],
        ]), self::getName());
    }
}
