<?php
namespace verbb\formie\services;

use verbb\formie\base\FieldInterface;
use verbb\formie\fields\Phone;
use verbb\formie\events\ModifyAddressCountriesEvent;
use verbb\formie\events\ModifyAddressSubdivisionsEvent;
use verbb\formie\events\ModifyPhoneCountriesEvent;

use Craft;
use craft\base\Component;
use craft\web\Request;

use libphonenumber\PhoneNumberUtil;
use CommerceGuys\Addressing\AddressFormat\AddressFormatRepository;
use CommerceGuys\Addressing\Country\CountryRepository;
use CommerceGuys\Addressing\Subdivision\SubdivisionRepository;

class Countries extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_MODIFY_ADDRESS_COUNTRIES = 'modifyAddressCountries';
    public const EVENT_MODIFY_ADDRESS_SUBDIVISIONS = 'modifyAddressSubdivisions';
    public const EVENT_MODIFY_PHONE_COUNTRIES = 'modifyPhoneCountries';


    // Public Methods
    // =========================================================================

    public function getPhoneCountries(?FieldInterface $field = null): array
    {
        $locale = $this->_resolvePhoneCountryLocale($field);

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

    public function getCountryCodeForRequest(?Request $request = null): ?string
    {
        $request ??= Craft::$app->getRequest();

        foreach ($this->_getRequestCountryHeaders() as $header) {
            $code = strtoupper(trim((string)$request->getHeaders()->get($header)));

            if ($this->_isValidCountryCode($code)) {
                return $code;
            }
        }

        return null;
    }

    public function getCountryForRequest(?Request $request = null): ?array
    {
        $countryCode = $this->getCountryCodeForRequest($request);

        if (!$countryCode) {
            return null;
        }

        $locale = Craft::$app->getLocale()->getLanguageID();
        $repo = new CountryRepository($locale);
        $country = $repo->get($countryCode);

        if (!$country) {
            return null;
        }

        return [
            'countryCode' => $countryCode,
            'countryName' => $country->getName(),
        ];
    }

    public function resolveCountryCode(string $country): ?string
    {
        $country = trim($country);

        if ($country === '') {
            return null;
        }

        if (strlen($country) <= 3) {
            return strtoupper($country);
        }

        $locale = Craft::$app->getLocale()->getLanguageID();
        $repo = new CountryRepository($locale);

        foreach ($repo->getList() as $code => $name) {
            if (strcasecmp($name, $country) === 0) {
                return strtoupper($code);
            }
        }

        return null;
    }

    public function getAddressSubdivisions(string $countryCode, ?FieldInterface $field = null, string $optionLabel = 'name', string $optionValue = 'name'): array
    {
        $countryCode = strtoupper(trim($countryCode));

        if ($countryCode === '') {
            return [];
        }

        $locale = Craft::$app->getLocale()->getLanguageID();

        $subdivisions = Craft::$app->getCache()->getOrSet([
            'formie.addressSubdivisions',
            'locale' => $locale,
            'country' => $countryCode,
            'optionLabel' => $optionLabel,
            'optionValue' => $optionValue,
        ], function() use ($countryCode, $locale, $optionLabel, $optionValue) {
            $subdivisionRepository = new SubdivisionRepository();
            $options = [];

            foreach ($subdivisionRepository->getAll([$countryCode]) as $subdivision) {
                $name = $subdivision->getName();
                $short = $subdivision->getCode();

                $options[] = [
                    'label' => $optionLabel === 'short' ? $short : $name,
                    'value' => $optionValue === 'short' ? $short : $name,
                    'name' => $name,
                    'short' => $short,
                ];
            }

            return $options;
        });

        $event = new ModifyAddressSubdivisionsEvent([
            'field' => $field,
            'countryCode' => $countryCode,
            'subdivisions' => $subdivisions,
        ]);
        $this->trigger(self::EVENT_MODIFY_ADDRESS_SUBDIVISIONS, $event);

        return $event->subdivisions;
    }

    public function getAddressFormatMetadata(string $countryCode): array
    {
        $countryCode = strtoupper(trim($countryCode));

        if ($countryCode === '') {
            return [
                'countryCode' => '',
                'administrativeAreaType' => null,
                'administrativeAreaLabel' => Craft::t('formie', 'State / Province'),
                'administrativeAreaUsed' => false,
                'administrativeAreaRequired' => false,
            ];
        }

        $locale = Craft::$app->getLocale()->getLanguageID();
        $formatRepository = new AddressFormatRepository();
        $format = $formatRepository->get($countryCode);
        $usedFields = $format->getUsedFields();
        $requiredFields = $format->getRequiredFields();
        $administrativeAreaType = $format->getAdministrativeAreaType() ?: null;

        return [
            'countryCode' => $countryCode,
            'administrativeAreaType' => $administrativeAreaType,
            'administrativeAreaLabel' => self::getAdministrativeAreaLabel($administrativeAreaType),
            'administrativeAreaUsed' => in_array('administrativeArea', $usedFields, true),
            'administrativeAreaRequired' => in_array('administrativeArea', $requiredFields, true),
        ];
    }

    public static function getAdministrativeAreaLabel(?string $type): string
    {
        return match ($type) {
            'state' => Craft::t('formie', 'State'),
            'province' => Craft::t('formie', 'Province'),
            'prefecture' => Craft::t('formie', 'Prefecture'),
            'county' => Craft::t('formie', 'County'),
            'district' => Craft::t('formie', 'District'),
            'oblast' => Craft::t('formie', 'Oblast'),
            'parish' => Craft::t('formie', 'Parish'),
            'department' => Craft::t('formie', 'Department'),
            default => Craft::t('formie', 'State / Province'),
        };
    }


    // Private Methods
    // =========================================================================

    private function _resolvePhoneCountryLocale(?FieldInterface $field): string
    {
        if ($field instanceof Phone) {
            $languageId = $field->getCountryLocale();

            if ($languageId) {
                return $languageId;
            }
        }

        return Craft::$app->getLocale()->getLanguageID();
    }

    private function _getRequestCountryHeaders(): array
    {
        return [
            'CF-IPCountry',
            'CloudFront-Viewer-Country',
            'X-Country-Code',
            'X-Appengine-Country',
        ];
    }

    private function _isValidCountryCode(string $code): bool
    {
        if (strlen($code) !== 2 || !ctype_alpha($code)) {
            return false;
        }

        $repo = new CountryRepository('en');

        return (bool)$repo->get($code);
    }
}
