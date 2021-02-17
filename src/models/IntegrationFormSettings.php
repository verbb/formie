<?php
namespace verbb\formie\models;

use Craft;
use craft\base\Model;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;

use yii\base\InvalidConfigException;

class IntegrationFormSettings extends Model
{
    // Properties
    // =========================================================================

    public $collections = [];
    public $classKey = 'class';


    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function __construct($collections = [])
    {
        $this->collections = $collections;
    }

    /**
     * @inheritdoc
     */
    public function getSettings()
    {
        return $this->collections;
    }

    /**
     * @inheritdoc
     */
    public function getSettingsByKey($key)
    {
        return ArrayHelper::getValue($this->collections, $key) ?? [];
    }

    /**
     * @inheritdoc
     */
    public function setSettings($collections)
    {
        return array_merge($this->collections, $collections);
    }

    /**
     * @inheritdoc
     */
    public function serialize()
    {
        return $this->classToArray($this->collections);
    }

    /**
     * @inheritdoc
     */
    public function unserialize($serialized)
    {
        $this->collections = $this->classFromArray($serialized);
    }

    /**
     * @param mixed $data
     * @return array|mixed
     * @throws InvalidConfigException
     */
    private function classToArray($data)
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
     */
    private function classFromArray($data)
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
