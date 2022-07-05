<?php
namespace verbb\formie\models;

use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\Html;

use craft\base\Model;

class HtmlTag extends Model
{
    // Properties
    // =========================================================================

    public string $tag = 'div';
    public array $attributes = [];


    // Public Methods
    // =========================================================================

    public function __construct($tag, $attributes = [])
    {
        parent::__construct();

        $this->tag = $tag;

        // Filter nested arrays like classes
        $this->attributes = ArrayHelper::filterEmptyValues($attributes);
    }

    public function setFromConfig(array $config)
    {
        $resetClass = $config['resetClass'] ?? false;
        $tagName = $config['tag'] ?? null;

        if ($tagName) {
            $this->tag = $tagName;
        }

        $attributes = $config['attributes'] ?? [];
        
        // Check if we're wanting to reset classes.
        if ($resetClass) { 
            $this->attributes['class'] = [];
        }

        $this->attributes = Html::mergeAttributes($this->attributes, $attributes);

        // Filter nested arrays like classes
        $this->attributes = ArrayHelper::filterEmptyValues($this->attributes);
    }

}
