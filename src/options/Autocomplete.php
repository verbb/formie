<?php
namespace verbb\formie\options;

use verbb\formie\base\PredefinedOption;
use verbb\formie\events\ModifyAutocompleteOptionsEvent;
use Craft;

use yii\base\Event;

class Autocomplete extends PredefinedOption
{
    // Constants
    // =========================================================================

    public const EVENT_MODIFY_AUTOCOMPLETE_OPTIONS = 'modifyAutocompleteOptions';

    // Protected Properties
    // =========================================================================

    public static ?string $defaultLabelOption = 'label';
    public static ?string $defaultValueOption = 'value';


    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Autocomplete');
    }

    public static function getLabelOptions(): array
    {
        return [
            ['label' => Craft::t('formie', 'Label'), 'value' => 'label'],
            ['label' => Craft::t('formie', 'Value'), 'value' => 'value'],
        ];
    }

    public static function getValueOptions(): array
    {
        return [
            ['label' => Craft::t('formie', 'Label'), 'value' => 'label'],
            ['label' => Craft::t('formie', 'Value'), 'value' => 'value'],
        ];
    }

    public static function getDataOptions(): array
    {
        $options = [
            // Controls
            [
                'label' => Craft::t('formie', 'Disabled'),
                'value' => 'off',
            ],
            [
                'label' => Craft::t('formie', 'Browser Default'),
                'value' => 'on',
            ],

            // Identity
            [
                'label' => Craft::t('formie', 'Full Name'),
                'value' => 'name',
            ],
            [
                'label' => Craft::t('formie', 'First Name'),
                'value' => 'given-name',
            ],
            [
                'label' => Craft::t('formie', 'Middle Name'),
                'value' => 'additional-name',
            ],
            [
                'label' => Craft::t('formie', 'Last Name'),
                'value' => 'family-name',
            ],
            [
                'label' => Craft::t('formie', 'Username'),
                'value' => 'username',
            ],
            [
                'label' => Craft::t('formie', 'Nickname'),
                'value' => 'nickname',
            ],

            // Email / Contact
            [
                'label' => Craft::t('formie', 'Email'),
                'value' => 'email',
            ],
            [
                'label' => Craft::t('formie', 'Phone Number'),
                'value' => 'tel',
            ],
            [
                'label' => Craft::t('formie', 'Country Code (Phone)'),
                'value' => 'tel-country-code',
            ],
            [
                'label' => Craft::t('formie', 'Area Code (Phone)'),
                'value' => 'tel-area-code',
            ],

            // Address
            [
                'label' => Craft::t('formie', 'Street Address'),
                'value' => 'street-address',
            ],
            [
                'label' => Craft::t('formie', 'Address Line 1'),
                'value' => 'address-line1',
            ],
            [
                'label' => Craft::t('formie', 'Address Line 2'),
                'value' => 'address-line2',
            ],
            [
                'label' => Craft::t('formie', 'City'),
                'value' => 'address-level2',
            ],
            [
                'label' => Craft::t('formie', 'State / Province'),
                'value' => 'address-level1',
            ],
            [
                'label' => Craft::t('formie', 'Postal Code'),
                'value' => 'postal-code',
            ],
            [
                'label' => Craft::t('formie', 'Country'),
                'value' => 'country',
            ],

            // Payment
            [
                'label' => Craft::t('formie', 'Cardholder Name'),
                'value' => 'cc-name',
            ],
            [
                'label' => Craft::t('formie', 'Card Number'),
                'value' => 'cc-number',
            ],
            [
                'label' => Craft::t('formie', 'Card Expiration Date'),
                'value' => 'cc-exp',
            ],
            [
                'label' => Craft::t('formie', 'Card Expiration Month'),
                'value' => 'cc-exp-month',
            ],
            [
                'label' => Craft::t('formie', 'Card Expiration Year'),
                'value' => 'cc-exp-year',
            ],
            [
                'label' => Craft::t('formie', 'Card Security Code'),
                'value' => 'cc-csc',
            ],

            // Account / Password
            [
                'label' => Craft::t('formie', 'Current Password'),
                'value' => 'current-password',
            ],
            [
                'label' => Craft::t('formie', 'New Password'),
                'value' => 'new-password',
            ],
            [
                'label' => Craft::t('formie', 'One-Time Code'),
                'value' => 'one-time-code',
            ],

            // Business / Misc
            [
                'label' => Craft::t('formie', 'Organization'),
                'value' => 'organization',
            ],
            [
                'label' => Craft::t('formie', 'Job Title'),
                'value' => 'organization-title',
            ],
            [
                'label' => Craft::t('formie', 'Website URL'),
                'value' => 'url',
            ],
            [
                'label' => Craft::t('formie', 'Language'),
                'value' => 'language',
            ],
        ];

        $event = new ModifyAutocompleteOptionsEvent([
            'options' => $options,
        ]);

        $this->trigger(self::EVENT_MODIFY_AUTOCOMPLETE_OPTIONS, $event);

        return $event->options;
    }
}
