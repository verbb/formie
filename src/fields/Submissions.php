<?php
namespace verbb\formie\fields;

use verbb\formie\elements\Submission;
use verbb\formie\elements\db\SubmissionQuery;
use verbb\formie\gql\arguments\SubmissionArguments;
use verbb\formie\gql\interfaces\SubmissionInterface;
use verbb\formie\gql\resolvers\SubmissionResolver;

use Craft;
use craft\elements\ElementCollection;
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

    public static function icon(): string
    {
        return '@verbb/formie/icon-mask.svg';
    }

    public static function elementType(): string
    {
        return Submission::class;
    }

    public static function defaultSelectionLabel(): string
    {
        return Craft::t('formie', 'Add a submission');
    }

    public static function phpType(): string
    {
        return sprintf('\\%s|\\%s<\\%s>', SubmissionQuery::class, ElementCollection::class, Submission::class);
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


    // Constants
    // =========================================================================

    // Added here to back-support Craft <5.9.
    public const VIEW_MODE_LIST = 'list';
    public const VIEW_MODE_LIST_INLINE = 'list-inline';
    

    // Protected Methods
    // =========================================================================

    protected function supportedViewModes(): array
    {
        return [
            self::VIEW_MODE_LIST => Craft::t('app', 'List'),
            self::VIEW_MODE_LIST_INLINE => Craft::t('app', 'Inline list'),
        ];
    }
}
