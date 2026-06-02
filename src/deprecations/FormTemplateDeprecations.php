<?php
namespace verbb\formie\deprecations;

use verbb\formie\elements\Form;

use Craft;

use Twig\Markup;

trait FormTemplateDeprecations
{
    // Public Methods
    // =========================================================================

    public function getOutputCssLayout(): bool
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Form template `outputCssLayout` has been deprecated. Use `outputCss` instead.');

        return $this->outputCss;
    }

    public function setOutputCssLayout(bool $value): void
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Form template `outputCssLayout` has been deprecated. Use `outputCss` instead.');

        $this->outputCss = $value;
    }

    public function getOutputCssTheme(): bool
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Form template `outputCssTheme` has been deprecated. Use `outputCss` instead.');

        return $this->outputCss;
    }

    public function setOutputCssTheme(bool $value): void
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Form template `outputCssTheme` has been deprecated. Use `outputCss` instead.');

        $this->outputCss = $value;
    }

    public function getOutputJsBase(): bool
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Form template `outputJsBase` has been deprecated. Use `outputJs` instead.');

        return $this->outputJs;
    }

    public function setOutputJsBase(bool $value): void
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Form template `outputJsBase` has been deprecated. Use `outputJs` instead.');

        $this->outputJs = $value;
    }

    public function getOutputJsTheme(): bool
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Form template `outputJsTheme` has been deprecated. Use `outputJs` instead.');

        return $this->outputJs;
    }

    public function setOutputJsTheme(bool $value): void
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Form template `outputJsTheme` has been deprecated. Use `outputJs` instead.');

        $this->outputJs = $value;
    }
}
