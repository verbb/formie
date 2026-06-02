<?php
namespace verbb\formie\deprecations;

use verbb\formie\Formie as FormiePlugin;
use verbb\formie\elements\Form;

use Craft;

use Twig\Markup;

trait FormieVariableDeprecations
{
    // Public Methods
    // =========================================================================

    public function registerAssets(Form|string|null $form, array $renderOptions = []): void
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, '`craft.formie.registerAssets()` has been deprecated. Use `craft.formie.formAssets()` instead.');

        FormiePlugin::$plugin->getRendering()->formAssets($form, $renderOptions);
    }

    public function renderFormCss(Form|string|null $form, array $renderOptions = []): ?Markup
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, '`craft.formie.renderFormCss()` has been deprecated. Use `craft.formie.formAssets()` with `includeJs: false` instead.');

        return FormiePlugin::$plugin->getRendering()->formAssets($form, array_merge($renderOptions, [
            'includeJs' => false,
        ]));
    }

    public function renderFormJs(Form|string|null $form, array $renderOptions = []): ?Markup
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, '`craft.formie.renderFormJs()` has been deprecated. Use `craft.formie.formAssets()` with `includeCss: false` instead.');

        return FormiePlugin::$plugin->getRendering()->formAssets($form, array_merge($renderOptions, [
            'includeCss' => false,
        ]));
    }

    public function renderCss(bool $inline = false, array $renderOptions = []): ?Markup
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, '`craft.formie.renderCss()` has been deprecated. Use `craft.formie.frontendAssets()` with `includeJs: false` instead.');

        return FormiePlugin::$plugin->getRendering()->frontendAssets(array_merge($renderOptions, [
            'inline' => $inline,
            'includeJs' => false,
        ]));
    }

    public function renderJs(bool $inline = false, array $renderOptions = []): ?Markup
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, '`craft.formie.renderJs()` has been deprecated. Use `craft.formie.frontendAssets()` with `includeCss: false` instead.');

        return FormiePlugin::$plugin->getRendering()->frontendAssets(array_merge($renderOptions, [
            'inline' => $inline,
            'includeCss' => false,
        ]));
    }

    public function registerFormAssets(Form|string|null $form, array $renderOptions = []): void
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, '`craft.formie.registerFormAssets()` has been deprecated. Use `craft.formie.formAssets()` instead.');

        FormiePlugin::$plugin->getRendering()->formAssets($form, $renderOptions);
    }

    public function renderFormAssets(Form|string|null $form, array $renderOptions = []): ?Markup
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, '`craft.formie.renderFormAssets()` has been deprecated. Use `craft.formie.formAssets()` instead.');

        return FormiePlugin::$plugin->getRendering()->formAssets($form, $renderOptions);
    }

    public function renderRuntimeAssets(array $renderOptions = []): ?Markup
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, '`craft.formie.renderRuntimeAssets()` has been deprecated. Use `craft.formie.frontendAssets()` instead.');

        return FormiePlugin::$plugin->getRendering()->frontendAssets($renderOptions);
    }
}
