<?php
namespace verbb\formie\compatibility\integrations;

use ReflectionMethod;

trait LegacyIntegrationApiTrait
{
    // Properties
    // =========================================================================

    private array $_legacyApiCalls = [];


    // Protected Methods
    // =========================================================================

    protected function callLegacyIntegrationMethod(string $method, string $owner, array $arguments, callable $fallback, ?callable $normalizeLegacyResult = null, ?string $canonicalMethod = null): mixed
    {
        $legacyClass = (new ReflectionMethod($this, $method))->getDeclaringClass()->getName();
        $canonicalClass = $canonicalMethod ? (new ReflectionMethod($this, $canonicalMethod))->getDeclaringClass()->getName() : null;

        // A canonical override on the same or a more-derived class takes precedence.
        $useLegacy = $legacyClass !== $owner && ($canonicalClass === null || is_subclass_of($legacyClass, $canonicalClass));

        if (!isset($this->_legacyApiCalls[$method]) && $useLegacy) {
            $result = $this->callCanonicalIntegrationMethod($method, fn() => $this->$method(...$arguments));

            return $normalizeLegacyResult ? $normalizeLegacyResult($result) : $result;
        }

        return $fallback();
    }

    protected function callCanonicalIntegrationMethod(string $method, callable $callback): mixed
    {
        // Parent calls from old overrides must reach native behaviour exactly once.
        $previous = $this->_legacyApiCalls[$method] ?? null;
        $this->_legacyApiCalls[$method] = true;

        try {
            return $callback();
        } finally {
            if ($previous === null) {
                unset($this->_legacyApiCalls[$method]);
            } else {
                $this->_legacyApiCalls[$method] = $previous;
            }
        }
    }
}
