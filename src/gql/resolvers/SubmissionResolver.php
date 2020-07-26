<?php
namespace verbb\formie\gql\resolvers;

use verbb\formie\elements\Submission;

use craft\gql\base\ElementResolver;

use GraphQL\Type\Definition\ResolveInfo;

class SubmissionResolver extends ElementResolver
{
    // Public Methods
    // =========================================================================

    public static function prepareQuery($source, array $arguments, $fieldName = null)
    {
        if ($source === null) {
            $query = Submission::find();
        } else {
            $query = $source->$fieldName;
        }

        if (is_array($query)) {
            return $query;
        }

        foreach ($arguments as $key => $value) {
            $query->$key($value);
        }

        return $query;
    }
}
