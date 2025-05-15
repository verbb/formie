<?php
namespace verbb\formie\fields;

use verbb\formie\elements\Submission;
use verbb\formie\elements\db\SubmissionQuery;
use verbb\formie\gql\arguments\SubmissionArguments;
use verbb\formie\gql\interfaces\SubmissionInterface;
use verbb\formie\gql\resolvers\SubmissionResolver;

use Craft;
use craft\fields\BaseRelationField;

use GraphQL\Type\Definition\Type;

class Submissions extends BaseRelationField
{
    // Public Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Submissions (Formie)');
    }

    public static function elementType(): string
    {
        return Submission::class;
    }

    public static function defaultSelectionLabel(): string
    {
        return Craft::t('formie', 'Add a submission');
    }

    public static function valueType(): string
    {
        return SubmissionQuery::class;
    }

    public function getContentGqlType(): array|Type
    {
        return [
            'name' => $this->handle,
            'type' => Type::listOf(SubmissionInterface::getType()),
            'args' => SubmissionArguments::getArguments(),
            'resolve' => SubmissionResolver::class . '::resolve',
        ];
    }
}
