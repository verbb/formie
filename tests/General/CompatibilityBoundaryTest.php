<?php

use verbb\formie\base\Field;
use verbb\formie\compatibility\delivery\LegacyDeliveryJobTrait;
use verbb\formie\compatibility\fields\FieldRuntimeCompatibility;
use verbb\formie\compatibility\signatures\SignatureAccessCompatibility;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\SignatureAccess;
use verbb\formie\jobs\SendNotification;
use verbb\formie\jobs\TriggerIntegration;
use verbb\formie\models\IntegrationResult;
use verbb\formie\models\SubmissionErrors;
use verbb\formie\services\Rendering;
use verbb\formie\variables\Formie as FormieVariable;

it('keeps compatibility implementation behind explicit compatibility boundaries', function (): void {
    expect(class_uses(Field::class))->toContain(FieldRuntimeCompatibility::class)
        ->and(class_uses(SignatureAccess::class))->toContain(SignatureAccessCompatibility::class)
        ->and(class_uses(SendNotification::class))->toContain(LegacyDeliveryJobTrait::class)
        ->and(class_uses(TriggerIntegration::class))->toContain(LegacyDeliveryJobTrait::class)
        ->and(method_exists(Submission::class, 'usesLegacySignatureAccess'))->toBeFalse()
        ->and(method_exists(IntegrationResult::class, 'fromLegacy'))->toBeFalse()
        ->and(method_exists(SubmissionErrors::class, 'toLegacy'))->toBeFalse();
});

it('retains Formie 3 aliases without retaining intermediate Formie 4 beta names', function (): void {
    expect(class_exists('verbb\\formie\\base\\FormField'))->toBeTrue()
        ->and(class_exists('verbb\\formie\\services\\PredefinedOptions'))->toBeTrue()
        ->and(class_exists('verbb\\formie\\runtime\\models\\PageTransitionRequest'))->toBeFalse()
        ->and(class_exists('verbb\\formie\\runtime\\models\\SessionRefreshRequest'))->toBeFalse()
        ->and(class_exists('verbb\\formie\\runtime\\models\\SubmitRequest'))->toBeFalse()
        ->and(class_exists('verbb\\formie\\events\\ModifyRuntimeJsTranslationsEvent'))->toBeFalse()
        ->and(class_exists('verbb\\formie\\models\\RuntimeRenderFrame'))->toBeFalse()
        ->and(class_exists('verbb\\formie\\services\\RuntimeAssets'))->toBeFalse()
        ->and(class_exists('verbb\\formie\\compatibility\\runtime\\RuntimeCompatibility'))->toBeFalse()
        ->and(class_exists('verbb\\formie\\compatibility\\runtime\\RefreshTokensCompatibility'))->toBeFalse()
        ->and(method_exists(Rendering::class, 'renderRuntimeAssets'))->toBeFalse()
        ->and(method_exists(FormieVariable::class, 'renderRuntimeAssets'))->toBeFalse();
});
