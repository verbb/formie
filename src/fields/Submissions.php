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

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Submissions (Formie)');
    }

    /**
     * @inheritdoc
     */
    public static function elementType(): string
    {
        return Submission::class;
    }

    /**
     * @inheritdoc
     */
    public static function defaultSelectionLabel(): string
    {
        return Craft::t('formie', 'Add a submission');
    }

    /**
     * @inheritdoc
     */
    public static function valueType(): string
    {
        return SubmissionQuery::class;
    }

    /**
     * @inheritdoc
     */
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
