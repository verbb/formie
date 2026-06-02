<?php
namespace verbb\formie\services;

use verbb\formie\base\FieldInterface;
use verbb\formie\elements\Form;
use verbb\formie\theme\context\RenderContext;
use verbb\formie\helpers\Html;
use verbb\formie\models\SlotTag;

use yii\base\Component;

class ThemeConfig extends Component
{
    // Constants
    // =========================================================================

    private const FRONTEND_CLASS_DEFAULTS = [
        'errors' => ['formie-errors'],
        'successes' => ['formie-successes'],
        'message' => ['formie-message'],
        'messageError' => ['formie-message-error'],
        'messageSuccess' => ['formie-message-success'],
        'tabError' => ['formie-tab-error'],
        'tabCurrent' => ['formie-tab-current'],
        'tabComplete' => ['formie-tab-complete'],
        'pageHidden' => ['formie-page-hidden'],
        'loading' => ['formie-loading'],
        'success' => ['formie-success'],
        'error' => ['formie-error'],
        'fieldLayoutError' => ['formie-field-has-error'],
        'fieldControlError' => ['formie-input-error'],
        'fieldErrors' => ['formie-field-errors'],
        'fieldError' => ['formie-field-error'],
    ];


    // Public Methods
    // =========================================================================

    public function applyFormTagConfig(Form $form, string $key, ?SlotTag $tag, RenderContext $context): ?SlotTag
    {
        if (!$tag) {
            return null;
        }

        $config = $this->_normalizePublicSlotConfig($form->getThemeConfigItem($key));
        $unstyled = $this->_isUnstyledTheme($form);

        return $this->_applyConfigToTag($tag, $config, $context, $unstyled);
    }

    public function applyFieldTagConfig(FieldInterface $field, Form $form, string $key, ?SlotTag $tag, RenderContext $context): ?SlotTag
    {
        if (!$tag) {
            return null;
        }

        $templateConfig = $this->_normalizePublicSlotConfig($form->getThemeConfigItem($key));
        $fieldTypeConfig = $this->_normalizePublicSlotConfig($form->getThemeConfigItem($field->themeConfigKey() . '.' . $key));
        $config = $this->mergeSlotConfig($templateConfig, $fieldTypeConfig);
        $unstyled = $this->_isUnstyledTheme($form);

        return $this->_applyConfigToTag($tag, $config, $context, $unstyled);
    }

    public function buildFrontendClassMap(Form $form): array
    {
        $context = RenderContext::from([
            'form' => $form,
            'page' => $form->getPages()[0] ?? null,
            'currentPage' => $form->getCurrentPage(),
        ]);
        $evaluationContext = $this->_buildEvaluationContext($context);
        $themeClasses = [];

        foreach (self::FRONTEND_CLASS_DEFAULTS as $key => $fallbackClasses) {
            $config = $this->_normalizePublicSlotConfig($form->getThemeConfigItem($key));

            if ($this->_isUnstyledTheme($form)) {
                $fallbackClasses = [];
            }

            $classes = $this->_resolveFrontendThemeClasses($config, $fallbackClasses, $evaluationContext);

            if ($classes !== []) {
                $themeClasses[$key] = $classes;
            }
        }

        return $themeClasses;
    }

    public function mergeConfigLayers(array $baseConfig, array $overrideConfig): array
    {
        $merged = $baseConfig;

        foreach ($overrideConfig as $key => $value) {
            $baseValue = $merged[$key] ?? null;

            if (is_array($value) && !array_key_exists($key, $merged)) {
                $merged[$key] = $value;
                continue;
            }

            if (is_array($value) && is_array($baseValue) && $this->_isSlotConfig($value)) {
                $merged[$key] = $this->mergeSlotConfig($baseValue, $value);
                continue;
            }

            if (is_array($value) && is_array($baseValue) && !$this->_isSlotConfig($value)) {
                $merged[$key] = $this->mergeConfigLayers($baseValue, $value);
                continue;
            }

            $merged[$key] = $value;
        }

        return $merged;
    }

