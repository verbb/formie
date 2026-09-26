<?php

declare(strict_types=1);

use verbb\formie\helpers\QueueJobDataHelper;
use verbb\formie\jobs\SendNotification;

it('sanitizes invalid utf-8 strings for queue job debug data', function (): void {
    $invalidUtf8 = "\xC3\x28";

    $sanitized = QueueJobDataHelper::sanitizeForSerialization([
        'name' => $invalidUtf8,
        'nested' => ['value' => $invalidUtf8],
    ]);

    expect($sanitized)->toBeArray()
        ->and(mb_check_encoding((string)$sanitized['name'], 'UTF-8'))->toBeTrue()
        ->and(mb_check_encoding((string)$sanitized['nested']['value'], 'UTF-8'))->toBeTrue();
});

it('serializes notification jobs using only a stable attempt locator', function (): void {
    $job = new SendNotification(['deliveryAttemptUid' => '17854078-7aec-4843-a91b-8a9e1a21f7c0']);
    $serialized = Craft::$app->getQueue()->serializer->serialize($job);
    expect($serialized)->toBeString()->not->toContain('submissionData', 'notificationData', 'email');
    expect(strlen($serialized))->toBeLessThan(1024);
});
