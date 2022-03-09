<?php
namespace verbb\formie\gql\resolvers;

use verbb\formie\elements\Submission;
use verbb\formie\helpers\Gql as GqlHelper;

use craft\gql\base\ElementResolver;
use craft\helpers\Db;

class SubmissionResolver extends ElementResolver
{
    // Public Methods
    // =========================================================================

    public static function prepareQuery(mixed $source, array $arguments, $fieldName = null): mixed
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

        $pairs = GqlHelper::extractAllowedEntitiesFromSchema('read');

        if (!GqlHelper::canQuerySubmissions()) {
            return [];
        }

        if (!GqlHelper::canSchema('formieSubmissions.all')) {
            $query->andWhere(['in', 'formId', array_values(Db::idsByUids('{{%formie_forms}}', $pairs['formieSubmissions']))]);
        }

        return $query;
    }
}