    public function mergeSlotConfig(array|bool|null $baseConfig, array|bool|null $overrideConfig): array|bool|null
    {
        $baseConfig = $this->_normalizePublicSlotConfig($baseConfig);
        $overrideConfig = $this->_normalizePublicSlotConfig($overrideConfig);

        if ($overrideConfig === false || $overrideConfig === null) {
            return $overrideConfig;
        }

        if ($baseConfig === false || $baseConfig === null) {
            return $overrideConfig;
        }

        if (!is_array($baseConfig) || !is_array($overrideConfig)) {
            return $overrideConfig ?? $baseConfig;
        }

        $baseAttributes = $baseConfig['attributes'] ?? [];
        $overrideAttributes = $overrideConfig['attributes'] ?? [];
        $baseClasses = $this->_normalizeClassList($baseAttributes['class'] ?? []);
        $overrideClasses = $this->_normalizeClassList($overrideAttributes['class'] ?? []);

        unset($baseAttributes['class'], $overrideAttributes['class']);

        $merged = [
            'tag' => $overrideConfig['tag'] ?? $baseConfig['tag'] ?? null,
            'reset' => $overrideConfig['reset'] ?? $baseConfig['reset'] ?? false,
            'attributes' => $this->_mergeAttributeMaps($baseAttributes, $overrideAttributes),
            'cssVars' => $this->_mergeAttributeMaps($baseConfig['cssVars'] ?? [], $overrideConfig['cssVars'] ?? []),
            'prepend' => array_values(array_merge(
                $this->_normalizeInjectedContentList($baseConfig['prepend'] ?? []),
                $this->_normalizeInjectedContentList($overrideConfig['prepend'] ?? [])
            )),
            'append' => array_values(array_merge(
                $this->_normalizeInjectedContentList($baseConfig['append'] ?? []),
                $this->_normalizeInjectedContentList($overrideConfig['append'] ?? [])
            )),
        ];

        if (($overrideConfig['reset'] ?? false) === true) {
            $classes = $overrideClasses;
        } else {
            $classes = array_values(array_filter(array_merge($baseClasses, $overrideClasses), static function($value) {
                return $value !== null && $value !== false && $value !== '';
            }));
        }

        if ($classes !== []) {
            $merged['attributes']['class'] = $classes;
        }

        return array_filter($merged, static function($value, $key) {
            if (in_array($key, ['attributes', 'cssVars'], true)) {
                return $value !== [];
            }

            return $value !== null;
        }, ARRAY_FILTER_USE_BOTH);
    }


    // Private Methods
    // =========================================================================

    private function _applyConfigToTag(SlotTag $tag, array|bool|null $config, RenderContext $context, bool $unstyled = false): ?SlotTag
    {
        if ($config === false || $config === null) {
            return null;
        }

        if (!$config) {
            if ($unstyled) {
                $tag->setFromConfig(['resetClass' => true], $context->toArray());
            }

            return $tag;
        }

        $normalizedConfig = $this->_normalizeSlotConfig($config, $context);

        if ($normalizedConfig === false || $normalizedConfig === null) {
            return null;
        }

        if ($normalizedConfig) {
            if ($unstyled) {
                $normalizedConfig['resetClass'] = true;
            }

            $tag->setFromConfig($normalizedConfig, $context->toArray());
        }

        return $tag;
    }

    private function _normalizeSlotConfig(array $config, RenderContext $context): array|bool|null
    {
        $config = $this->_normalizePublicSlotConfig($config);

        if (!$config) {
            return $config;
        }

        $evaluationContext = $this->_buildEvaluationContext($context);
        $attributeConfig = $config['attributes'] ?? [];
        $resolvedClasses = $this->_resolveClasses($attributeConfig['class'] ?? null, $evaluationContext);
        unset($attributeConfig['class']);

        $attributes = $this->_resolveAttributeMap($attributeConfig, $evaluationContext);
        $resolvedTag = $this->_resolveThemeValue($config['tag'] ?? null, $evaluationContext);
        $resolvedCssVars = $this->_resolveAttributeMap($config['cssVars'] ?? [], $evaluationContext);
        $resolvedReset = (bool)$this->_resolveThemeValue($config['reset'] ?? false, $evaluationContext);
        $resolvedPrepend = $this->_resolveInjectedContent($config['prepend'] ?? [], $evaluationContext);
        $resolvedAppend = $this->_resolveInjectedContent($config['append'] ?? [], $evaluationContext);

        if ($resolvedClasses) {
            $attributes['class'] = $resolvedClasses;
        }

        if ($resolvedCssVars) {
            $attributes['style'] = array_merge($attributes['style'] ?? [], $resolvedCssVars);
        }

        $normalizedConfig = [
            'tag' => is_string($resolvedTag) ? $resolvedTag : null,
            'attributes' => $attributes,
            'prependContent' => $resolvedPrepend,
            'appendContent' => $resolvedAppend,
        ];

        if ($resolvedReset) {
            $normalizedConfig['resetClass'] = true;
        }

        return $normalizedConfig;
    }

