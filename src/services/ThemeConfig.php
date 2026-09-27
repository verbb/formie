<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\base\FieldInterface;
use verbb\formie\elements\Form;
use verbb\formie\theme\context\RenderContext;
use verbb\formie\helpers\Html;
use verbb\formie\models\ResolvedTheme;
use verbb\formie\models\SlotTag;

use Craft;
use craft\helpers\Json;
use yii\base\Component;
use yii\base\InvalidArgumentException;

class ThemeConfig extends Component
{
    // Constants
    // =========================================================================

    public const MAX_CONFIG_BYTES = 65536;
    public const MAX_CONFIG_DEPTH = 12;
    public const MAX_CONFIG_NODES = 2000;

    private const CONDITION_PATHS = [
        'form.id', 'form.uid', 'form.handle', 'form.hasMultiplePages',
        'field.id', 'field.uid', 'field.handle', 'field.type', 'field.displayType', 'field.layout', 'field.hasErrors', 'field.isHidden', 'field.isRequired',
        'page.id', 'page.index', 'page.isActive', 'page.hasErrors', 'page.isComplete', 'page.buttonsPosition', 'page.saveButtonStyle',
        'currentPage.id', 'currentPage.index', 'row.isHidden',
        'submission.id', 'submission.uid', 'submission.hasErrors',
    ];

    // Public Methods
    // =========================================================================

    public function resolve(Form $form, array $renderOptions = [], bool $transported = false): ResolvedTheme
    {
        $mode = trim((string)($renderOptions['theme'] ?? '')) ?: 'formie';

        if (!in_array($mode, ['formie', 'none'], true)) {
            throw new InvalidArgumentException('Theme must be either `formie` or `none`.');
        }

        $providedConfig = $renderOptions['themeConfig'] ?? [];

        if ($providedConfig === null) {
            $providedConfig = [];
        }

        if (!is_array($providedConfig)) {
            throw new InvalidArgumentException('Theme config must be an object/map.');
        }

        // Global settings and ordinary Twig/PHP render options are developer-owned.
        // Only the request-transported layer receives the stricter executable-content
        // policy; validating after the merge would incorrectly demote trusted settings.
        $settingsConfig = $this->_validateAndNormalizeConfig(Formie::$plugin->getSettings()->themeConfig, false);
        $providedConfig = $this->_validateAndNormalizeConfig($providedConfig, $transported);
        $config = $this->_validateAndNormalizeConfig($this->mergeConfigLayers($settingsConfig, $providedConfig), false);
        $digest = hash('sha256', Json::encode($this->_canonicalize([
            'mode' => $mode,
            'config' => $config,
        ])));
        $resolved = new ResolvedTheme($mode, $config, [], $digest, true);

        return new ResolvedTheme(
            $mode,
            $config,
            $this->_buildBrowserClassMap($form, $resolved),
            $digest,
            true,
        );
    }

    public function restoreFragmentState(Form $form, array $state): ResolvedTheme
    {
        $mode = isset($state['mode']) && is_string($state['mode']) ? $state['mode'] : 'formie';
        $config = isset($state['config']) && is_array($state['config']) ? $state['config'] : [];
        $allowsRawHtml = (bool)($state['allowsRawHtml'] ?? false);

        if (!in_array($mode, ['formie', 'none'], true)) {
            throw new InvalidArgumentException('Invalid theme fragment mode.');
        }

        $config = $this->_validateAndNormalizeConfig($config, !$allowsRawHtml);
        $digest = hash('sha256', Json::encode($this->_canonicalize([
            'mode' => $mode,
            'config' => $config,
        ])));

        if (!hash_equals((string)($state['digest'] ?? ''), $digest)) {
            throw new InvalidArgumentException('Theme fragment state digest mismatch.');
        }

        $resolved = new ResolvedTheme($mode, $config, [], $digest, $allowsRawHtml);

        return new ResolvedTheme($mode, $config, $this->_buildBrowserClassMap($form, $resolved), $digest, $allowsRawHtml);
    }

    public function applyFormTagConfig(Form $form, string $key, ?SlotTag $tag, RenderContext $context): ?SlotTag
    {
        if (!$tag) {
            return null;
        }

        $theme = $this->_resolvedTheme($form);
        $config = $this->_normalizePublicSlotConfig($theme->getConfigItem($key));
        $unstyled = $theme->isNone() || (bool)($theme->config['resetClasses'] ?? false);

        return $this->_applyConfigToTag($tag, $config, $context, $unstyled, $theme->allowsRawHtml);
    }

