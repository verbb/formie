<?php
namespace verbb\formie\models;

use Craft;
use craft\base\Model;
use craft\helpers\ArrayHelper;

use yii\base\InvalidConfigException;

class IntegrationFormSettings extends Model
{
    // Properties
    // =========================================================================

    public array $collections = [];
    public string $classKey = 'class';


    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function __construct($collections = [])
    {
        parent::__construct();

        $this->collections = $collections;
    }

    public function getSettings(): array
    {
        return $this->collections;
    }

    public function getSettingsByKey($key)
    {
        return ArrayHelper::getValue($this->collections, $key) ?? [];
    }

    public function setSettings($collections): array
    {
        return array_merge($this->collections, $collections);
    }

    public function setSettingsByKey($key, $value): void
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

    /**
     * @param mixed $data
     * @return array|mixed
     * @throws InvalidConfigException
     */
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

    /**
     * @param array $data
     * @return mixed
     * @throws InvalidConfigException
     */
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
