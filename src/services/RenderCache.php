<?php
namespace verbb\formie\services;

use Craft;
use craft\base\Component;

class RenderCache extends Component
{
    // Properties
    // =========================================================================

    public $globalvariables = [];
    public $fieldVariables = [];


    // Public Methods
    // =========================================================================

    public function getGlobalVariables($key)
    {
        return $this->globalvariables[$key] ?? [];
    }

    public function setGlobalVariables($key, $values)
    {
        $this->globalvariables[$key] = array_merge($this->getGlobalVariables($key), $values);
    }

    public function getFieldVariables($key)
    {
        return $this->fieldVariables[$key] ?? [];
    }

    public function setFieldVariables($key, $values)
    {
        $this->fieldVariables[$key] = array_merge($this->getFieldVariables($key), $values);
    }

    public function getVariables($key)
    {
        return array_merge($this->getGlobalVariables($key), $this->getFieldVariables($key));
    }

}
