<?php
namespace verbb\formie\fields\values;

use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\StringHelper;

class NameFieldValue extends BaseFieldValue
{
    // Static Methods
    // =========================================================================

    public static function capabilityTypes(): array
    {
        return ['string', 'array'];
    }


    // Properties
    // =========================================================================

    public ?string $prefix = null;
    public ?string $prefixOption = null;
    public ?string $firstName = null;
    public ?string $middleName = null;
    public ?string $lastName = null;
    public ?string $name = null;
    public ?bool $isMultiple = null;


    // Public Methods
    // =========================================================================

    public function __construct(array $config = [])
    {
        if (isset($config['prefix']) && $config['prefix'] instanceof OptionValue) {
            $prefixValue = $config['prefix']->value ?? '';

            if ($prefixValue) {
                $prefixOptions = $config['prefix']->getOptions();

                if ($prefixOption = ArrayHelper::firstWhere($prefixOptions, 'value', $prefixValue)) {
                    $config['prefixOption'] = $prefixOption->label ?? '';
                }
            }
        }

        parent::__construct($config);
    }

    public function __toString()
    {
        if ($this->isMultiple) {
            return $this->getFullName();
        }

        return (string)$this->name;
    }

    public function isEmpty(): bool
    {
        return $this->__toString() === '';
    }

    public function toValueArray(): array
    {
        return [
            'prefix' => $this->prefix,
            'prefixOption' => $this->prefixOption,
            'firstName' => $this->firstName,
            'middleName' => $this->middleName,
            'lastName' => $this->lastName,
            'name' => $this->name,
            'isMultiple' => $this->isMultiple,
        ];
    }

    public function getName(): string
    {
        if (!$this->isMultiple) {
            return (string)$this->name;
        }

        $name = ArrayHelper::filterEmptyStringsFromArray([
            StringHelper::trim($this->firstName ?? ''),
            StringHelper::trim($this->lastName ?? ''),
        ]);

        return implode(' ', $name);
    }

    public function getFullName(): string
    {
        if (!$this->isMultiple) {
            return (string)$this->name;
        }

        $name = ArrayHelper::filterEmptyStringsFromArray([
            StringHelper::trim($this->prefixOption ?? ''),
            StringHelper::trim($this->firstName ?? ''),
            StringHelper::trim($this->middleName ?? ''),
            StringHelper::trim($this->lastName ?? ''),
        ]);

        return implode(' ', $name);
    }
}
