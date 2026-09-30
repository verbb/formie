<?php
namespace verbb\formie\compatibility\payments;

use ReflectionObject;
use ReflectionProperty;

/** Conservative account binding for Formie 3 providers without credential attributes. */
trait LegacyPaymentCredentials
{
    // Protected Methods
    // =========================================================================

    protected function getLegacyPaymentCredentialAttributes(): array
    {
        $attributes = [];
        foreach ((new ReflectionObject($this))->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if (!$property->isStatic() && $property->isInitialized($this)
                && preg_match('/password|secret|token|authorization|api.?key|credential/i', $property->getName())) {
                $attributes[] = $property->getName();
            }
        }
        return $attributes;
    }
}
