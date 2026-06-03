<?php

declare(strict_types=1);

use verbb\formie\models\Settings;

it('treats a zero megabyte email attachment limit as unlimited', function (): void {
    $settings = new Settings(['maxEmailAttachmentSizeMb' => 0]);

    expect($settings->getMaxEmailAttachmentSizeBytes())->toBeNull();
});

it('converts the email attachment megabyte setting to bytes', function (): void {
    $settings = new Settings(['maxEmailAttachmentSizeMb' => 15]);

    expect($settings->getMaxEmailAttachmentSizeBytes())->toBe(15 * 1024 * 1024);
});
