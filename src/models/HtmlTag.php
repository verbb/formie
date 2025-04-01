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
    public array $extraAttributes = [];
    public string|array|null $extraClasses = null;


    // Public Methods
    // =========================================================================

    public function __construct($tag, $attributes = [], $extraAttributes = [], $extraClasses = null)
    {
        parent::__construct();

        $this->tag = $tag;
        $this->extraAttributes = $extraAttributes;
        $this->extraClasses = $extraClasses;

        // Filter nested arrays like classes
        $this->attributes = ArrayHelper::filterEmptyFalse($attributes);
    }

    public function setFromConfig(array $config, array $context = []): void
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
        $this->attributes = ArrayHelper::filterEmptyFalse($this->attributes);

        // Provide support for Twig-in-config syntax for really complex stuff. Just for classes and style.
        foreach (['class', 'style'] as $attribute) {
            $items = $this->attributes[$attribute] ?? [];

            if ($items) {
                foreach ($items as $key => $item) {
                    if (str_contains($item, '{{')) {
                        $parsed = Craft::$app->getView()->renderString($item, $context);
                        $this->attributes[$attribute][$key] = $parsed;
                    }
                }
            }
        }

        // Any custom attributes (set in field settings, typically) should be retained and not reset
        if ($this->extraAttributes) {
            $this->attributes = Html::mergeAttributes($this->attributes, $this->extraAttributes);
        }

        // Any custom classes set at the field settings should be retained and not reset
        if ($this->extraClasses) {
            $this->attributes['class'][] = $this->extraClasses;
        }
    }

}