    private function _buildEvaluationContext(RenderContext $context): array
    {
        $form = $context->form;
        $field = $context->field;
        $page = $context->targetPage;
        $currentPage = $context->currentPage;
        $submission = $context->submission;
        $row = $context->row;
        $errors = $context->errors;
        $pageSettings = $page?->getPageSettings();

        $pageId = $page->id ?? null;
        $currentPageId = $currentPage->id ?? null;
        $pageIndex = $page && $form ? $form->getPageIndex($page) : null;
        $currentPageIndex = $currentPage && $form ? $form->getPageIndex($currentPage) : null;

        return [
            'form' => [
                'id' => $form->id ?? null,
                'uid' => $form->uid ?? null,
                'handle' => $form->handle ?? null,
                'hasMultiplePages' => $form ? $form->hasMultiplePages() : false,
            ],
            'field' => [
                'id' => $field->id ?? null,
                'uid' => $field->uid ?? null,
                'handle' => $field->handle ?? null,
                'type' => $field ? $field->themeConfigKey() : null,
                'displayType' => $field?->getDisplayType(),
                'layout' => $field?->layout ?? null,
                'hasErrors' => !empty($errors),
                'isHidden' => $field?->getIsHidden() ?? false,
                'isRequired' => (bool)($field->required ?? false),
            ],
            'page' => [
                'id' => $pageId,
                'index' => $pageIndex,
                'isActive' => $pageId !== null && $currentPageId !== null && (string)$pageId === (string)$currentPageId,
                'hasErrors' => (bool)($page && $submission ? $page->getFieldErrors($submission) : false),
                'isComplete' => $pageIndex !== null && $currentPageIndex !== null && $currentPageIndex > $pageIndex,
                'buttonsPosition' => $pageSettings?->buttonsPosition ?? 'left',
                'saveButtonStyle' => $pageSettings?->saveButtonStyle ?? 'link',
            ],
            'currentPage' => [
                'id' => $currentPageId,
                'index' => $currentPageIndex,
            ],
            'row' => [
                'isHidden' => is_object($row) && method_exists($row, 'getIsHidden')
                    ? $row->getIsHidden()
                    : ((is_array($row) && array_key_exists('isHidden', $row)) ? (bool)$row['isHidden'] : false),
            ],
            'submission' => [
                'id' => $submission->id ?? null,
                'uid' => $submission->uid ?? null,
                'hasErrors' => $submission ? (bool)$submission->hasErrors() : false,
            ],
        ];
    }

    private function _resolveClasses(mixed $value, array $context): array|string|null
    {
        $resolved = $this->_resolveThemeValue($value, $context);

        if ($resolved === null || $resolved === false || $resolved === '') {
            return null;
        }

        if (is_array($resolved)) {
            $classes = [];

            foreach ($resolved as $item) {
                $item = $this->_resolveThemeValue($item, $context);

                if ($item === null || $item === false || $item === '') {
                    continue;
                }

                if (is_array($item)) {
                    foreach ($item as $nestedItem) {
                        if ($nestedItem !== null && $nestedItem !== false && $nestedItem !== '') {
                            $classes[] = $nestedItem;
                        }
                    }

                    continue;
                }

                $classes[] = $item;
            }

            return $classes;
        }

        return $resolved;
    }

