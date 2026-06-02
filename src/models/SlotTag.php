<?php
namespace verbb\formie\models;

use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\Html;

use craft\base\Model;

class SlotTag extends Model
{
    // Static Methods
    // =========================================================================

    public static function make(string $tag): self
    {
        $slotTag = new self();
        $slotTag->tag = $tag;

        return $slotTag;
    }
    

    // Properties
    // =========================================================================

    public string $tag = 'div';
    public array $attributes = [];
    public array $coreAttributes = [];
    public array $themeAttributes = [];
    public array $instanceAttributes = [];
    public array $overrideAttributes = [];
    public array $prependContent = [];
    public array $appendContent = [];


    // Public Methods
    // =========================================================================

    public function core(array $attributes): self
    {
        return $this->mergeCoreAttributes($attributes);
    }

    public function theme(array $attributes): self
    {
        return $this->mergeThemeAttributes($attributes);
    }

    public function instanceAttributes(array $attributes): self
    {
        return $this->mergeInstanceAttributes($attributes);
    }

    public function override(array $attributes): self
    {
        return $this->mergeOverrideAttributes($attributes);
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
        $this->_setLayeredConfig($config, $context, $resetClass);
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

    public function mergeCoreAttributes(array $attributes): self
    {
        $this->coreAttributes = Html::mergeAttributes($this->coreAttributes, ArrayHelper::filterEmptyFalse($attributes));
        $this->_syncAttributes();

        return $this;
    }

    public function mergeThemeAttributes(array $attributes): self
    {
        $this->themeAttributes = Html::mergeAttributes($this->themeAttributes, ArrayHelper::filterEmptyFalse($attributes));
        $this->_syncAttributes();

        return $this;
    }

    public function mergeInstanceAttributes(array $attributes): self
    {
        $this->instanceAttributes = Html::mergeAttributes($this->instanceAttributes, ArrayHelper::filterEmptyFalse($attributes));
        $this->_syncAttributes();

        return $this;
    }

    public function mergeOverrideAttributes(array $attributes): self
    {
        $this->overrideAttributes = Html::mergeAttributes($this->overrideAttributes, ArrayHelper::filterEmptyFalse($attributes));
        $this->_syncAttributes();

        return $this;
    }


    // Private Methods
    // =========================================================================

    private function _setLayeredConfig(array $config, array $context, bool $resetClass): void
    {
        $attributes = $this->_resolveConfigAttributes($config, $context);

        if ($resetClass) {
            $this->themeAttributes['class'] = [];
        }

        $this->overrideAttributes = Html::mergeAttributes($this->overrideAttributes, $attributes);
        $this->_syncAttributes();
    }

    private function _resolveConfigAttributes(array $config, array $context): array
    {
        $attributes = $config['attributes'] ?? [];
        $attributes = ArrayHelper::filterEmptyFalse($attributes);

        return $attributes;
    }

    private function _syncAttributes(): void
    {
        $attributes = Html::mergeAttributes($this->coreAttributes, $this->themeAttributes);
        $attributes = Html::mergeAttributes($attributes, $this->instanceAttributes);
        $attributes = Html::mergeAttributes($attributes, $this->overrideAttributes);

        $this->attributes = ArrayHelper::filterEmptyFalse($attributes);
    }
}
