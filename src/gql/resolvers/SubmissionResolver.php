<?php
namespace verbb\formie\gql\resolvers;

use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\elements\db\SubmissionQuery;
use verbb\formie\gql\arguments\SubmissionArguments;
use verbb\formie\helpers\Gql as GqlHelper;

use craft\db\Table;
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
            $query->andWhere(['in', 'formId', array_values(Db::idsByUids(Table::ELEMENTS, $pairs['formieSubmissions']))]);
        }

        return $query;
    }

    public static function resolve(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        $query = self::prepareElementQuery($source, $arguments, $context, $resolveInfo);

        // Fragment context is a convenience for unscoped queries; explicit and source scopes take precedence.
        if ($query instanceof SubmissionQuery && $query->formId === null && !array_key_exists('form', $arguments)) {
            $formHandles = [];

            foreach ($resolveInfo->fieldNodes as $fieldNode) {
                foreach ($fieldNode->selectionSet?->selections ?? [] as $selectionNode) {
                    if ($selectionNode instanceof InlineFragmentNode) {
                        $formHandle = self::formHandleFromFragmentType($selectionNode->typeCondition->name->value ?? '');

                        if ($formHandle) {
                            $formHandles[] = $formHandle;
                        }
                    }
                }
            }

            if ($formHandles) {
                $query->form(array_values(array_unique($formHandles)));
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