    private function _resolveInjectedContent(mixed $content, array $context): array
    {
        $resolved = $this->_resolveThemeValue($content, $context);

        if ($resolved === null || $resolved === false) {
            return [];
        }

        if ($this->_isInjectedContentNode($resolved)) {
            $renderedNode = $this->_renderInjectedContentNode($resolved, $context);

            return $renderedNode ? [$renderedNode] : [];
        }

        if (!is_array($resolved)) {
            return [];
        }

        $nodes = [];

        foreach ($resolved as $item) {
            foreach ($this->_resolveInjectedContent($item, $context) as $renderedNode) {
                $nodes[] = $renderedNode;
            }
        }

        return $nodes;
    }

    private function _resolveAttributeMap(array $attributes, array $context): array
    {
        $resolved = [];

        foreach ($attributes as $key => $value) {
            if (is_array($value) && !$this->_isConditionalValue($value)) {
                $resolved[$key] = $this->_resolveAttributeMap($value, $context);
                continue;
            }

            $resolvedValue = $this->_resolveThemeValue($value, $context);

            if ($resolvedValue === null || $resolvedValue === false) {
                continue;
            }

            $resolved[$key] = $resolvedValue;
        }

        return $resolved;
    }

    private function _resolveThemeValue(mixed $value, array $context): mixed
    {
        if (is_array($value) && $this->_isConditionalValue($value)) {
            $condition = $value['if'] ?? true;
            $branch = $this->_evaluateCondition($condition, $context) ? ($value['then'] ?? null) : ($value['else'] ?? null);

            return $this->_resolveThemeValue($branch, $context);
        }

        return $value;
    }

    private function _renderInjectedContentNode(array $node, array $context): ?string
    {
        $tag = $this->_resolveThemeValue($node['tag'] ?? 'span', $context);

        if (!is_string($tag) || $tag === '') {
            return null;
        }

        $attributeConfig = $node['attributes'] ?? [];
        $classes = $this->_resolveClasses($attributeConfig['class'] ?? null, $context);
        unset($attributeConfig['class']);

        $attributes = $this->_resolveAttributeMap($attributeConfig, $context);
        $cssVars = $this->_resolveAttributeMap($node['cssVars'] ?? [], $context);
        $text = $this->_resolveThemeValue($node['text'] ?? null, $context);
        $html = $this->_resolveThemeValue($node['html'] ?? null, $context);

        if ($classes) {
            $attributes['class'] = $classes;
        }

        if ($cssVars) {
            $attributes['style'] = array_merge($attributes['style'] ?? [], $cssVars);
        }

        $content = $html ?? $text ?? '';

        return Html::tag($tag, (string)$content, $attributes);
    }

    private function _isConditionalValue(array $value): bool
    {
        return array_key_exists('if', $value) || array_key_exists('then', $value) || array_key_exists('else', $value);
    }

    private function _isInjectedContentNode(mixed $value): bool
    {
        if (!is_array($value) || $this->_isConditionalValue($value)) {
            return false;
        }

        return (bool)array_intersect(array_keys($value), ['tag', 'attributes', 'cssVars', 'text', 'html']);
    }

    private function _normalizeInjectedContentList(mixed $value): array
    {
        if ($value === null || $value === false) {
            return [];
        }

        if ($this->_isInjectedContentNode($value) || (is_array($value) && $this->_isConditionalValue($value))) {
            return [$value];
        }

        return is_array($value) ? array_values($value) : [];
    }

