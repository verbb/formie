<?php
namespace verbb\formie\helpers;

class SchemaReadiness
{
    // Static Methods
    // =========================================================================

    public static function canHydrateRuntimeSettings(): bool
    {
        // Craft initializes plugins before running their install or pending upgrade migrations.
        return DbSchema::columnExists(Table::FORMIE_CAPTCHA_PROVIDERS, 'scope')
            && DbSchema::columnExists(Table::FORMIE_SPAM_SETTINGS, 'enableAllowedEmailDomains');
    }
}
