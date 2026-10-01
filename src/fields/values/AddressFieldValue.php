<?php
namespace verbb\formie\fields\values;

use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\StringHelper;

use Craft;

use CommerceGuys\Addressing\Country\CountryRepository;

class AddressFieldValue extends BaseFieldValue
{
    // Static Methods
    // =========================================================================


    public static function getCountries(string $indexBy = 'code'): array
    {
        $locale = Craft::$app->getLocale()->getLanguageID();
        $repo = new CountryRepository($locale);

        return $indexBy === 'name' ? array_flip($repo->getList()) : $repo->getList();
    }

    public static function codeToName(string $code): ?string
    {
        return self::getCountries('code')[$code] ?? null;
    }

    public static function nameToCode(string $name): ?string
    {
        return self::getCountries('name')[$name] ?? null;
    }


    // Properties
    // =========================================================================

    protected ?string $autoComplete = null;
    protected ?string $address1 = null;
    protected ?string $address2 = null;
    protected ?string $address3 = null;
    protected ?string $city = null;
    protected ?string $state = null;
    protected ?string $zip = null;
    protected ?string $country = null;


    // Public Methods
    // =========================================================================

    public function __construct(array $config = [])
    {
        if (($config['country'] ?? null) instanceof OptionValue) {
            $config['country'] = $config['country']->value;
        }

        // Read legacy label-only addresses without retaining presentation policy.
        if (empty($config['country']) && !empty($config['countryOption'])) {
            $config['country'] = self::nameToCode($config['countryOption']) ?? $config['countryOption'];
        }
        unset($config['countryOption']);

        foreach (['autoComplete', 'address1', 'address2', 'address3', 'city', 'state', 'zip', 'country'] as $key) {
            $value = $config[$key] ?? null;
            $this->{$key} = $value === null ? null : (string)$value;
        }
    }

    public function __toString(): string
    {
        if ($this->autoComplete) {
            return (string)$this->autoComplete;
        }

        $address = ArrayHelper::filterEmptyStringsFromArray([
            StringHelper::trim($this->address1 ?? ''),
            StringHelper::trim($this->address2 ?? ''),
            StringHelper::trim($this->address3 ?? ''),
            StringHelper::trim($this->city ?? ''),
            StringHelper::trim($this->state ?? ''),
            StringHelper::trim($this->zip ?? ''),
            StringHelper::trim($this->country ?? ''),
        ]);

        return implode(', ', $address);
    }

    public function isEmpty(): bool
    {
        return $this->__toString() === '';
    }

    public function toArray(): array
    {
        return [
            'autoComplete' => $this->autoComplete,
            'address1' => $this->address1,
            'address2' => $this->address2,
            'address3' => $this->address3,
            'city' => $this->city,
            'state' => $this->state,
            'zip' => $this->zip,
            'country' => $this->country,
        ];
    }
}
