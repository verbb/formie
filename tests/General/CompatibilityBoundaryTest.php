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

it('keeps compatibility implementation behind explicit compatibility boundaries', function (): void {
    expect(class_uses(Field::class))->toContain(FieldRuntimeCompatibility::class)
        ->and(class_uses(SignatureAccess::class))->toContain(SignatureAccessCompatibility::class)
        ->and(class_uses(SendNotification::class))->toContain(LegacyDeliveryJobTrait::class)
        ->and(class_uses(TriggerIntegration::class))->toContain(LegacyDeliveryJobTrait::class)
        ->and(method_exists(Submission::class, 'usesLegacySignatureAccess'))->toBeFalse()
        ->and(method_exists(IntegrationResult::class, 'fromLegacy'))->toBeFalse()
        ->and(method_exists(SubmissionErrors::class, 'toLegacy'))->toBeFalse();
});
