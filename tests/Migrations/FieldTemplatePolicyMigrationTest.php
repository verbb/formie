<?php

declare(strict_types=1);

use verbb\formie\helpers\FieldBuilderPolicy;
use verbb\formie\migrations\m261015_000000_field_template_policy;

it('preserves legacy capabilities explicitly without replacing deployed choices', function (): void {
    $config = Craft::$app->getProjectConfig();
    $original = $config->get('plugins.formie.settings');
    try {
        foreach (FieldBuilderPolicy::legacyTemplateSettings() as $key => $value) {
            $config->remove('plugins.formie.settings.' . $key);
        }
        $migration = new m261015_000000_field_template_policy();
        expect($migration->safeUp())->toBeTrue();
        foreach (FieldBuilderPolicy::legacyTemplateSettings() as $key => $value) {
            expect($config->get('plugins.formie.settings.' . $key))->toBe($value);
            $config->set('plugins.formie.settings.' . $key, !$value);
        }
        expect($migration->safeUp())->toBeTrue();
        foreach (FieldBuilderPolicy::legacyTemplateSettings() as $key => $value) {
            expect($config->get('plugins.formie.settings.' . $key))->toBe(!$value);
        }
    } finally {
        $config->set('plugins.formie.settings', $original);
    }
});
