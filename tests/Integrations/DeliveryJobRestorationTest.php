<?php

declare(strict_types=1);

use craft\queue\BaseJob;
use verbb\formie\jobs\SendNotification;
use verbb\formie\jobs\TriggerIntegration;
use yii\queue\sync\Queue;

it('restores Craft progress state for serialized delivery jobs', function (string $jobClass, bool $legacy): void {
    $job = new $jobClass();
    if ($legacy) {
        $job->__unserialize(['submissionId' => 123, 'notificationId' => 456, 'integrationHandle' => 'crm']);
    } else {
        $job->deliveryAttemptUid = 'delivery-progress-fixture';
    }
    $serialized = serialize($job);
    $restored = unserialize($serialized);

    // Both workers report progress after their delivery completes.
    (new ReflectionMethod(BaseJob::class, 'setProgress'))->invoke($restored, new Queue(), 1);
    expect(serialize($restored))->toBe($serialized);
})->with([SendNotification::class, TriggerIntegration::class])->with([false, true]);
