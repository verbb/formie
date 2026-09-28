<?php

declare(strict_types=1);

use verbb\formie\helpers\{SchemaReadiness, Table};

it('keeps schema inspection at migration, repair, and bootstrap boundaries', function (): void {
    $sourceRoot = dirname(__DIR__, 2) . '/src';
    $allowed = [
        $sourceRoot . '/helpers/DbSchema.php',
        $sourceRoot . '/helpers/SchemaReadiness.php',
        $sourceRoot . '/services/Repair.php',
    ];
    $violations = [];
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sourceRoot));

    foreach ($files as $file) {
        if (!$file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        $path = $file->getPathname();

        if (str_starts_with($path, $sourceRoot . '/migrations/') || in_array($path, $allowed, true)) {
            continue;
        }

        $source = (string)file_get_contents($path);

        if (str_contains($source, 'DbSchema::columnExists(')
            || str_contains($source, 'DbSchema::tableExists(')
            || str_contains($source, '->columnExists(')
            || str_contains($source, '->tableExists(')
            || str_contains($source, '->getTableSchema(')
        ) {
            $violations[] = substr($path, strlen($sourceRoot) + 1);
        }
    }

    expect($violations)->toBe([]);
});

it('installs every column required by normal runtime paths', function (): void {
    $required = [
        Table::FORMIE_FORMS => ['createdById', 'updatedById', 'groupId', 'formStatusId', 'sourceSiteId'],
        Table::FORMIE_SUBMISSIONS => ['updatedById', 'signatureAccessKey', 'legacySignatureAccess', 'metadata'],
        Table::FORMIE_NOTIFICATIONS => ['dispatchTiming'],
        Table::FORMIE_PAYMENTS => ['redirectUrl'],
        Table::FORMIE_INTEGRATIONS => ['scope'],
        Table::FORMIE_CAPTCHA_PROVIDERS => ['scope'],
        Table::FORMIE_STENCILS => ['scope'],
        Table::FORMIE_SPAM_SETTINGS => [
            'enableHoneypot',
            'enableFormSubmitExpiration',
            'enableAllowedEmailDomains',
            'enableSuspiciousTextDetection',
        ],
    ];
    $db = Craft::$app->getDb();

    foreach ($required as $table => $columns) {
        expect($db->tableExists($table))->toBeTrue();

        foreach ($columns as $column) {
            expect($db->columnExists($table, $column))->toBeTrue();
        }
    }

    expect(SchemaReadiness::canHydrateRuntimeSettings())->toBeTrue();
});
