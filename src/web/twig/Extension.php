<?php
namespace verbb\formie\web\twig;

use verbb\formie\helpers\Html;
use verbb\formie\helpers\RichTextHelper;
use verbb\formie\web\twig\tokenparsers\FieldTagTokenParser;
use verbb\formie\web\twig\tokenparsers\FormTagTokenParser;

use Craft;
use craft\helpers\ArrayHelper;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\TwigFilter;
use Twig\Environment;

use yii\helpers\Inflector;

class Extension extends AbstractExtension
{
    // Public Methods
    // =========================================================================

    public function getName(): string
    {
        return 'Formie Variables';
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('getRichTextConfig', [new RichTextHelper(), 'getRichTextConfig']),
            new TwigFunction('formieInclude', [$this, 'formieInclude'], ['needs_environment' => true, 'needs_context' => true, 'is_safe' => ['all']]),
            new TwigFunction('formieSiteInclude', [$this, 'formieSiteInclude'], ['needs_environment' => true, 'needs_context' => true, 'is_safe' => ['all']]),
            new TwigFunction('formiePluginInclude', [$this, 'formiePluginInclude'], ['needs_environment' => true, 'needs_context' => true, 'is_safe' => ['all']]),
            new TwigFunction('formtag', [$this, 'formTagFunction'], ['needs_context' => true, 'is_safe' => ['html']]),
            new TwigFunction('fieldtag', [$this, 'fieldTagFunction'], ['needs_context' => true, 'is_safe' => ['html']]),
        ];
    }

    public function getTokenParsers(): array
    {
        return [
            new FieldTagTokenParser(),
            new FormTagTokenParser(),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('camel2words', [$this, 'camel2words'], ['is_safe' => ['html']]),
        ];
    }

    public function camel2words(string $string): string
    {
        return Inflector::camel2words($string);
    }

    public function formieInclude(Environment $env, $context, $template, $variables = [], $withContext = true, $ignoreMissing = false, $sandboxed = false): string
    {
        // Get the form from the context
        $form = $context['form'] ?? null;

        if ($form) {
            // Render the provided include depending on form template overrides
            return $form->renderTemplate($template, array_merge($context, $variables));
        }

        return twig_include($env, $context, $template, $variables, $withContext, $ignoreMissing, $sandboxed);
    }

    public function formieSiteInclude(Environment $env, $context, $template, $variables = [], $withContext = true, $ignoreMissing = false, $sandboxed = false): string
    {
        $view = $context['view'];

        $oldTemplatesPath = $view->getTemplatesPath();
        $view->setTemplatesPath(Craft::$app->getPath()->getSiteTemplatesPath());

        $result = twig_include($env, $context, $template, $variables, $withContext, $ignoreMissing, $sandboxed);

        $view->setTemplatesPath($oldTemplatesPath);

        return $result;
    }

    public function formiePluginInclude(Environment $env, $context, $template, $variables = [], $withContext = true, $ignoreMissing = false, $sandboxed = false): string
    {
        $view = $context['view'];

        $oldTemplatesPath = $view->getTemplatesPath();

        $templatePath = Craft::getAlias('@verbb/formie/templates/_special/form-template');
        $view->setTemplatesPath($templatePath);

        $result = twig_include($env, $context, $template, $variables, $withContext, $ignoreMissing, $sandboxed);

        $view->setTemplatesPath($oldTemplatesPath);

        return $result;
    }

    public function formTagFunction($context, $key, $options = []): ?string
    {
        $form = $context['form'] ?? null;

        if ($form) {
            $htmlTag = $form->renderHtmlTag($key, $context);

            if ($htmlTag) {
                if ($options) {
                    $attributes = Html::mergeAttributes($htmlTag->attributes, $options);
                } else {
                    $attributes = $htmlTag->attributes;
                }

                // Grab a `text` attribute to use
                $text = ArrayHelper::remove($attributes, 'text');

                return Html::tag($htmlTag->tag, $text, $attributes);
            }
        }

        return null;
    }

    public function fieldTagFunction($context, $key, $options = []): ?string
    {
        $field = $context['field'] ?? null;

        if ($field) {
            $htmlTag = $field->renderHtmlTag($key, $context);

            if ($htmlTag) {
                if ($options) {
                    $attributes = Html::mergeAttributes($htmlTag->attributes, $options);
                } else {
                    $attributes = $htmlTag->attributes;
                }

                // Grab a `text` attribute to use
                $text = ArrayHelper::remove($attributes, 'text');

                return Html::tag($htmlTag->tag, $text, $attributes);
            }
        }

        return null;
    }
}
