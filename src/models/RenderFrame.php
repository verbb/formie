<?php
namespace verbb\formie\models;

use verbb\formie\elements\Form;
use verbb\formie\fields\Html;

// One stack frame for the active form render context.
class RenderFrame
{
    // Static Methods
    // =========================================================================

    public static function isReservedRenderOptionKey(string $key): bool
    {
        static $keys = null;

        $keys ??= [
            'sessionKey',
            'themeConfig',
            'theme',
            'outputCss',
            'outputJs',
            'outputCssLocation',
            'outputJsLocation',
            'includeCss',
            'includeJs',
            'outputCssLayout',
            'outputCssTheme',
            'outputJsBase',
            'outputJsTheme',
            'renderCss',
            'renderJs',
            'customInputs',
            'csrfInput',
            'fieldNamespace',
            'value',
            'fieldLabelPrefix',
            'fieldLabelSuffix',
            'includeScriptsInline',
            'mode',
            'endpoint',
            'cssAttributes',
            'scriptAttributes',
            'jsAttributes',
            'initJs',
            'useObserver',
            'useStockTemplates',
            'previewMode',
            'templateVars',
            'errors',
            'submission',
            'field',
            'inputName',
        ];

        return in_array($key, $keys, true);
    }



    // Public Methods
    // =========================================================================

    public function __construct(
        private readonly Form $form,
        private readonly array $renderOptions,
    ) {
    }

    public function getForm(): Form
    {
        return $this->form;
    }

    public function getRenderOptions(): array
    {
        return $this->renderOptions;
    }

    public function includeScriptsInline(): bool
    {
        return (bool)($this->renderOptions['includeScriptsInline'] ?? false);
    }

    public function getTemplateVars(): array
    {
        $merged = (array)($this->renderOptions['templateVars'] ?? []);

        foreach ($this->renderOptions as $key => $value) {
            if (!self::isReservedRenderOptionKey((string)$key)) {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }
}
