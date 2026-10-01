<?php
namespace verbb\formie\services;

use verbb\formie\references\ReferenceContext;
use verbb\formie\references\ReferenceOutputContext;

use Craft;

use Closure;

use verbb\base\services\Templates as BaseTemplates;

class Templates extends BaseTemplates
{
    // Properties
    // =========================================================================

    public array $additionalAllowedTags = [];
    public array $additionalAllowedFilters = [];
    public array $additionalAllowedFunctions = [];
    public array $additionalAllowedMethods = [];
    public array $additionalAllowedProperties = [];

    // Public Methods
    // =========================================================================

    public function init(): void
    {
        parent::init();

        // The event widens Base's explicit policy for every output escaping mode.
        $this->allowedTags = array_values(array_unique(array_merge($this->getDefaultSandboxedAllowedTags(), $this->additionalAllowedTags)));
        $this->allowedFilters = array_values(array_unique(array_merge($this->getDefaultSandboxedAllowedFilters(), $this->additionalAllowedFilters)));
        $this->allowedFunctions = array_values(array_unique(array_merge($this->getDefaultSandboxedAllowedFunctions(), $this->additionalAllowedFunctions)));
        $this->allowedMethods = $this->getDefaultSandboxedAllowedMethods();

        foreach ($this->additionalAllowedMethods as $class => $methods) {
            $this->allowedMethods[$class] = array_values(array_unique(array_merge($this->allowedMethods[$class] ?? [], (array)$methods)));
        }

        $this->allowedProperties = $this->getDefaultSandboxedAllowedProperties();

        foreach ($this->additionalAllowedProperties as $class => $additional) {
            $default = $this->allowedProperties[$class] ?? null;

            if ($default === null) {
                $this->allowedProperties[$class] = $additional;
                continue;
            }

            $this->allowedProperties[$class] = static function(object $object, string $property) use ($default, $additional): bool {
                return ($default instanceof Closure ? $default($object, $property) : in_array($property, (array)$default, true))
                    || ($additional instanceof Closure ? $additional($object, $property) : in_array($property, (array)$additional, true));
            };
        }
    }

    public function renderSandboxedObjectTemplate(string $template, mixed $object, array $variables = [], string|false|null $autoescape = null): string
    {
        $aliases = [];
        $prefix = '__formie_env_' . md5($template) . '_';

        $template = preg_replace_callback('/\$\{[a-zA-Z_][a-zA-Z0-9_]*\}/', static function(array $match) use (&$aliases, $prefix): string {
            $token = $prefix . count($aliases) . '__';
            $aliases[$token] = $match[0];

            return $token;
        }, $template);

        if ($object instanceof \verbb\formie\elements\Submission) {
            $context = ReferenceContext::forSubmission($object, $variables['notification'] ?? null);
            $template = preg_replace_callback('/(?<!\{)\{([a-zA-Z][^{}]*)\}(?!\})/', static function(array $match) use (&$aliases, $prefix, $context, $autoescape): string {
                $reference = $match[0];

                // Bare object-template properties were stable filename/subpath syntax.
                if (in_array($match[1], ['id', 'uid', 'title', 'url'], true)) {
                    $reference = '{submission:' . $match[1] . '}';
                } elseif (preg_match('/^[a-zA-Z][a-zA-Z0-9_.]*$/D', $match[1]) && $context->form->getFieldByHandle(explode('.', $match[1])[0])) {
                    $reference = '{field:' . $match[1] . '}';
                }
                $token = $prefix . count($aliases) . '__';
                $aliases[$token] = \verbb\formie\helpers\References::interpolateText($reference, $context, $autoescape === false ? ReferenceOutputContext::PlainText : ReferenceOutputContext::Html);
                return $token;
            }, $template);
        }

        // Resolve authored references before rendering, insert their data afterwards.
        // Neither submitted Twig nor submitted reference syntax gets a second pass.
        return strtr(parent::renderSandboxedObjectTemplate($template, $object, $variables, $autoescape), $aliases);
    }

    public function renderSandboxedString(string $template, array $variables = [], string|false|null $autoescape = null): string
    {
        return parent::renderSandboxedString($template, $variables, $autoescape);
    }

    public function getSandboxedVariables(): array
    {
        $site = Craft::$app->getSites()->getCurrentSite();

        return [
            'site' => $site,
            'currentSite' => $site,
            'siteName' => $site->getName(),
            'siteUrl' => $site->getBaseUrl(),
        ];
    }
}
