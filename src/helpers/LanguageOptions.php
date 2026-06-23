<?php
namespace verbb\formie\helpers;

use Craft;

class LanguageOptions
{
    // Static Methods
    // =========================================================================

    public static function buildOptions(array $languages): array
    {
        $options = [];

        foreach ($languages as $label => $value) {
            $options[] = [
                'label' => self::translateLabel($label),
                'value' => $value,
            ];
        }

        return $options;
    }

    public static function translateLabel(string $label): string
    {
        return match ($label) {
            'Auto' => Craft::t('formie', 'Auto'),
            'Afrikaans' => Craft::t('formie', 'Afrikaans'),
            'Amharic' => Craft::t('formie', 'Amharic'),
            'Arabic' => Craft::t('formie', 'Arabic'),
            'Armenian' => Craft::t('formie', 'Armenian'),
            'Azerbaijani' => Craft::t('formie', 'Azerbaijani'),
            'Basque' => Craft::t('formie', 'Basque'),
            'Bengali' => Craft::t('formie', 'Bengali'),
            'Bosnian' => Craft::t('formie', 'Bosnian'),
            'Bulgarian' => Craft::t('formie', 'Bulgarian'),
            'Catalan' => Craft::t('formie', 'Catalan'),
            'Chinese' => Craft::t('formie', 'Chinese'),
            'Chinese (Hong Kong)' => Craft::t('formie', 'Chinese (Hong Kong)'),
            'Chinese (Simplified)' => Craft::t('formie', 'Chinese (Simplified)'),
            'Chinese (Traditional)' => Craft::t('formie', 'Chinese (Traditional)'),
            'Croatian' => Craft::t('formie', 'Croatian'),
            'Czech' => Craft::t('formie', 'Czech'),
            'Danish' => Craft::t('formie', 'Danish'),
            'Dutch' => Craft::t('formie', 'Dutch'),
            'English' => Craft::t('formie', 'English'),
            'English (UK)' => Craft::t('formie', 'English (UK)'),
            'English (US)' => Craft::t('formie', 'English (US)'),
            'Estonian' => Craft::t('formie', 'Estonian'),
            'Filipino' => Craft::t('formie', 'Filipino'),
            'Finnish' => Craft::t('formie', 'Finnish'),
            'French' => Craft::t('formie', 'French'),
            'French (Canadian)' => Craft::t('formie', 'French (Canadian)'),
            'Galician' => Craft::t('formie', 'Galician'),
            'Georgian' => Craft::t('formie', 'Georgian'),
            'German' => Craft::t('formie', 'German'),
            'German (Austria)' => Craft::t('formie', 'German (Austria)'),
            'German (Switzerland)' => Craft::t('formie', 'German (Switzerland)'),
            'Greek' => Craft::t('formie', 'Greek'),
            'Gujarati' => Craft::t('formie', 'Gujarati'),
            'Hebrew' => Craft::t('formie', 'Hebrew'),
            'Hindi' => Craft::t('formie', 'Hindi'),
            'Hungarian' => Craft::t('formie', 'Hungarian'),
            'Icelandic' => Craft::t('formie', 'Icelandic'),
            'Indonesian' => Craft::t('formie', 'Indonesian'),
            'Italian' => Craft::t('formie', 'Italian'),
            'Japanese' => Craft::t('formie', 'Japanese'),
            'Kannada' => Craft::t('formie', 'Kannada'),
            'Korean' => Craft::t('formie', 'Korean'),
            'Laothian' => Craft::t('formie', 'Laothian'),
            'Latvian' => Craft::t('formie', 'Latvian'),
            'Lithuanian' => Craft::t('formie', 'Lithuanian'),
            'Malay' => Craft::t('formie', 'Malay'),
            'Malayalam' => Craft::t('formie', 'Malayalam'),
            'Marathi' => Craft::t('formie', 'Marathi'),
            'Mongolian' => Craft::t('formie', 'Mongolian'),
            'Norwegian' => Craft::t('formie', 'Norwegian'),
            'Persian' => Craft::t('formie', 'Persian'),
            'Polish' => Craft::t('formie', 'Polish'),
            'Portuguese' => Craft::t('formie', 'Portuguese'),
            'Portuguese (Brazil)' => Craft::t('formie', 'Portuguese (Brazil)'),
            'Portuguese (Portugal)' => Craft::t('formie', 'Portuguese (Portugal)'),
            'Romanian' => Craft::t('formie', 'Romanian'),
            'Russian' => Craft::t('formie', 'Russian'),
            'Serbian' => Craft::t('formie', 'Serbian'),
            'Sinhalese' => Craft::t('formie', 'Sinhalese'),
            'Slovak' => Craft::t('formie', 'Slovak'),
            'Slovenian' => Craft::t('formie', 'Slovenian'),
            'Spanish' => Craft::t('formie', 'Spanish'),
            'Spanish (Latin America)' => Craft::t('formie', 'Spanish (Latin America)'),
            'Swahili' => Craft::t('formie', 'Swahili'),
            'Swedish' => Craft::t('formie', 'Swedish'),
            'Tamil' => Craft::t('formie', 'Tamil'),
            'Telugu' => Craft::t('formie', 'Telugu'),
            'Thai' => Craft::t('formie', 'Thai'),
            'Turkish' => Craft::t('formie', 'Turkish'),
            'Ukrainian' => Craft::t('formie', 'Ukrainian'),
            'Urdu' => Craft::t('formie', 'Urdu'),
            'Vietnamese' => Craft::t('formie', 'Vietnamese'),
            'Zulu' => Craft::t('formie', 'Zulu'),
            default => $label,
        };
    }
}
