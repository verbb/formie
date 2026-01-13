<?php
namespace verbb\formie\models;

use verbb\formie\base\FieldValueInterface;

class DateTime extends \DateTime implements FieldValueInterface
{
    // Public Methods
    // =========================================================================

    public function __get(string $name): mixed
    {
        if ($name === 'year') {
            return (int)$this->format('Y');
        }

        if ($name === 'month') {
            return (int)$this->format('m');
        }

        if ($name === 'day') {
            return (int)$this->format('d');
        }

        if ($name === 'hour') {
            return (int)$this->format('H');
        }

        if ($name === 'minute') {
            return (int)$this->format('i');
        }

        if ($name === 'second') {
            return (int)$this->format('s');
        }

        if ($name === 'ampm') {
            return $this->format('A');
        }

        return null;
    }
}
