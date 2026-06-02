<?php
namespace verbb\formie\deprecations;

use verbb\formie\elements\Form;

use Craft;

use Twig\Markup;

trait RenderingDeprecations
{
    // Public Methods
    // =========================================================================

    public function registerAssets(Form|string|null $form, array $renderOptions = []): void
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Rendering `registerAssets()` has been deprecated. Use `formAssets()` instead.');

        $this->formAssets($form, $renderOptions);
    }

    public function renderFormCss(Form|string|null $form, array $renderOptions = []): ?Markup
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Rendering `renderFormCss()` has been deprecated. Use `formAssets()` with `includeJs: false` instead.');

        return $this->formAssets($form, array_merge($renderOptions, [
            'includeJs' => false,
        ]));
    }

    public function renderFormJs(Form|string|null $form, array $renderOptions = []): ?Markup
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Rendering `renderFormJs()` has been deprecated. Use `formAssets()` with `includeCss: false` instead.');

        return $this->formAssets($form, array_merge($renderOptions, [
            'includeCss' => false,
        ]));
    }

    public function renderCss(bool $inline = false, array $renderOptions = []): ?Markup
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Rendering `renderCss()` has been deprecated. Use `frontendAssets()` with `includeJs: false` instead.');

        return $this->frontendAssets(array_merge($renderOptions, [
            'inline' => $inline,
            'includeJs' => false,
        ]));
    }

    public function renderJs(bool $inline = false, array $renderOptions = []): ?Markup
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Rendering `renderJs()` has been deprecated. Use `frontendAssets()` with `includeCss: false` instead.');

        return $this->frontendAssets(array_merge($renderOptions, [
            'inline' => $inline,
            'includeCss' => false,
        ]));
    }

    public function registerFormAssets(Form|string|null $form, array $renderOptions = []): void
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Rendering `registerFormAssets()` has been deprecated. Use `formAssets()` instead.');

        $this->formAssets($form, $renderOptions);
    }

    public function renderFormAssets(Form|string|null $form, array $renderOptions = []): ?Markup
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Rendering `renderFormAssets()` has been deprecated. Use `formAssets()` instead.');

        return $this->formAssets($form, $renderOptions);
    }

    public function renderRuntimeAssets(array $renderOptions = []): ?Markup
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Rendering `renderRuntimeAssets()` has been deprecated. Use `frontendAssets()` instead.');

        return $this->frontendAssets($renderOptions);
    }
}
