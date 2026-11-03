<?php

declare(strict_types=1);

use craft\db\Query;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use verbb\formie\helpers\Table;
use verbb\formie\migrations\m261103_000001_local_reports;
use verbb\formie\services\Reports;
use verbb\formie\services\ScheduledReports;

/**
 * Runs the migration against supplied config entries, using a real project config read of
 * what's stored and, when given, what's in YAML. Columns already exist, so it issues no DDL.
 */
function runLocalReportsMigration(array $reports, array $scheduled = [], ?array $yamlReports = null, ?array $yamlScheduled = null): void
{
    $projectConfig = Craft::$app->getProjectConfig();
    $property = new ReflectionProperty($projectConfig, '_currentWorkingConfig');
    $externalProperty = new ReflectionProperty($projectConfig, '_externalConfig');
    $originalWorking = $property->getValue($projectConfig);
    $originalExternal = $externalProperty->getValue($projectConfig);
    $config = $projectConfig->get();
    $config['formie']['reports'] = $reports;
    $config['formie']['scheduledReports'] = $scheduled;
    // The migration only trusts YAML when a project.yaml exists, so give it one for this run.
    $yamlPath = Craft::$app->getPath()->getProjectConfigFilePath();
    $createdYaml = $yamlReports !== null && !file_exists($yamlPath);

    if ($createdYaml) {
        craft\helpers\FileHelper::writeToFile($yamlPath, "{}\n");
    }

    try {
        $property->setValue($projectConfig, new \craft\models\ProjectConfigData($config, $projectConfig));

        if ($yamlReports !== null) {
            $external = $config;
            $external['formie']['reports'] = $yamlReports;
            $external['formie']['scheduledReports'] = $yamlScheduled ?? $scheduled;
            $externalProperty->setValue($projectConfig, new \craft\models\ReadOnlyProjectConfigData($external));
        }

        $migration = new m261103_000001_local_reports(['compact' => true]);
        expect($migration->safeUp())->toBeTrue();
    } finally {
        $property->setValue($projectConfig, $originalWorking);
        $externalProperty->setValue($projectConfig, $originalExternal);

        if ($createdYaml) {
            unlink($yamlPath);
        }
    }
}

function localReportEntry(string $handle, array $settings = []): array
{
    return [
        'name' => ucfirst($handle),
        'handle' => $handle,
        'sortOrder' => 3,
        'settings' => array_merge(['filters' => ['formIds' => [7, 8]], 'export' => ['filename' => 'kept-{date}']], $settings),
    ];
}

function localReportRow(string $uid): ?array
{
    return (new Query())->from(Table::FORMIE_REPORTS)->where(['uid' => $uid])->one() ?: null;
}

function insertLegacyReport(string $handle): array
{
    $uid = StringHelper::UUID();
    Db::insert(Table::FORMIE_REPORTS, ['name' => 'Old name', 'handle' => $handle, 'sortOrder' => 1, 'uid' => $uid]);

    return ['uid' => $uid, 'id' => (int)Db::idByUid(Table::FORMIE_REPORTS, $uid)];
}

it('copies report settings and schedule details from project config', function (): void {
    $handle = 'localImport' . uniqid();
    $report = insertLegacyReport($handle);
    $scheduleUid = StringHelper::UUID();
    Db::insert(Table::FORMIE_SCHEDULED_REPORTS, ['reportId' => $report['id'], 'name' => 'Old', 'enabled' => false, 'lastSentAt' => '2026-10-01 08:00:00', 'uid' => $scheduleUid]);

    runLocalReportsMigration([$report['uid'] => localReportEntry($handle)], [$scheduleUid => [
        'reportUid' => $report['uid'],
        'name' => 'Monday summary',
        'enabled' => true,
        'delivery' => ['recipients' => ['team@example.test'], 'frequency' => 'weekly'],
    ]]);

    $row = localReportRow($report['uid']);
    $schedule = (new Query())->from(Table::FORMIE_SCHEDULED_REPORTS)->where(['uid' => $scheduleUid])->one();

    expect((int)$row['id'])->toBe($report['id'])
        ->and($row['name'])->toBe(ucfirst($handle))
        ->and((int)$row['sortOrder'])->toBe(3)
        ->and(Json::decode($row['settings'])['filters']['formIds'])->toBe([7, 8])
        ->and((int)$schedule['reportId'])->toBe($report['id'])
        ->and($schedule['name'])->toBe('Monday summary')
        ->and((bool)$schedule['enabled'])->toBeTrue()
        ->and($schedule['lastSentAt'])->toBe('2026-10-01 08:00:00')
        ->and(Json::decode($schedule['delivery'])['recipients'])->toBe(['team@example.test']);

    // The services now read what was copied, with no project config entry behind it.
    $reports = new Reports();
    $model = $reports->getReportByUid($report['uid']);
    expect($model->getSettingsModel()->export['filename'])->toBe('kept-{date}');
    $scheduledModel = (new ScheduledReports())->getScheduledReportByUid($scheduleUid);
    expect($scheduledModel->getDeliveryModel()->recipients)->toBe(['team@example.test'])
        ->and($scheduledModel->lastSentAt?->format('Y-m-d'))->toBe('2026-10-01');
});

