<?php
namespace verbb\formie\helpers;

use Craft;

class HtmlAutocomplete
{
    // Static Methods
    // =========================================================================

    public static function getOptions(): array
    {
        $options = [
            [
                'label' => Craft::t('formie', 'Default (browser)'),
                'value' => '',
            ],
            [
                'label' => Craft::t('formie', 'Off'),
                'value' => 'off',
            ],
            [
                'label' => Craft::t('formie', 'On'),
                'value' => 'on',
            ],
        ];

        foreach (self::getTokenGroups() as $groupLabel => $tokens) {
            $translatedGroup = self::translateGroupLabel($groupLabel);

            foreach ($tokens as $token => $tokenLabel) {
                $options[] = [
                    'label' => Craft::t('formie', '{group} — {token}', [
                        'group' => $translatedGroup,
                        'token' => $tokenLabel,
                    ]),
                    'value' => $token,
                ];
            }
        }

        return $options;
    }

    public static function getValidTokenValues(): array
    {
        $tokens = ['', 'off', 'on'];

        foreach (self::getTokenGroups() as $groupTokens) {
            foreach ($groupTokens as $token => $tokenLabel) {
                $tokens[] = $token;
            }
        }

        return array_values(array_unique($tokens));
    }

    public static function isValid(?string $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        if (in_array($value, self::getValidTokenValues(), true)) {
            return true;
        }

        // Allow sectioned tokens such as "billing email" per the HTML spec.
        return (bool)preg_match('/^[\w\- ]+$/', $value);
    }


    // Private Methods
    // =========================================================================

    private static function translateGroupLabel(string $groupLabel): string
    {
        return match ($groupLabel) {
            'Name' => Craft::t('formie', 'Name'),
            'Email & Username' => Craft::t('formie', 'Email & Username'),
            'Password' => Craft::t('formie', 'Password'),
            'Organization' => Craft::t('formie', 'Organization'),
            'Address' => Craft::t('formie', 'Address'),
            'Payment' => Craft::t('formie', 'Payment'),
            'Transaction' => Craft::t('formie', 'Transaction'),
            'Personal' => Craft::t('formie', 'Personal'),
            'Telephone' => Craft::t('formie', 'Telephone'),
            'Other' => Craft::t('formie', 'Other'),
            default => $groupLabel,
        };
    }

    private static function getTokenGroups(): array
    {
        return [
            'Name' => [
                'name' => 'name',
                'honorific-prefix' => 'honorific-prefix',
                'given-name' => 'given-name',
                'additional-name' => 'additional-name',
                'family-name' => 'family-name',
                'honorific-suffix' => 'honorific-suffix',
                'nickname' => 'nickname',
            ],
            'Email & Username' => [
                'email' => 'email',
                'username' => 'username',
            ],
            'Password' => [
                'new-password' => 'new-password',
                'current-password' => 'current-password',
                'one-time-code' => 'one-time-code',
            ],
            'Organization' => [
                'organization-title' => 'organization-title',
                'organization' => 'organization',
            ],
            'Address' => [
                'street-address' => 'street-address',
                'address-line1' => 'address-line1',
                'address-line2' => 'address-line2',
                'address-line3' => 'address-line3',
                'address-level4' => 'address-level4',
                'address-level3' => 'address-level3',
                'address-level2' => 'address-level2',
                'address-level1' => 'address-level1',
                'country' => 'country',
                'country-name' => 'country-name',
                'postal-code' => 'postal-code',
            ],
            'Payment' => [
                'cc-name' => 'cc-name',
                'cc-given-name' => 'cc-given-name',
                'cc-additional-name' => 'cc-additional-name',
                'cc-family-name' => 'cc-family-name',
                'cc-number' => 'cc-number',
                'cc-exp' => 'cc-exp',
                'cc-exp-month' => 'cc-exp-month',
                'cc-exp-year' => 'cc-exp-year',
                'cc-csc' => 'cc-csc',
                'cc-type' => 'cc-type',
            ],
            'Transaction' => [
                'transaction-currency' => 'transaction-currency',
                'transaction-amount' => 'transaction-amount',
            ],
            'Personal' => [
                'language' => 'language',
                'bday' => 'bday',
                'bday-day' => 'bday-day',
                'bday-month' => 'bday-month',
                'bday-year' => 'bday-year',
                'sex' => 'sex',
            ],
            'Telephone' => [
                'tel' => 'tel',
                'tel-country-code' => 'tel-country-code',
                'tel-national' => 'tel-national',
                'tel-area-code' => 'tel-area-code',
                'tel-local' => 'tel-local',
                'tel-local-prefix' => 'tel-local-prefix',
                'tel-local-suffix' => 'tel-local-suffix',
                'tel-extension' => 'tel-extension',
            ],
            'Other' => [
                'impp' => 'impp',
                'url' => 'url',
                'photo' => 'photo',
                'webauthn' => 'webauthn',
            ],
        ];
    }
}
