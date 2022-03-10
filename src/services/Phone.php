<?php
namespace verbb\formie\services;

use verbb\formie\events\ModifyPhoneCountriesEvent;

use Craft;
use craft\base\Component;

use libphonenumber\PhoneNumberUtil;
use CommerceGuys\Addressing\Country\CountryRepository;

class Phone extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_MODIFY_PHONE_COUNTRIES = 'modifyPhoneCountries';


    // Public Methods
    // =========================================================================

    /**
     * Returns a list of countries and their extensions.
     *
     * @return mixed
     */
    public function getCountries(): mixed
    {
        $locale = Craft::$app->getLocale()->getLanguageID();

        $countries = Craft::$app->getCache()->getOrSet(['formie.countries', 'locale' => $locale], function($cache) use ($locale) {
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
            'countries' => $countries,
        ]);
        $this->trigger(self::EVENT_MODIFY_PHONE_COUNTRIES, $event);

        return $event->countries;
    }
}
