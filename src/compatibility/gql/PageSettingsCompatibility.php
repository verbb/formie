<?php
namespace verbb\formie\compatibility\gql;

use verbb\formie\Formie;
use verbb\formie\gql\types\Json as JsonType;
use verbb\formie\models\FieldLayoutPageSettings;

use GraphQL\Type\Definition\Type;

class PageSettingsCompatibility
{
    // Public Methods
    // =========================================================================

    public static function applyLegacyFieldAliases(array $fields): array
    {
        if (!Formie::$plugin->getCompatibility()->isCompatibilityModeEnabled()) {
            return $fields;
        }

        $fields['enableJsEvents'] = [
            'name' => 'enableJsEvents',
            'type' => Type::boolean(),
            'description' => 'Deprecated alias for whether client event payload emission is enabled for this page’s submit.',
            'deprecationReason' => 'Use `enableClientEvents` instead.',
            'resolve' => fn(FieldLayoutPageSettings $source): bool => $source->enableClientEvents,
        ];

        $fields['jsGtmEventOptions'] = [
            'name' => 'jsGtmEventOptions',
            'type' => JsonType::getType(),
            'description' => 'Deprecated alias for the page client event payload fields.',
            'deprecationReason' => 'Use `clientEventFields` instead.',
            'resolve' => fn(FieldLayoutPageSettings $source): array => $source->clientEventFields,
        ];

        return $fields;
    }
}
