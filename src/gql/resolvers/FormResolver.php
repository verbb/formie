<?php
namespace verbb\formie\gql\resolvers;

use verbb\formie\elements\Form;
use verbb\formie\helpers\Gql as GqlHelper;

use craft\elements\db\ElementQuery;
use craft\gql\base\ElementResolver;
use craft\helpers\Db;

use Illuminate\Support\Collection;

class FormResolver extends ElementResolver
{
    // Static Methods
    // =========================================================================

    public static function prepareQuery(mixed $source, array $arguments, $fieldName = null): mixed
    {
        if ($source === null) {
            $query = Form::find();
        } else {
            $query = $source->$fieldName;
        }

        if (!$query instanceof ElementQuery) {
            return $query;
        }

        foreach ($arguments as $key => $value) {
            $query->$key($value);
        }

        $pairs = GqlHelper::extractAllowedEntitiesFromSchema('read');

        if (!GqlHelper::canQueryForms()) {
            return Collection::empty();
        }

        if (!GqlHelper::canSchema('formieForms.all')) {
            $query->andWhere(['in', 'elements.id', array_values(Db::idsByUids('{{%formie_forms}}', $pairs['formieForms']))]);
        }

        return $query;
    }
}
