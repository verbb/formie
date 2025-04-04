<?php
namespace verbb\formie\gql\arguments;

use verbb\formie\Formie;
use verbb\formie\elements\Submission;

use Craft;
use craft\gql\base\ElementArguments;

use GraphQL\Type\Definition\Type;

class SubmissionArguments extends ElementArguments
{
    // Static Methods
    // =========================================================================

    public static function getArguments(): array
    {
        return array_merge(parent::getArguments(), self::getContentArguments(), [
            'form' => [
                'name' => 'form',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on the submission’s form handle.',
            ],
            'status' => [
                'name' => 'status',
                'type' => Type::string(),
                'description' => 'Narrows the query results based on the submission’s status.',
            ],
            'statusId' => [
                'name' => 'statusId',
                'type' => Type::int(),
                'description' => 'Narrows the query results based on the submission’s status ID.',
            ],
            'siteId' => [
                'name' => 'siteId',
                'type' => Type::int(),
                'description' => 'Narrows the query results based on the submission’s site ID.',
            ],
            'isIncomplete' => [
                'name' => 'isIncomplete',
                'type' => Type::boolean(),
                'description' => 'Narrows the query results based on the submission’s incomplete state.',
            ],
            'isSpam' => [
                'name' => 'isSpam',
                'type' => Type::boolean(),
                'description' => 'Narrows the query results based on the submission’s spam state.',
            ],
        ]);
    }

    public static function getContentArguments(): array
    {
        $arguments = [];

        foreach (Formie::$plugin->getFields()->getAllFields() as $field) {
            $arguments[$field->handle] = $field->getContentGqlQueryArgumentType();
        }

        return array_merge(parent::getContentArguments(), $arguments);
    }
}
