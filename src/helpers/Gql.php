<?php
namespace verbb\formie\helpers;

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