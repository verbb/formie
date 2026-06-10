<?php
namespace verbb\formie\web\twig;

use verbb\formie\base\Field;
use verbb\formie\elements\Form;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\Html;
use verbb\formie\helpers\HtmlHelper;
use verbb\formie\helpers\RichTextHelper;
use verbb\formie\models\Notification;
use verbb\formie\models\SlotTag;
use verbb\formie\theme\context\RenderContext;
use verbb\formie\web\twig\tokenparsers\FieldTagTokenParser;
use verbb\formie\web\twig\tokenparsers\FormTagTokenParser;

use Craft;

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
            new TwigFunction('getHtmlEditorConfig', [new HtmlHelper(), 'getHtmlEditorConfig']),
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
        // This might be an email notification template
        $notification = $context['notification'] ?? null;

        if ($notification instanceof Notification) {
            // Render the provided include depending on form template overrides
            return $notification->renderTemplate($template, array_merge($context, $variables));
        }

        // Get the form from the context
        $form = $context['form'] ?? null;

        if ($form instanceof Form) {
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

        if ($form instanceof Form) {
            $htmlTag = $form->renderSlotTag($key, self::createRenderContext($context));

            if ($htmlTag) {
                $attributes = self::mergeTagAttributes($htmlTag->attributes, $options);
                $attributes = self::applyContextTagDefaults($key, $htmlTag->tag, $attributes, $context);

                // Grab a `text` attribute to use
                $text = ArrayHelper::remove($attributes, 'text');

                $content = $htmlTag->composeContent($text);

                return Html::tag($htmlTag->tag, $content, $attributes);
            }
        }

        return null;
    }

    public function fieldTagFunction($context, $key, $options = []): ?string
    {
        $field = $context['field'] ?? null;

        if ($field instanceof Field) {
            $htmlTag = $field->renderSlotTag($key, self::createRenderContext($context));

            if ($htmlTag) {
                return self::formatSlotTagHtml($key, $htmlTag, $context, $options);
            }
        }

        return null;
    }

    /**
     * Build opening/closing HTML for a resolved field slot (same rules as the `fieldtag` Twig function).
     *
     * @param array<string, mixed> $twigContext
     * @param array<string, mixed> $options
     */
    public static function formatSlotTagHtml(string $key, SlotTag $htmlTag, array $twigContext, array $options = []): string
    {
        $attributes = self::mergeTagAttributes($htmlTag->attributes, $options);
        $attributes = self::applyContextTagDefaults($key, $htmlTag->tag, $attributes, $twigContext);

        $text = ArrayHelper::remove($attributes, 'text');
        $content = $htmlTag->composeContent($text);

        return Html::tag($htmlTag->tag, $content, $attributes);
    }

    public static function createRenderContext(array $context): RenderContext
    {
        return RenderContext::from($context, [
            'form' => $context['form'] ?? null,
            'field' => $context['field'] ?? null,
        ]);
    }

    public static function mergeTagAttributes(array $attributes, array $options = []): array
    {
        if (!$options) {
            return $attributes;
        }

        $reset = ArrayHelper::remove($options, 'reset', false);
        $mergeOptions = [];

        if ($reset) {
            $mergeOptions['resetClassA'] = true;
        }

        return Html::mergeAttributes($attributes, $options, $mergeOptions);
    }

    public static function applyContextTagDefaults(string $key, string $tagName, array $attributes, array $context): array
    {
        if ($key !== 'fieldInput') {
            return $attributes;
        }

        $value = $context['value'] ?? null;

        if ($tagName === 'textarea' && !array_key_exists('text', $attributes) && $value !== null) {
            $attributes['text'] = $value;
        }

        if ($tagName === 'input' && !array_key_exists('value', $attributes) && $value !== null) {
            $attributes['value'] = $value;
        }

        return $attributes;
    }
}
