<?php
namespace verbb\formie\behaviors;

use \verbb\formie\services\Fields;

use yii\base\Behavior;

class CustomFieldBehavior extends Behavior
{
    public bool $hasMethods = false;

    public bool $canSetProperties = true;

    public static $fieldHandles = [];

    private array $_customFieldValues = [];

    public function init(): void
    {
        $fieldHandles = array_values(array_unique(array_values(Fields::getFieldHandles())));
        static::$fieldHandles = array_combine($fieldHandles, array_fill(0, count($fieldHandles), true));

        parent::init();
    }

    public function __call($name, $params)
    {
        if ($this->hasMethods && isset(self::$fieldHandles[$name]) && count($params) === 1) {
            $this->$name = $params[0];
            return $this->owner;
        }

        return parent::__call($name, $params);
    }

    public function hasMethod($name): bool
    {
        if ($this->hasMethods && isset(self::$fieldHandles[$name])) {
            return true;
        }

        return parent::hasMethod($name);
    }

    public function __isset($name): bool
    {
        if (isset(self::$fieldHandles[$name])) {
            return true;
        }

        return parent::__isset($name);
    }

    public function __get($name)
    {
        if (isset(self::$fieldHandles[$name])) {
            return $this->_customFieldValues[$name] ?? null;
        }

        return parent::__get($name);
    }

    public function __set($name, $value)
    {
        if (isset(self::$fieldHandles[$name])) {
            $this->_customFieldValues[$name] = $value;
            return;
        }

        parent::__set($name, $value);
    }

    public function canGetProperty($name, $checkVars = true): bool
    {
        if ($checkVars && isset(self::$fieldHandles[$name])) {
            return true;
        }
        return parent::canGetProperty($name, $checkVars);
    }

    public function canSetProperty($name, $checkVars = true): bool
    {
        if (!$this->canSetProperties) {
            return false;
        }
        if ($checkVars && isset(self::$fieldHandles[$name])) {
            return true;
        }
        return parent::canSetProperty($name, $checkVars);
    }
}
