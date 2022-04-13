<?php
namespace verbb\formie\web\twig;

use verbb\formie\Formie;
use verbb\formie\helpers\RichTextHelper;

use Craft;
use craft\web\View;

use Twig_Extension;
use Twig_SimpleFunction;
use Twig_SimpleFilter;
use Twig_Environment;

use yii\helpers\Inflector;

class Extension extends Twig_Extension
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
            new Twig_SimpleFunction('getRichTextConfig', [new RichTextHelper(), 'getRichTextConfig']),
            new Twig_SimpleFunction('formieInclude', [$this, 'formieInclude'], ['needs_environment' => true, 'needs_context' => true, 'is_safe' => ['all']]),
            new Twig_SimpleFunction('formieSiteInclude', [$this, 'formieSiteInclude'], ['needs_environment' => true, 'needs_context' => true, 'is_safe' => ['all']]),
            new Twig_SimpleFunction('formiePluginInclude', [$this, 'formiePluginInclude'], ['needs_environment' => true, 'needs_context' => true, 'is_safe' => ['all']]),
        ];
    }

    public function getFilters(): array
    {
        return [
            new Twig_SimpleFilter('camel2words', [$this, 'camel2words'], ['is_safe' => ['html']]),
        ];
    }

    public function camel2words(string $string): string
    {
        return Inflector::camel2words($string);
    }

    public function formieInclude(Twig_Environment $env, $context, $template, $variables = [], $withContext = true, $ignoreMissing = false, $sandboxed = false) {
        // Get the form from the context
        $form = $context['form'] ?? null;

        if ($form) {
            $view = $context['view'];

            // Render the provided include depending on form template overrides
            $templatePath = Formie::$plugin->getRendering()->getFormComponentTemplatePath($form, $template);
            $view->setTemplatesPath($templatePath);
        }
        
        return twig_include($env, $context, $template, $variables, $withContext, $ignoreMissing, $sandboxed);
    }

    public function formieSiteInclude(Twig_Environment $env, $context, $template, $variables = [], $withContext = true, $ignoreMissing = false, $sandboxed = false)
    {
        $view = $context['view'];

        $oldTemplatesPath = $view->getTemplatesPath();
        $view->setTemplatesPath(Craft::$app->path->getSiteTemplatesPath());

        $result = twig_include($env, $context, $template, $variables, $withContext, $ignoreMissing, $sandboxed);

        $view->setTemplatesPath($oldTemplatesPath);

        return $result;
    }

    public function formiePluginInclude(Twig_Environment $env, $context, $template, $variables = [], $withContext = true, $ignoreMissing = false, $sandboxed = false)
    {
        $view = $context['view'];

        $oldTemplatesPath = $view->getTemplatesPath();

        $templatePath = Craft::getAlias('@verbb/formie/templates/_special/form-template');
        $view->setTemplatesPath($templatePath);

        $result = twig_include($env, $context, $template, $variables, $withContext, $ignoreMissing, $sandboxed);

        $view->setTemplatesPath($oldTemplatesPath);

        return $result;
    }
}
