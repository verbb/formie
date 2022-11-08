<?php
namespace verbb\formie\gql\resolvers;

use verbb\formie\elements\Submission;
use verbb\formie\helpers\Gql as GqlHelper;

use craft\elements\db\ElementQuery;
use craft\gql\base\ElementResolver;
use craft\helpers\Db;

use GraphQL\Language\AST\InlineFragmentNode;
use GraphQL\Type\Definition\ResolveInfo;

use Illuminate\Support\Collection;

class SubmissionResolver extends ElementResolver
{
    // Static Methods
    // =========================================================================

    public static function prepareQuery(mixed $source, array $arguments, $fieldName = null): mixed
    {
        if ($source === null) {
            $query = Submission::find();
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

        if (!GqlHelper::canQuerySubmissions()) {
            return Collection::empty();
        }

        if (!GqlHelper::canSchema('formieSubmissions.all')) {
            $query->andWhere(['in', 'formId', array_values(Db::idsByUids('{{%formie_forms}}', $pairs['formieSubmissions']))]);
        }

        return $query;
    }

    public static function resolve(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        $query = self::prepareElementQuery($source, $arguments, $context, $resolveInfo);

        // Try and automatically set the submissions' context based on the inline fragment used. This is because submissions
        // require a form context to resolve their custom field values, and sometimes, we don't want to supply the "form:handle"
        // GQL query param for `formieSubmissions`. Instead, because we already use inline fragments (`...on contactForm_Submission`)
        // We can make use of that, and set the form param on the Submission element query.
        // Unfortunately, we don't have access to `$resolveInfo` in `prepareQuery()`.
        foreach ($resolveInfo->fieldNodes as $fieldNode) {
            if ($fieldNode->selectionSet === null) {
                continue;
            }

            if ($fieldNode->selectionSet) {
                foreach ($fieldNode->selectionSet->selections as $selectionNode) {
                    if ($selectionNode instanceof InlineFragmentNode) {
                        $fragmentName = $selectionNode->typeCondition->name->value ?? '';

                        if ($fragmentName && strstr($fragmentName, '_Submission')) {
                            $query->form(explode('_', $fragmentName)[0]);
                        }
                    }
                }
            }
        }

        $value = $query instanceof ElementQuery ? $query->all() : $query;

        return GqlHelper::applyDirectives($source, $resolveInfo, $value);
    }
}
