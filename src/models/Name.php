<?php
namespace verbb\formie\models;

use verbb\formie\base\FieldValueInterface;
use verbb\formie\fields\data\OptionData;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\StringHelper;

use craft\base\Model;

class Name extends Model implements FieldValueInterface
{
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
        // Prefix should use the label, not value given it's a dropdown
        if (isset($config['prefix']) && $config['prefix'] instanceof OptionData) {
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

    public function getName(): string
    {
        if (!$this->isMultiple && $this->name) {
            return $this->name;
        }

        $name = ArrayHelper::filterEmptyStringsFromArray([
            StringHelper::trim($this->firstName ?? ''),
            StringHelper::trim($this->lastName ?? ''),
        ]);

        return implode(' ', $name);
    }

    public function getFullName(): string
    {
        if (!$this->isMultiple && $this->name) {
            return $this->name;
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