it('gives the same result when it runs again', function (): void {
    $handle = 'localRepeat' . uniqid();
    $report = insertLegacyReport($handle);
    $entries = [$report['uid'] => localReportEntry($handle)];

    runLocalReportsMigration($entries);
    $first = localReportRow($report['uid']);
    runLocalReportsMigration($entries);

    expect(localReportRow($report['uid']))->toBe($first);
});

it('applies YAML deployed with the upgrade', function (): void {
    $changed = insertLegacyReport('localChanged' . uniqid());
    $removed = insertLegacyReport('localRemoved' . uniqid());
    $changedHandle = (new Query())->from(Table::FORMIE_REPORTS)->select('handle')->where(['uid' => $changed['uid']])->scalar();
    $removedHandle = (new Query())->from(Table::FORMIE_REPORTS)->select('handle')->where(['uid' => $removed['uid']])->scalar();
    $addedUid = StringHelper::UUID();
    $addedHandle = 'localAdded' . uniqid();
    $scheduleUid = StringHelper::UUID();

    runLocalReportsMigration(
        [$changed['uid'] => localReportEntry($changedHandle), $removed['uid'] => localReportEntry($removedHandle)],
        [],
        [
            $changed['uid'] => localReportEntry($changedHandle, ['export' => ['filename' => 'from-yaml']]),
            $addedUid => localReportEntry($addedHandle),
        ],
        [$scheduleUid => ['reportUid' => $addedUid, 'name' => 'New schedule', 'delivery' => ['frequency' => 'daily']]],
    );

    $added = localReportRow($addedUid);

    expect(Json::decode(localReportRow($changed['uid'])['settings'])['export']['filename'])->toBe('from-yaml')
        ->and(localReportRow($removed['uid'])['dateDeleted'])->not->toBeNull()
        ->and($added['handle'])->toBe($addedHandle)
        ->and((int)(new Query())->from(Table::FORMIE_SCHEDULED_REPORTS)->select('reportId')->where(['uid' => $scheduleUid])->scalar())->toBe((int)$added['id']);
});

it('skips handle collisions and schedules with no report without failing', function (): void {
    $taken = insertLegacyReport('localTaken' . uniqid());
    $takenHandle = (new Query())->from(Table::FORMIE_REPORTS)->select('handle')->where(['uid' => $taken['uid']])->scalar();
    $collidingUid = StringHelper::UUID();
    $scheduleUid = StringHelper::UUID();

    runLocalReportsMigration(
        [$collidingUid => localReportEntry($takenHandle)],
        [$scheduleUid => ['reportUid' => StringHelper::UUID(), 'name' => 'Orphan', 'delivery' => []]],
    );

    expect(localReportRow($collidingUid))->toBeNull()
        ->and((new Query())->from(Table::FORMIE_SCHEDULED_REPORTS)->where(['uid' => $scheduleUid])->exists())->toBeFalse();
});

it('keeps reports that have no project config entry', function (): void {
    $report = insertLegacyReport('localOrphan' . uniqid());

    runLocalReportsMigration([]);

    expect(localReportRow($report['uid'])['dateDeleted'])->toBeNull();
});

it('has the storage columns on a new install', function (): void {
    $db = Craft::$app->getDb();

    expect($db->columnExists(Table::FORMIE_REPORTS, 'settings'))->toBeTrue()
        ->and($db->columnExists(Table::FORMIE_SCHEDULED_REPORTS, 'delivery'))->toBeTrue();
});
