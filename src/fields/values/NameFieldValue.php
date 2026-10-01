<?php
namespace verbb\formie\fields\values;

use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\StringHelper;

class NameFieldValue extends BaseFieldValue
{
    // Properties
    // =========================================================================

    protected ?string $prefix = null;
    protected ?string $prefixOption = null;
    protected ?string $firstName = null;
    protected ?string $middleName = null;
    protected ?string $lastName = null;
    protected ?string $name = null;


    // Public Methods
    // =========================================================================

    public function __construct(array $config = [])
    {
        unset($config['isMultiple']);

        foreach (['prefix', 'prefixOption', 'firstName', 'middleName', 'lastName', 'name'] as $key) {
            $value = $config[$key] ?? null;

            if ($value instanceof SingleOptionFieldValue || $value instanceof OptionValue) {
                if ($key === 'prefix') {
                    $config['prefixOption'] = $value->getDisplayLabel();
                }
                $value = $value->value;
            }
            $config[$key] = $value === null ? null : trim(is_scalar($value) ? (string)$value : \craft\helpers\Json::encode($value));
        }
        $this->prefix = $config['prefix'];
        $this->prefixOption = $config['prefixOption'];
        $this->firstName = $config['firstName'];
        $this->middleName = $config['middleName'];
        $this->lastName = $config['lastName'];
        $this->name = $config['name'];
    }

    public function __toString(): string
    {
        return $this->getFullName();
    }

    public function isEmpty(): bool
    {
        return $this->__toString() === '';
    }

    public function toArray(): array
    {
        return [
            'prefix' => $this->prefix,
            'prefixOption' => $this->prefixOption,
            'firstName' => $this->firstName,
            'middleName' => $this->middleName,
            'lastName' => $this->lastName,
            'name' => $this->name,
        ];
    }

    public function getName(): string
    {
        if ($this->name !== null && $this->name !== '') {
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
        if ($this->name !== null && $this->name !== '') {
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