    public function applyFieldTagConfig(FieldInterface $field, Form $form, string $key, ?SlotTag $tag, RenderContext $context): ?SlotTag
    {
        if (!$tag) {
            return null;
        }

        $theme = $this->_resolvedTheme($form);
        $templateConfig = $this->_normalizePublicSlotConfig($theme->getConfigItem($key));
        $fieldTypeConfig = $this->_normalizePublicSlotConfig($theme->getConfigItem($field->themeConfigKey() . '.' . $key));
        $config = $this->mergeSlotConfig($templateConfig, $fieldTypeConfig);
        $unstyled = $theme->isNone() || (bool)($theme->config['resetClasses'] ?? false);

        return $this->_applyConfigToTag($tag, $config, $context, $unstyled, $theme->allowsRawHtml);
    }

    public function buildBrowserClassMap(Form $form): array
    {
        return $this->_resolvedTheme($form)->browserClassMap;
    }

    public function buildFrontendClassMap(Form $form): array
    {
        Craft::$app->getDeprecator()->log(__METHOD__, 'Use `buildBrowserClassMap()` instead.');

        return $this->buildBrowserClassMap($form);
    }

    private function _buildBrowserClassMap(Form $form, ResolvedTheme $theme): array
    {
        $context = RenderContext::from([
            'form' => $form,
            'page' => $form->getPages()[0] ?? null,
            'currentPage' => $form->getCurrentPage(),
        ]);
        $evaluationContext = $this->_buildEvaluationContext($context);
        $themeClasses = [];

        foreach ($this->_browserClassDefaults() as $key => $fallbackClasses) {
            $config = $this->_normalizePublicSlotConfig($theme->getConfigItem($key));

            if ($theme->isNone()) {
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
        $baseClasses = $this->_normalizeClassExpressions($baseAttributes['class'] ?? []);
        $overrideClasses = $this->_normalizeClassExpressions($overrideAttributes['class'] ?? []);

        unset($baseAttributes['class'], $overrideAttributes['class']);

        $merged = [
            'tag' => $overrideConfig['tag'] ?? $baseConfig['tag'] ?? null,
            'resetClass' => $overrideConfig['resetClass'] ?? $overrideConfig['reset'] ?? $baseConfig['resetClass'] ?? $baseConfig['reset'] ?? false,
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

        if (($overrideConfig['resetClass'] ?? $overrideConfig['reset'] ?? false) === true) {
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

    private function _applyConfigToTag(SlotTag $tag, array|bool|null $config, RenderContext $context, bool $unstyled = false, bool $allowsRawHtml = false): ?SlotTag
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

        $normalizedConfig = $this->_normalizeSlotConfig($config, $context, $allowsRawHtml);

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

    private function _normalizeSlotConfig(array $config, RenderContext $context, bool $allowsRawHtml): array|bool|null
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
        $resolvedReset = (bool)$this->_resolveThemeValue($config['resetClass'] ?? $config['reset'] ?? false, $evaluationContext);
        $resolvedPrepend = $this->_resolveInjectedContent($config['prepend'] ?? [], $evaluationContext, $allowsRawHtml);
        $resolvedAppend = $this->_resolveInjectedContent($config['append'] ?? [], $evaluationContext, $allowsRawHtml);

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

            return $this->_normalizeClassList($classes);
        }

        return $this->_normalizeClassList($resolved);
    }

    private function _resolveInjectedContent(mixed $content, array $context, bool $allowsRawHtml): array
    {
        $resolved = $this->_resolveThemeValue($content, $context);

        if ($resolved === null || $resolved === false) {
            return [];
        }

        if ($this->_isInjectedContentNode($resolved)) {
            $renderedNode = $this->_renderInjectedContentNode($resolved, $context, $allowsRawHtml);

            return $renderedNode ? [$renderedNode] : [];
        }

        if (!is_array($resolved)) {
            return [];
        }

        $nodes = [];

        foreach ($resolved as $item) {
            foreach ($this->_resolveInjectedContent($item, $context, $allowsRawHtml) as $renderedNode) {
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

    private function _renderInjectedContentNode(array $node, array $context, bool $allowsRawHtml): ?string
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

        $content = ($allowsRawHtml ? $html : null) ?? ($text !== null ? Html::encode((string)$text) : '');

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

    private function _validateAndNormalizeConfig(array $config, bool $transported): array
    {
        $encoded = Json::encode($config);

        if (strlen($encoded) > self::MAX_CONFIG_BYTES) {
            throw new InvalidArgumentException('Theme config exceeds the 64 KiB transport limit.');
        }

        $nodes = 0;
        $normalized = $this->_validateConfigNode($config, '$', 0, $nodes, $transported);

        if (!is_array($normalized)) {
            throw new InvalidArgumentException('Theme config must resolve to an object/map.');
        }

        return $normalized;
    }

    private function _validateConfigNode(mixed $value, string $path, int $depth, int &$nodes, bool $transported): mixed
    {
        $nodes++;

        if ($depth > self::MAX_CONFIG_DEPTH) {
            throw new InvalidArgumentException("Theme config exceeds the maximum depth at `{$path}`.");
        }

        if ($nodes > self::MAX_CONFIG_NODES) {
            throw new InvalidArgumentException('Theme config exceeds the maximum node count.');
        }

        if (is_string($value)) {
            if (
                str_contains($value, '{{') ||
                str_contains($value, '{%') ||
                preg_match('/\b(?:attribute|constant|source|include)\s*\(/i', $value) ||
                preg_match('/(?:^|[^a-z0-9_-])(?:[a-z_][a-z0-9_]*\.)+[a-z_][a-z0-9_]*\s*\(/i', $value)
            ) {
                Craft::warning("Rejected executable theme expression at {$path}.", 'formie');
                throw new InvalidArgumentException("Executable Twig or method expressions are not supported at `{$path}`.");
            }

            return $value;
        }

        if (!is_array($value)) {
            return $value;
        }

        if ($this->_isConditionalValue($value)) {
            $unknownKeys = array_diff(array_keys($value), ['if', 'then', 'else']);

            if ($unknownKeys !== []) {
                $unknownKey = reset($unknownKeys);
                throw new InvalidArgumentException("Unknown conditional theme property `{$unknownKey}` at `{$path}`.");
            }

            $this->_validateConditionGrammar($value['if'] ?? true, $path . '.if');
        }

        $normalized = [];

        foreach ($value as $key => $item) {
            $key = is_int($key) ? $key : trim((string)$key);
            $itemPath = $path . '.' . $key;

            if (is_string($key) && $key === '') {
                throw new InvalidArgumentException("Theme config contains an empty property at `{$path}`.");
            }

            if (is_string($key) && preg_match('/^on[a-z]/i', $key)) {
                throw new InvalidArgumentException("Declarative event-handler attribute `{$key}` is not allowed at `{$path}`.");
            }

            if ($path !== '$' && str_ends_with($path, '.cssVars') && is_string($key) && !str_starts_with($key, '--')) {
                throw new InvalidArgumentException("CSS variable `{$key}` at `{$path}` must begin with `--`.");
            }

            if ($key === 'tag') {
                if (!is_string($item) || !preg_match('/^[a-z][a-z0-9-]*$/', $item)) {
                    throw new InvalidArgumentException("Invalid theme tag at `{$itemPath}`.");
                }

                if ($transported) {
                    throw new InvalidArgumentException("Theme tags are not accepted from transported theme config at `{$itemPath}`.");
                }
            }

            if (in_array($key, ['attributes', 'cssVars'], true) && !is_array($item)) {
                throw new InvalidArgumentException("Theme property `{$itemPath}` must be an object/map.");
            }

            if ($key === 'html' && $transported) {
                throw new InvalidArgumentException("Raw HTML is not accepted from transported theme config at `{$itemPath}`.");
            }

            if (in_array($key, ['context', 'key', 'path', 'equalsPath'], true) && is_string($item) && !in_array($item, self::CONDITION_PATHS, true)) {
                throw new InvalidArgumentException("Unknown theme condition path `{$item}` at `{$itemPath}`.");
            }

            if ($key === 'class') {
                $resolved = $this->_validateConfigNode($item, $itemPath, $depth + 1, $nodes, $transported);
                $normalized[$key] = is_string($resolved) ? $this->_normalizeClassList($resolved) : $resolved;
                continue;
            }

            $normalized[$key] = $this->_validateConfigNode($item, $itemPath, $depth + 1, $nodes, $transported);
        }

        return $normalized;
    }

    private function _validateConditionGrammar(mixed $condition, string $path): void
    {
        if (is_bool($condition) || is_string($condition)) {
            if (is_string($condition) && !in_array($condition, self::CONDITION_PATHS, true)) {
                throw new InvalidArgumentException("Unknown theme condition path `{$condition}` at `{$path}`.");
            }

            return;
        }

        if (!is_array($condition) || array_is_list($condition)) {
            throw new InvalidArgumentException("Theme condition at `{$path}` must be a boolean, context path or condition object.");
        }

        $allowedKeys = ['and', 'or', 'context', 'key', 'path', 'equalsPath', 'equals', 'notEquals', 'in', 'truthy'];
        $unknownKeys = array_diff(array_keys($condition), $allowedKeys);

        if ($unknownKeys !== []) {
            $unknownKey = reset($unknownKeys);
            throw new InvalidArgumentException("Unknown theme condition property `{$unknownKey}` at `{$path}`.");
        }

        foreach (['and', 'or'] as $operator) {
            if (!array_key_exists($operator, $condition)) {
                continue;
            }

            if (!is_array($condition[$operator]) || !array_is_list($condition[$operator])) {
                throw new InvalidArgumentException("Theme condition operator `{$operator}` at `{$path}` must be a list.");
            }

            foreach ($condition[$operator] as $index => $nestedCondition) {
                $this->_validateConditionGrammar($nestedCondition, "{$path}.{$operator}.{$index}");
            }
        }

        if (array_key_exists('in', $condition) && !is_array($condition['in'])) {
            throw new InvalidArgumentException("Theme condition `in` value at `{$path}` must be a list.");
        }
    }

    private function _canonicalize(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        if (!array_is_list($value)) {
            ksort($value, SORT_STRING);
        }

        foreach ($value as $key => $item) {
            $value[$key] = $this->_canonicalize($item);
        }

        return $value;
    }

    private function _normalizePublicSlotConfig(array|bool|null $config): array|bool|null
    {
        if (!is_array($config) || !$config || !$this->_isPublicFlatSlotConfig($config)) {
            return $config;
        }

        $normalized = array_intersect_key($config, array_flip([
            'tag',
            'cssVars',
            'reset',
            'resetClass',
            'prepend',
            'append',
        ]));
        $attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];

        if (($config['class'] ?? null) !== null) {
            $attributes['class'] = $config['class'];
        }

        if (($config['resetClass'] ?? $config['reset'] ?? false) === true) {
            $normalized['resetClass'] = true;
        }

        foreach ($config as $key => $value) {
            if (in_array($key, ['tag', 'class', 'attributes', 'cssVars', 'reset', 'resetClass', 'prepend', 'append'], true)) {
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
        return (bool)array_intersect(array_keys($value), ['tag', 'class', 'attributes', 'cssVars', 'reset', 'resetClass', 'prepend', 'append']);
    }

    private function _isPublicFlatSlotConfig(array $value): bool
    {
        if ($value === [] || array_is_list($value) || $this->_isConditionalValue($value) || $this->_isInjectedContentNode($value)) {
            return false;
        }

        if (array_key_exists('reset', $value) || array_key_exists('resetClass', $value) || array_key_exists('class', $value)) {
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
            return preg_split('/\s+/', trim($classes), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        }

        if (!is_array($classes)) {
            return [(string)$classes];
        }

        $normalized = [];

        foreach ($classes as $value) {
            if ($value === null || $value === false || $value === '') {
                continue;
            }

            if (is_string($value)) {
                array_push($normalized, ...(preg_split('/\s+/', trim($value), -1, PREG_SPLIT_NO_EMPTY) ?: []));
            } else {
                $normalized[] = (string)$value;
            }
        }

        return array_values(array_unique($normalized));
    }

    private function _normalizeClassExpressions(mixed $classes): array
    {
        if ($classes === null || $classes === false || $classes === '') {
            return [];
        }

        $classes = is_array($classes) ? $classes : [$classes];
        $normalized = [];

        foreach ($classes as $value) {
            if ($value === null || $value === false || $value === '') {
                continue;
            }

            if (is_array($value)) {
                $normalized[] = $value;
                continue;
            }

            array_push($normalized, ...$this->_normalizeClassList($value));
        }

        return $normalized;
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

    private function _resolvedTheme(Form $form): ResolvedTheme
    {
        $frame = Formie::$plugin->getRendering()->getActiveRenderFrame();

        if ($frame && $frame->getForm() === $form) {
            return $frame->getResolvedTheme();
        }

        return $this->resolve($form);
    }

    private function _browserClassDefaults(): array
    {
        static $defaults = null;

        if ($defaults !== null) {
            return $defaults;
        }

        $path = dirname(__DIR__) . '/config/browser-theme-state.json';
        $manifest = is_file($path) ? Json::decode((string)file_get_contents($path)) : [];
        $defaults = [];

        foreach (is_array($manifest) ? $manifest : [] as $key => $definition) {
            if (is_string($key) && is_array($definition)) {
                $defaults[$key] = $this->_normalizeClassList($definition['classes'] ?? []);
            }
        }

        return $defaults;
    }

}
