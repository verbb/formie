<?php
namespace verbb\formie\models;

use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\Html;

use Craft;
use craft\base\Model;

class HtmlTag extends Model
{
    // Properties
    // =========================================================================

    public string $tag = 'div';
    public array $attributes = [];
    public string|array|null $extraClasses = null;


    // Public Methods
    // =========================================================================

    public function __construct($tag, $attributes = [], $extraClasses = null)
    {
        parent::__construct();

        $this->tag = $tag;
        $this->extraClasses = $extraClasses;

        // Filter nested arrays like classes
        $this->attributes = ArrayHelper::filterEmptyValues($attributes);
    }

    public function setFromConfig(array $config, array $context = [])
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

        // Provide support for Twig-in-config syntax for really complex stuff. Just for classes.
        $classes = $this->attributes['class'] ?? [];

        if ($classes) {
            foreach ($classes as $key => $class) {
                if (strstr($class, '{{')) {
                    $parsed = Craft::$app->getView()->renderString($class, $context);

                    $this->attributes['class'][$key] = $parsed;
                }
            }
        }

        // Any custom classes set at the field settings should be retained and not reset
        if ($this->extraClasses) {
            $this->attributes['class'][] = $this->extraClasses;
        }
    }

}
