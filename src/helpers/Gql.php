<?php
namespace verbb\formie\helpers;

use verbb\formie\Formie as FormiePlugin;
use verbb\formie\elements\Form;
use verbb\formie\models\RichText;

use craft\helpers\Db;
use craft\helpers\Gql as GqlHelper;

class Gql extends GqlHelper
{
    // Static Methods
    // =========================================================================

    public static function canQueryForms(): bool
    {
        $allowedEntities = self::extractAllowedEntitiesFromSchema();

        return isset($allowedEntities['formieForms']);
    }

    public static function canQuerySubmissions(): bool
    {
        $allowedEntities = self::extractAllowedEntitiesFromSchema();

        return isset($allowedEntities['formieSubmissions']);
    }

    public static function canReadForm(Form $form): bool
    {
        if (!self::canQueryForms()) {
            return false;
        }

        if (self::canSchema('formieForms.all')) {
            return true;
        }

        $allowedEntities = self::extractAllowedEntitiesFromSchema('read');
        $allowedFormUids = $allowedEntities['formieForms'] ?? [];

        if (!$allowedFormUids) {
            return false;
        }

        $allowedFormIds = array_map('intval', array_values(Db::idsByUids(Table::FORMIE_FORMS, $allowedFormUids)));

        return in_array((int)$form->id, $allowedFormIds, true);
    }

    public static function findReadableFormByHandle(string $handle, ?int $siteId = null): ?Form
    {
        $form = FormiePlugin::$plugin->getForms()->getFormByHandle($handle, $siteId);

        if (!$form || !self::canReadForm($form)) {
            return null;
        }

        return $form;
    }

    public static function canMutateSubmissionsForForm(Form $form): bool
    {
        $scope = 'formieSubmissions.' . $form->uid;

        return self::canSchema('formieSubmissions.all', 'create')
            || self::canSchema('formieSubmissions.all', 'save')
            || self::canSchema($scope, 'create')
            || self::canSchema($scope, 'save');
    }

    /**
     * Returns stored rich-text content as a TipTap-compatible document for GraphQL `Json` fields.
     */
    public static function resolveRichTextJson(RichText $richText): ?array
    {
        return $richText->isEmpty() ? null : $richText->toDoc();
    }

    /**
     * Removes GraphQL-php "Did you mean …" hints from an error message so valid field/argument names
     * are not disclosed when devMode is off (see webonyx KnownArgumentNames, ValuesOfCorrectType, etc.).
     *
     * Matching on the English phrase is intentional: webonyx/graphql-php validation messages are not
     * passed through Craft’s translation layer; they are hardcoded in the library (English only).
     */
    public static function stripGraphqlSuggestionHints(string $message): string
    {
        $original = $message;

        // ValuesOfCorrectType: `Expected type …; Did you mean …`
        $message = preg_replace('/;\s*Did you mean.*$/s', '.', $message) ?? $message;
        
        // KnownArgumentNames, KnownArgumentNamesOnDirectives, FieldsOnCorrectType (when debug rules run), etc.
        $message = preg_replace('/\s+Did you mean.*$/s', '', $message) ?? $message;
        $message = rtrim($message);

        // Avoid wiping the message if formatting was unexpected (multiline, i18n, future graphql-php changes).
        if ($message === '') {
            return rtrim($original);
        }

        return $message;
    }
}