<?php
namespace verbb\formie\gql\resolvers;

use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\gql\arguments\SubmissionArguments;
use verbb\formie\helpers\Gql as GqlHelper;
use verbb\formie\helpers\Table;

use craft\elements\db\ElementQuery;
use craft\elements\ElementCollection;
use craft\gql\base\ElementResolver;
use craft\helpers\Db;

use GraphQL\Error\Error;
use GraphQL\Language\AST\InlineFragmentNode;
use GraphQL\Type\Definition\ResolveInfo;

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

        self::validateDynamicFieldArguments($arguments);

        foreach ($arguments as $key => $value) {
            $query->$key($value);
        }

        $pairs = GqlHelper::extractAllowedEntitiesFromSchema('read');

        if (!GqlHelper::canQuerySubmissions()) {
            return ElementCollection::empty();
        }

        if (!GqlHelper::canSchema('formieSubmissions.all')) {
            $query->andWhere(['in', 'formId', array_values(Db::idsByUids(Table::FORMIE_FORMS, $pairs['formieSubmissions']))]);
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
                        $formHandle = self::formHandleFromFragmentType($fragmentName);

                        if ($formHandle) {
                            $query->form($formHandle);
                        }
                    }
                }
            }
        }

        $value = $query instanceof ElementQuery ? $query->all() : $query;

        return GqlHelper::applyDirectives($source, $resolveInfo, $value);
    }

    public static function formHandleFromFragmentType(string $fragmentName): ?string
    {
        $suffix = '_Submission';

        if (!str_ends_with($fragmentName, $suffix)) {
            return null;
        }

        return substr($fragmentName, 0, -strlen($suffix)) ?: null;
    }

    private static function validateDynamicFieldArguments(array $arguments): void
    {
        $dynamicFieldHandles = SubmissionArguments::getDynamicFieldArgumentHandlesFor($arguments);

        if (!$dynamicFieldHandles) {
            return;
        }

        $formHandle = SubmissionArguments::getSingleTargetFormHandle($arguments['form'] ?? null);

        if (!$formHandle) {
            throw new Error('Field handle filters on submission queries require the `form` argument to target exactly one form.');
        }

        $form = Formie::$plugin->getForms()->getFormByHandle($formHandle);

        if (!$form) {
            return;
        }

        $fieldHandles = array_map(static fn($field) => $field->handle, $form->getFields());
        $invalidFieldHandles = array_values(array_diff($dynamicFieldHandles, $fieldHandles));

        if ($invalidFieldHandles) {
            throw new Error('Field handle filters on submission queries must belong to the targeted form.');
        }
    }
}
