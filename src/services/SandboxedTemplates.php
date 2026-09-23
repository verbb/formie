<?php
namespace verbb\formie\services;

use verbb\formie\elements\Submission;

use Craft;

use yii\base\Model;

use verbb\base\services\Templates as BaseTemplates;

use DateTimeInterface;

class SandboxedTemplates extends BaseTemplates
{
    // Public Methods
    // =========================================================================

    public function renderSandboxedObjectTemplate(string $template, mixed $object, array $variables = [], string|false|null $autoescape = null): string
    {
        $aliases = [];
        $prefix = '__formie_env_' . md5($template) . '_';

        $template = preg_replace_callback('/\$\{[a-zA-Z_][a-zA-Z0-9_]*\}/', static function(array $match) use (&$aliases, $prefix): string {
            $token = $prefix . count($aliases) . '__';
            $aliases[$token] = $match[0];

            return $token;
        }, $template);

        return strtr(parent::renderSandboxedObjectTemplate($template, $object, $variables, $autoescape), $aliases);
    }

    public function renderSandboxedString(string $template, array $variables = [], string|false|null $autoescape = null): string
    {
        return parent::renderSandboxedString($template, $variables, $autoescape);
    }

    /**
     * Resolves redirect placeholders without compiling or executing Twig.
     */
    public function renderObjectTokens(string $template, mixed $object): string
    {
        $aliases = [];
        $prefix = '__formie_literal_' . md5($template) . '_';

        // Environment variables and complete Twig constructs stay literal. This
        // prevents both accidental partial replacement and any Twig execution.
        $template = preg_replace_callback('/\$\{[a-zA-Z_][a-zA-Z0-9_]*\}|\{\{.*?\}\}|\{%.*?%\}|\{#.*?#\}/s', static function(array $match) use (&$aliases, $prefix): string {
            $token = $prefix . count($aliases) . '__';
            $aliases[$token] = $match[0];

            return $token;
        }, $template);

        $tokens = $this->_getObjectTokens($object);

        $template = (string)preg_replace_callback('/(?<![\{$])\{([a-zA-Z_][a-zA-Z0-9_.:-]*)\}(?!\})/', static function(array $matches) use ($tokens): string {
            return $tokens[$matches[1]] ?? $matches[0];
        }, $template);

        return strtr($template, $aliases);
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


    // Private Methods
    // =========================================================================

    private function _getObjectTokens(mixed $object): array
    {
        $tokens = [];

        if (is_array($object)) {
            foreach ($object as $name => $value) {
                if (is_string($name)) {
                    $tokens[$name] = $this->_stringifyTokenValue($value);
                }
            }
        } else if ($object instanceof Model) {
            foreach ($object->attributes() as $name) {
                $value = $object->getAttribute($name);
                $tokens[$name] = $this->_stringifyTokenValue($value);
                $this->_addNestedTokens($tokens, $name, $value);
            }
        }

        if ($object instanceof Submission) {
            foreach ($object->attributes() as $name) {
                $value = $tokens[$name] ?? '';
                $tokens["submission.$name"] = $value;
                $tokens["submission:$name"] = $value;
            }

            $tokens['submissionId'] = $tokens['id'] ?? '';
            $tokens['submissionUid'] = $tokens['uid'] ?? '';

            foreach ($object->getFields() as $field) {
                $handle = $field->handle;

                if (!$handle) {
                    continue;
                }

                $fieldValue = $object->getFieldValue($handle);
                $value = $this->_stringifyTokenValue($field->getValueAsString($fieldValue, $object));
                $tokens[$handle] = $value;
                $tokens["field.$handle"] = $value;
                $tokens["field:$handle"] = $value;
                $this->_addNestedTokens($tokens, $handle, $fieldValue);
                $this->_addNestedTokens($tokens, "field.$handle", $fieldValue);
                $this->_addNestedTokens($tokens, "field:$handle", $fieldValue);
            }

            if ($form = $object->getForm()) {
                foreach (['id', 'handle', 'title'] as $name) {
                    $value = $this->_stringifyTokenValue($form->$name);
                    $tokens["form.$name"] = $value;
                    $tokens["form:$name"] = $value;
                }
            }
        }

        return $tokens;
    }

    private function _addNestedTokens(array &$tokens, string $prefix, mixed $value, int $depth = 0): void
    {
        if ($depth >= 4) {
            return;
        }

        if ($value instanceof DateTimeInterface) {
            foreach (['year' => 'Y', 'month' => 'm', 'day' => 'd', 'hour' => 'H', 'minute' => 'i', 'second' => 's', 'ampm' => 'a'] as $name => $format) {
                $tokens["$prefix.$name"] = $value->format($format);
            }

            return;
        }

        if ($value instanceof Model) {
            $value = array_combine($value->attributes(), array_map(fn(string $name) => $value->getAttribute($name), $value->attributes()));
        }

        if (!is_array($value)) {
            return;
        }

        foreach ($value as $name => $nestedValue) {
            if (!is_string($name) || !preg_match('/^[a-zA-Z_][a-zA-Z0-9_-]*$/', $name)) {
                continue;
            }

            $nestedPrefix = "$prefix.$name";
            $tokens[$nestedPrefix] = $this->_stringifyTokenValue($nestedValue);
            $this->_addNestedTokens($tokens, $nestedPrefix, $nestedValue, $depth + 1);
        }
    }

    private function _stringifyTokenValue(mixed $value): string
    {
        if ($value === null || $value === false) {
            return '';
        }

        if ($value === true) {
            return '1';
        }

        if (is_scalar($value)) {
            return (string)$value;
        }

        if (is_array($value)) {
            $values = [];

            foreach ($value as $item) {
                $values[] = $this->_stringifyTokenValue($item);
            }

            return implode(', ', $values);
        }

        return '';
    }
}