    private function _evaluateCondition(mixed $condition, array $context): bool
    {
        if (is_bool($condition)) {
            return $condition;
        }

        if (is_string($condition)) {
            return (bool)$this->_getContextPathValue($context, $condition);
        }

        if (!is_array($condition)) {
            return (bool)$condition;
        }

        if (isset($condition['and']) && is_array($condition['and'])) {
            foreach ($condition['and'] as $nestedCondition) {
                if (!$this->_evaluateCondition($nestedCondition, $context)) {
                    return false;
                }
            }

            return true;
        }

        if (isset($condition['or']) && is_array($condition['or'])) {
            foreach ($condition['or'] as $nestedCondition) {
                if ($this->_evaluateCondition($nestedCondition, $context)) {
                    return true;
                }
            }

            return false;
        }

        $left = $this->_getContextPathValue($context, $this->_getConditionContextKey($condition));

        if (array_key_exists('equalsPath', $condition)) {
            return $left == $this->_getContextPathValue($context, (string)$condition['equalsPath']);
        }

        if (array_key_exists('equals', $condition)) {
            return $left == $condition['equals'];
        }

        if (array_key_exists('notEquals', $condition)) {
            return $left != $condition['notEquals'];
        }

        if (array_key_exists('in', $condition) && is_array($condition['in'])) {
            return in_array($left, $condition['in'], true);
        }

        if (array_key_exists('truthy', $condition)) {
            return (bool)$left === (bool)$condition['truthy'];
        }

        return (bool)$left;
    }

    private function _getConditionContextKey(array $condition): string
    {
        $contextKey = $condition['context'] ?? $condition['key'] ?? $condition['path'] ?? '';

        return is_string($contextKey) ? $contextKey : '';
    }

    private function _getContextPathValue(array $context, string $path): mixed
    {
        if ($path === '') {
            return null;
        }

        $segments = explode('.', $path);
        $value = $context;

        foreach ($segments as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
                continue;
            }

            return null;
        }

        return $value;
    }

    private function _mergeAttributeMaps(array $base, array $override): array
    {
        $merged = $base;

        foreach ($override as $key => $value) {
            $baseValue = $merged[$key] ?? null;

            if (is_array($value) && is_array($baseValue)) {
                $merged[$key] = $this->_mergeAttributeMaps($baseValue, $value);
                continue;
            }

            $merged[$key] = $value;
        }

        return $merged;
    }

    private function _normalizePublicSlotConfig(array|bool|null $config): array|bool|null
    {
        if (!is_array($config) || !$config || $this->_isSlotConfig($config) || !$this->_isPublicFlatSlotConfig($config)) {
            return $config;
        }

        $normalized = [];
        $attributes = [];

        if (($config['class'] ?? null) !== null) {
            $attributes['class'] = $config['class'];
        }

        if (($config['reset'] ?? false) === true) {
            $normalized['reset'] = true;
        }

        foreach ($config as $key => $value) {
            if (in_array($key, ['class', 'reset'], true)) {
                continue;
            }

            $attributes[$key] = $value;
        }

        if ($attributes) {
            $normalized['attributes'] = $attributes;
        }

        return $normalized;
    }

    private function _isSlotConfig(array $value): bool
    {
        return (bool)array_intersect(array_keys($value), ['tag', 'attributes', 'cssVars', 'reset', 'prepend', 'append']);
    }

    private function _isPublicFlatSlotConfig(array $value): bool
    {
        if ($value === [] || array_is_list($value) || $this->_isConditionalValue($value) || $this->_isInjectedContentNode($value)) {
            return false;
        }

        if (array_key_exists('reset', $value) || array_key_exists('class', $value)) {
            return true;
        }

        foreach (array_keys($value) as $key) {
            if (is_string($key) && !$this->_isSlotConfig([$key => true])) {
                return true;
            }
        }

        return false;
    }

    private function _normalizeClassList(mixed $classes): array
    {
        if ($classes === null || $classes === false || $classes === '') {
            return [];
        }

        if (is_string($classes)) {
            return [$classes];
        }

        if (!is_array($classes)) {
            return [(string)$classes];
        }

        return array_values(array_filter($classes, static function($value) {
            return $value !== null && $value !== false && $value !== '';
        }));
    }

    private function _resolveFrontendThemeClasses(mixed $config, array $fallbackClasses, array $context): array
    {
        if ($config === false || $config === null) {
            return [];
        }

        if ($config === [] || $config === '') {
            return $fallbackClasses;
        }

        if (is_array($config) && $this->_isSlotConfig($config)) {
            $config = $config['attributes']['class'] ?? null;
        }

        $classes = $this->_normalizeClassList($this->_resolveClasses($config, $context));

        return $classes ?: $fallbackClasses;
    }

    private function _isUnstyledTheme(Form $form): bool
    {
        return $form->getFrontendTheme() === 'none';
    }

}
