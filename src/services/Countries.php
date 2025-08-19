<?php
namespace verbb\formie\services;

use verbb\formie\base\FieldInterface;
use verbb\formie\events\ModifyAddressCountriesEvent;
use verbb\formie\events\ModifyPhoneCountriesEvent;

use Craft;
use craft\base\Component;

use libphonenumber\PhoneNumberUtil;
use CommerceGuys\Addressing\Country\CountryRepository;

class Countries extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_MODIFY_ADDRESS_COUNTRIES = 'modifyAddressCountries';
    public const EVENT_MODIFY_PHONE_COUNTRIES = 'modifyPhoneCountries';


    // Public Methods
    // =========================================================================

    public function getPhoneCountries(?FieldInterface $field = null): array
    {
        $locale = Craft::$app->getLocale()->getLanguageID();

        $countries = Craft::$app->getCache()->getOrSet(['formie.phoneCountries', 'locale' => $locale], function($cache) use ($locale) {
            $phoneUtil = PhoneNumberUtil::getInstance();
            $regions = $phoneUtil->getSupportedRegions();
            $countries = [];

            foreach ($regions as $countryCode) {
                $code = $phoneUtil->getCountryCodeForRegion($countryCode);
                $repo = new CountryRepository($locale);
                $country = $repo->get($countryCode);

                if ($country) {
                    $countries[] = [
                        'label' => $country->getName(),
                        'value' => $countryCode,
                        'code' => "+$code",
                    ];
                }
            }

            usort($countries, function($a, $b) {
                return strcmp($a['label'], $b['label']);
            });

            return $countries;
        });

        // Fire a 'modifyPhoneCountries' event
        $event = new ModifyPhoneCountriesEvent([
            'field' => $field,
            'countries' => $countries,
        ]);
        $this->trigger(self::EVENT_MODIFY_PHONE_COUNTRIES, $event);

        return $event->countries;
    }

    public function getAddressCountries(?FieldInterface $field = null): array
    {
        $locale = Craft::$app->getLocale()->getLanguageID();

        $countries = Craft::$app->getCache()->getOrSet(['formie.addressCountries', 'locale' => $locale], function($cache) use ($locale) {
            $phoneUtil = PhoneNumberUtil::getInstance();
            $regions = $phoneUtil->getSupportedRegions();
            $countries = [];

            foreach ($regions as $countryCode) {
                $repo = new CountryRepository($locale);
                $country = $repo->get($countryCode);

                if ($country) {
                    $countries[] = [
                        'label' => $country->getName(),
                        'value' => $countryCode,
                    ];
                }
            }

            usort($countries, function($a, $b) {
                return strcmp($a['label'], $b['label']);
            });

            return $countries;
        });

        // Fire a 'modifyAddressCountries' event
        $event = new ModifyAddressCountriesEvent([
            'field' => $field,
            'countries' => $countries,
        ]);
        $this->trigger(self::EVENT_MODIFY_ADDRESS_COUNTRIES, $event);

        return $event->countries;
    }
}
