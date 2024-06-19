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
        $prefixOptions = NameField::getPrefixOptions();

        if (isset($config['prefix']) && $config['prefix']) {
            $prefixOption = ArrayHelper::firstWhere($prefixOptions, 'value', $config['prefix']);
            $config['prefixOption'] = $prefixOption['label'] ?? '';
        }

        parent::__construct($config);
    }

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

        $name = ArrayHelper::filterEmptyStringsFromArray([
            StringHelper::trim($this->prefixOption ?? ''),
            StringHelper::trim($this->firstName ?? ''),
            StringHelper::trim($this->middleName ?? ''),
            StringHelper::trim($this->lastName ?? ''),
        ]);

        return implode(' ', $name);
    }
}
