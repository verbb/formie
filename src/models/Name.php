<?php
namespace verbb\formie\models;

use verbb\formie\fields\formfields\Name as NameField;

use Craft;
use craft\base\Model;
use craft\helpers\ArrayHelper;
use craft\helpers\StringHelper;

class Name extends Model
{
    // Properties
    // =========================================================================

    public ?string $prefix = null;
    public ?string $firstName = null;
    public ?string $middleName = null;
    public ?string $lastName = null;
    public ?string $name = null;
    public ?bool $isMultiple = null;


    // Public Methods
    // =========================================================================

    /**
     * @return string
     */
    public function __toString()
    {
        if ($this->isMultiple) {
            return $this->getName();
        }

        return (string)$this->name;
    }

    /**
     * Returns a concatenated string of name parts.
     *
     * @return string
     */
    public function getName(): string
    {
        if (!$this->isMultiple) {
            return $this->name;
        }

        $name = ArrayHelper::filterEmptyStringsFromArray([
            StringHelper::trim($this->firstName ?? ''),
            StringHelper::trim($this->lastName ?? ''),
        ]);

        return implode(' ', $name);
    }

    /**
     * Returns a concatenated string of name parts.
     *
     * @return string
     */
    public function getFullName(): string
    {
        if (!$this->isMultiple) {
            return $this->name;
        }

        // Prefix should use the label, not value given it's a dropdown
        $prefixOptions = NameField::getPrefixOptions();
        $prefixOption = ArrayHelper::firstWhere($prefixOptions, 'value', $this->prefix);
        $prefix = $prefixOption['label'] ?? '';

        $name = ArrayHelper::filterEmptyStringsFromArray([
            StringHelper::trim($prefix ?? ''),
            StringHelper::trim($this->firstName ?? ''),
            StringHelper::trim($this->middleName ?? ''),
            StringHelper::trim($this->lastName ?? ''),
        ]);

        return implode(' ', $name);
    }
}
