<?php

declare(strict_types=1);

use verbb\formie\base\Field;
use verbb\formie\fields\FileUpload;
use verbb\formie\fields\SingleLineText;

// Settings accepted when they were saved can't be allowed to abort an upgrade part way through.
function withoutEncryptionKey(callable $callback): void
{
    $settings = \verbb\formie\Formie::$plugin->getSettings();
    $configured = [getenv('FORMIE_ENCRYPTION_KEY'), $_ENV['FORMIE_ENCRYPTION_KEY'] ?? null, $_SERVER['FORMIE_ENCRYPTION_KEY'] ?? null, $settings->encryptionKey];

    putenv('FORMIE_ENCRYPTION_KEY');
    unset($_ENV['FORMIE_ENCRYPTION_KEY'], $_SERVER['FORMIE_ENCRYPTION_KEY']);
    $settings->encryptionKey = null;

    try {
        $callback();
    } finally {
        if ($configured[0] !== false) {
            putenv('FORMIE_ENCRYPTION_KEY=' . $configured[0]);
        }

        if ($configured[1] !== null) {
            $_ENV['FORMIE_ENCRYPTION_KEY'] = $configured[1];
        }

        if ($configured[2] !== null) {
            $_SERVER['FORMIE_ENCRYPTION_KEY'] = $configured[2];
        }

        $settings->encryptionKey = $configured[3];
        Field::$relaxLegacyUpgradeValidation = false;
    }
}

function encryptedFieldErrors(): array
{
    $field = new SingleLineText(['label' => 'Secret', 'handle' => 'secret', 'enableContentEncryption' => true]);
    $field->validate(['enableContentEncryption']);

    return $field->getErrors('enableContentEncryption');
}

it('still requires a key to encrypt a new field', function (): void {
    withoutEncryptionKey(function (): void {
        expect(encryptedFieldErrors())->toHaveCount(1)
            ->and(encryptedFieldErrors()[0])->toContain('FORMIE_ENCRYPTION_KEY');
    });
});

it('accepts a field that was already encrypted while an upgrade carries it over', function (): void {
    withoutEncryptionKey(function (): void {
        Field::$relaxLegacyUpgradeValidation = true;

        expect(encryptedFieldErrors())->toBe([]);
    });
});

it('rejects an upload field with a deleted volume except while an upgrade carries it over', function (string $source): void {
    $errors = static function() use ($source): array {
        $field = new FileUpload(['label' => 'CV', 'handle' => 'cv', 'uploadLocationSource' => $source]);
        $field->validate(['uploadLocationSource']);

        return $field->getErrors('uploadLocationSource');
    };

    try {
        expect($errors())->not->toBe([]);

        Field::$relaxLegacyUpgradeValidation = true;

        expect($errors())->toBe([]);
    } finally {
        Field::$relaxLegacyUpgradeValidation = false;
    }
})->with([
    'a deleted volume' => ['volume:4a28346d-4925-4b0a-b800-b7772c5e3623'],
    'no location' => [''],
]);

it('turns the upgrade relaxation on only while the Craft 5 migration moves layouts', function (): void {
    $source = file_get_contents(dirname(__DIR__, 2) . '/src/migrations/m231125_000000_craft5.php');

    expect(substr_count($source, 'Field::$relaxLegacyUpgradeValidation = true;'))->toBe(1)
        ->and(substr_count($source, 'Field::$relaxLegacyUpgradeValidation = false;'))->toBe(1)
        ->and(Field::$relaxLegacyUpgradeValidation)->toBeFalse();
});
