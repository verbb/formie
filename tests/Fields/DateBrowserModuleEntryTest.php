<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\models\BrowserModuleEntry;

it('passes includeFlatpickrCss through the date-picker client module config', function (): void {
    $previousSetting = Formie::$plugin->getSettings()->includeFlatpickrCss;

    try {
        Formie::$plugin->getSettings()->includeFlatpickrCss = false;

        $form = formie()
            ->form(['title' => 'Flatpickr CSS Setting'])
            ->dateField('eventDate', [
                'displayType' => 'datePicker',
            ])
            ->create();

        $modules = Formie::$plugin->getBrowserModuleManifestBuilder()->buildCanonical($form, BrowserModuleEntry::SURFACE_SERVER_RENDERED)['entries'];
        $datePickerModule = current(array_filter($modules, static fn(array $module): bool => ($module['moduleId'] ?? null) === 'formie:date-picker')) ?: null;

        expect($datePickerModule)->not->toBeNull()
            ->and($datePickerModule['config']['includeFlatpickrCss'] ?? null)->toBeFalse();
    } finally {
        Formie::$plugin->getSettings()->includeFlatpickrCss = $previousSetting;
    }
});
