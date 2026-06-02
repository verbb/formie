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
    public array $extraAttributes = [];
    public string|array|null $extraClasses = null;
    public array $prependContent = [];
    public array $appendContent = [];


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
        $prependContent = $config['prependContent'] ?? [];
        $appendContent = $config['appendContent'] ?? [];

        if ($tagName) {
            $this->tag = $tagName;
        }

        $this->prependContent = $prependContent;
        $this->appendContent = $appendContent;

        $attributes = $config['attributes'] ?? [];

        // Check if we're wanting to reset classes.
        if ($resetClass) {
            $this->attributes['class'] = [];
        }

        $this->attributes = Html::mergeAttributes($this->attributes, $attributes);

        // Filter nested arrays like classes
        $this->attributes = ArrayHelper::filterEmptyFalse($this->attributes);

        // Any custom attributes (set in field settings, typically) should be retained and not reset
        if ($this->extraAttributes) {
            $this->attributes = Html::mergeAttributes($this->attributes, $this->extraAttributes);
        }

        // Any custom classes set at the field settings should be retained and not reset
        if ($this->extraClasses) {
            $this->attributes['class'][] = $this->extraClasses;
        }
    }

    public function composeContent(?string $content = null): string
    {
        $segments = [];

        foreach ($this->prependContent as $prepend) {
            if ($prepend !== null && $prepend !== false && $prepend !== '') {
                $segments[] = $prepend;
            }
        }

        if ($content !== null && $content !== '') {
            $segments[] = $content;
        }

        foreach ($this->appendContent as $append) {
            if ($append !== null && $append !== false && $append !== '') {
                $segments[] = $append;
            }
        }

        return implode('', $segments);
    }
}
