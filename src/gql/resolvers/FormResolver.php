<?php
namespace verbb\formie\gql\resolvers;

use verbb\formie\elements\Form;
use verbb\formie\helpers\Gql as GqlHelper;

use craft\gql\base\ElementResolver;
use craft\helpers\Db;

class FormResolver extends ElementResolver
{
    // Public Methods
    // =========================================================================

    public static function prepareQuery(mixed $source, array $arguments, $fieldName = null): mixed
    {
        if ($source === null) {
            $query = Form::find();
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

        if (!GqlHelper::canQueryForms()) {
            return [];
        }

        if (!GqlHelper::canSchema('formieForms.all')) {
            $query->andWhere(['in', 'elements.id', array_values(Db::idsByUids('{{%formie_forms}}', $pairs['formieForms']))]);
        }

        return $query;
    }
}
