<?php
namespace verbb\formie\models;

use verbb\formie\helpers\ArrayHelper;

use Craft;
use craft\base\Model;

use yii\base\InvalidConfigException;

class IntegrationFormSettings extends Model
{
    // Properties
    // =========================================================================

    public array $collections = [];
    public string $classKey = 'class';


    // Public Methods
    // =========================================================================

    public function __construct($collections = [])
    {
        parent::__construct();

        $this->collections = $collections;
    }

    public function __clone()
    {
        parent::__clone();

        $this->collections = $this->_cloneSettings($this->collections);
    }

    public function getSettings(): array
    {
        return $this->collections;
    }

    public function getSettingsByKey(string $key)
    {
        return ArrayHelper::getValue($this->collections, $key) ?? [];
    }

    public function setSettings(array $collections): array
    {
        return array_merge($this->collections, $collections);
    }

    public function setSettingsByKey(string $key, mixed $value): void
    {
        ArrayHelper::setValue($this->collections, $key, $value);
    }

    public function serialize()
    {
        return $this->classToArray($this->collections);
    }

    public function unserialize($serialized): void
    {
        $this->collections = $this->classFromArray($serialized);
    }


    // Private Methods
    // =========================================================================

    private function _cloneSettings(mixed $data): mixed
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->_cloneSettings($value);
            }
        } elseif (is_object($data)) {
            $data = clone $data;

            // Hydrated collections can contain nested fields and provider-specific models.
            foreach (get_object_vars($data) as $key => $value) {
                if (is_array($value) || is_object($value)) {
                    $data->$key = $this->_cloneSettings($value);
                }
            }
        }

        return $data;
    }

    private function classToArray(mixed $data): mixed
    {
        if (is_object($data)) {
            $result = [$this->classKey => get_class($data)];

            foreach (get_object_vars($data) as $property => $value) {
                if ($property === $this->classKey) {
                    throw new InvalidConfigException("Object cannot contain $this->classKey property.");
                }

                $result[$property] = $this->classToArray($value);
            }
 
            return $result;
        }

        if (is_array($data)) {
            $result = [];

            foreach ($data as $key => $value) {
                if ($key === $this->classKey) {
                    throw new InvalidConfigException("Array cannot contain $this->classKey key.");
                }

                $result[$key] = $this->classToArray($value);
            }

            return $result;
        }

        return $data;
    }

    private function classFromArray(mixed $data): mixed
    {
        if (!is_array($data)) {
            return $data;
        }

        if (!isset($data[$this->classKey])) {
            $result = [];
            foreach ($data as $key => $value) {
                $result[$key] = $this->classFromArray($value);
            }

            return $result;
        }

        $config = ['class' => $data[$this->classKey]];
        unset($data[$this->classKey]);
        foreach ($data as $property => $value) {
            $config[$property] = $this->classFromArray($value);
        }

        return Craft::createObject($config);
    }

}
