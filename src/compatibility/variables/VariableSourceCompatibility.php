<?php
namespace verbb\formie\compatibility\variables;

use verbb\formie\Formie;
use verbb\formie\models\ReferenceExpression;
use verbb\formie\elements\Submission;
use verbb\formie\events\RegisterVariablesEvent;
use verbb\formie\helpers\Variables;
use verbb\formie\variables\VariableSource;

use Craft;

use yii\base\InvalidCallException;

class VariableSourceCompatibility
{
    // Public Methods
    // =========================================================================

    public static function registerLegacySource(RegisterVariablesEvent $event, string $target, string $handle, string $label): VariableSource
    {
        if (!Formie::$plugin->getCompatibility()->isCompatibilityModeEnabled()) {
            throw new InvalidCallException('RegisterVariablesEvent::register() is only available while compatibility mode is enabled. Push `VariableSource::create()` instances onto `RegisterVariablesEvent::$sources` instead.');
        }

        Craft::$app->getDeprecator()->log(
            RegisterVariablesEvent::class . '::register',
            'RegisterVariablesEvent::register() has been deprecated. Push `VariableSource::create()` instances onto `RegisterVariablesEvent::$sources` instead.'
        );

        $source = VariableSource::create(self::legacyHandleFromTargetAndHandle($target, $handle), $label);
        $event->sources[] = $source;

        return $source;
    }

    public static function legacyHandleFromTargetAndHandle(string $target, string $handle): string
    {
        $target = strtolower(trim($target));
        $handle = strtolower(trim($handle));

        if ($target === '' || $handle === '') {
            return '';
        }

        return $target . '_' . $handle;
    }

    public static function resolveLegacyToken(Submission $submission, ReferenceExpression $expr): mixed
    {
        if (!Formie::$plugin->getCompatibility()->isCompatibilityModeEnabled()) {
            return null;
        }

        $target = strtolower(trim($expr->target));
        $handle = strtolower(trim($expr->identifier));

        if ($target === '' || $handle === '' || Variables::isReservedVariableTarget($target)) {
            return null;
        }

        $legacyHandle = self::legacyHandleFromTargetAndHandle($target, $handle);

        if ($legacyHandle === '' || !Variables::findRegisteredVariableSource($legacyHandle)) {
            return null;
        }

        Craft::$app->getDeprecator()->log(
            __METHOD__,
            'Legacy custom variable tokens such as `{' . $target . ':' . $handle . '}` have been deprecated. Use `{custom:' . $legacyHandle . '}` instead.'
        );

        return Variables::resolveRegisteredVariableSourceByHandle($submission, $legacyHandle);
    }
}
