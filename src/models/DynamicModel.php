<?php
namespace verbb\formie\models;

use craft\base\Model;

use yii\base\InvalidConfigException;
use yii\validators\Validator;

class DynamicModel extends Model
{
    // Properties
    // =========================================================================

    private array $_attributes = [];
    private array $_attributeLabels = [];


    // Public Methods
    // =========================================================================

    public function __construct(array $attributes = [], $config = [])
    {
        foreach ($attributes as $name => $value) {
            if (is_int($name)) {
                $this->_attributes[$value] = null;
            } else {
                $this->_attributes[$name] = $value;
            }
        }

        parent::__construct($config);
    }

    public function __get($name)
    {
        if ($this->hasAttribute($name)) {
            return $this->_attributes[$name];
        }

        return parent::__get($name);
    }

    public function __set($name, $value)
    {
        if ($this->hasAttribute($name)) {
            $this->_attributes[$name] = $value;
        } else {
            parent::__set($name, $value);
        }
    }

    public function __isset($name)
    {
        if ($this->hasAttribute($name)) {
            return isset($this->_attributes[$name]);
        }

        return parent::__isset($name);
    }

    public function __unset($name)
    {
        if ($this->hasAttribute($name)) {
            unset($this->_attributes[$name]);
        } else {
            parent::__unset($name);
        }
    }

    public function canGetProperty($name, $checkVars = true, $checkBehaviors = true): bool
    {
        return parent::canGetProperty($name, $checkVars, $checkBehaviors) || $this->hasAttribute($name);
    }

    public function canSetProperty($name, $checkVars = true, $checkBehaviors = true): bool
    {
        return parent::canSetProperty($name, $checkVars, $checkBehaviors) || $this->hasAttribute($name);
    }

    public function hasAttribute($name): bool
    {
        return array_key_exists($name, $this->_attributes);
    }

    public function defineAttribute($name, $value = null): void
    {
        $this->_attributes[$name] = $value;
    }

    public function undefineAttribute($name): void
    {
        unset($this->_attributes[$name]);
    }

    public function addRule($attributes, $validator, $options = []): static
    {
        $validators = $this->getValidators();

        if ($validator instanceof Validator) {
            $validator->attributes = (array)$attributes;
        } else {
            $validator = Validator::createValidator($validator, $this, (array)$attributes, $options);
        }

        $validators->append($validator);

        return $this;
    }

    public static function validateData(array $data, $rules = []): static
    {
        $model = new static($data);

        if (!empty($rules)) {
            $validators = $model->getValidators();

            foreach ($rules as $rule) {
                if ($rule instanceof Validator) {
                    $validators->append($rule);
                } else if (is_array($rule) && isset($rule[0], $rule[1])) {
                    $validator = Validator::createValidator($rule[1], $model, (array)$rule[0], array_slice($rule, 2));
                    $validators->append($validator);
                } else {
                    throw new InvalidConfigException('Invalid validation rule: a rule must specify both attribute names and validator type.');
                }
            }
        }

        $model->validate();

        return $model;
    }

    public function attributes(): array
    {
        return array_keys($this->_attributes);
    }

    public function setAttributeLabels(array $labels = []): static
    {
        $this->_attributeLabels = $labels;

        return $this;
    }

    public function setAttributeLabel($attribute, $label): static
    {
        $this->_attributeLabels[$attribute] = $label;

        return $this;
    }

    public function attributeLabels(): array
    {
        return $this->_attributeLabels;
    }

}
