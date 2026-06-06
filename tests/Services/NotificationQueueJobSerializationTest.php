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

it('can serialize a send notification job after sanitizing submission field values', function (): void {
    $invalidUtf8 = "\xC3\x28";
    $job = new SendNotification([
        'submissionId' => 1,
        'notificationId' => 2,
    ]);
    $job->submissionData = [
        'fields' => ['message' => $invalidUtf8],
    ];

    $job = QueueJobDataHelper::sanitizeJobObject($job);

    $serialized = Craft::$app->getQueue()->serializer->serialize($job);

    expect($serialized)->toBeString()->not->toBeEmpty();
});
