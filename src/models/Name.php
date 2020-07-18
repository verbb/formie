<?php
namespace verbb\formie\models;

use craft\base\Model;
use craft\helpers\ArrayHelper;
use craft\helpers\StringHelper;

class Name extends Model
{
    // Properties
    // =========================================================================

    /**
     * @var string
     */
    public $prefix;

    /**
     * @var string
     */
    public $firstName;

    /**
     * @var string
     */
    public $middleName;

    /**
     * @var string
     */
    public $lastName;

    /**
     * @var string
     */
    public $name;

    /**
     * @var bool
     */
    public $isMultiple;


    // Public Methods
    // =========================================================================

    /**
     * @return string
     */
    public function __toString()
    {
        if ($this->isMultiple) {
            return $this->getName();
        } else {
            return (string)$this->name;
        }
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
            StringHelper::trim($this->prefix ?? ''),
            StringHelper::trim($this->firstName ?? ''),
            StringHelper::trim($this->middleName ?? ''),
            StringHelper::trim($this->lastName ?? ''),
        ]);

        return implode(' ', $name);
    }
}
