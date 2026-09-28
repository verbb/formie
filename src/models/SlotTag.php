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
    public array $prependContent = [];
    public array $appendContent = [];
    private array $_trustedAttributeOverrides = [];
    private array $_trustedAttributeRemovals = [];


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

    public function attributesForRender(array $instanceAttributes = []): array
    {
        $resetClass = (bool)ArrayHelper::remove($instanceAttributes, 'reset', false);
        $themeAttributes = $this->themeAttributes;

        if ($resetClass) {
            unset($themeAttributes['class']);
        }

        return $this->_composeAttributes($themeAttributes, $instanceAttributes);
    }

    public function captureTrustedEventResult(array $beforeAttributes): void
    {
        $this->_trustedAttributeOverrides = [];
        $this->_trustedAttributeRemovals = [];

        foreach ($this->attributes as $name => $value) {
            if (!array_key_exists($name, $beforeAttributes) || $beforeAttributes[$name] !== $value) {
                $this->_trustedAttributeOverrides[$name] = $value;
            }
        }

        foreach (array_keys($beforeAttributes) as $name) {
            if (!array_key_exists($name, $this->attributes)) {
                $this->_trustedAttributeRemovals[] = $name;
            }
        }

        $this->attributes = $this->attributesForRender();
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

    // Private Methods
    // =========================================================================

    private function _setLayeredConfig(array $config, array $context, bool $resetClass): void
    {
        $attributes = $this->_resolveConfigAttributes($config, $context);

        if ($resetClass) {
            $this->themeAttributes['class'] = [];
        }

        $this->themeAttributes = Html::mergeAttributes($this->themeAttributes, $attributes);
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
        $this->attributes = $this->_composeAttributes($this->themeAttributes);
    }

    private function _composeAttributes(array $themeAttributes, array $renderAttributes = []): array
    {
        $attributes = Html::mergeAttributes($themeAttributes, $this->instanceAttributes);
        $attributes = Html::mergeAttributes($attributes, ArrayHelper::filterEmptyFalse($renderAttributes));
        $attributes = Html::mergeAttributes($attributes, $this->coreAttributes);

        // Event changes are deliberate expert overrides. Assign changed top-level
        // values exactly so nested core attributes can also be replaced or removed.
        foreach ($this->_trustedAttributeOverrides as $name => $value) {
            $attributes[$name] = $value;
        }

        foreach ($this->_trustedAttributeRemovals as $name) {
            unset($attributes[$name]);
        }

        return ArrayHelper::filterEmptyFalse($attributes);
    }
}
