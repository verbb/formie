<?php

declare(strict_types=1);

use craft\db\Query;
use craft\elements\Asset;
use craft\helpers\StringHelper;
use Tests\Support\UploadTestHelper;
use verbb\formie\elements\db\SubmissionQuery;
use verbb\formie\elements\Submission;
use verbb\formie\fields\FileUpload;
use verbb\formie\Formie;
use verbb\formie\helpers\FileUploadRetentionHelper;
use verbb\formie\helpers\Table;
use yii\base\Event;

beforeEach(function (): void {
    $this->releaseTransaction = Craft::$app->getDb()->beginTransaction();
    $this->releaseAssets = [];
    UploadTestHelper::ensureUploadVolume();
});

afterEach(function (): void {
    try {
        foreach ($this->releaseAssets as $assetId) {
            $asset = Asset::find()->id($assetId)->status(null)->trashed(null)->one();
            if ($asset && $asset->getVolume()->fileExists($asset->getPath())) {
                $asset->getVolume()->deleteFile($asset->getPath());
            }
        }
    } finally {
        $this->releaseTransaction->rollBack();
        Formie::$plugin->getForms()->invalidateFormCaches();
        Formie::$plugin->getFields()->resetFieldRegistryCache();
    }
});

function releaseRemovedAsset(object $test): Asset
{
    $asset = UploadTestHelper::seedAsset('release-removed-' . uniqid() . '.txt', 'release retained file');
    $test->releaseAssets[] = (int)$asset->id;
    return $asset;
}

function measureReleaseRemoved(callable $callback): array
{
    $hydrated = 0;
    $scans = 0;
    $populate = static function () use (&$hydrated): void { $hydrated++; };
    $prepare = static function () use (&$scans): void {
        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 20) as $frame) {
            if (($frame['function'] ?? '') === 'isReferenced') {
                $scans++;
                break;
            }
        }
    };
    $logger = Craft::getLogger();
    $flushInterval = $logger->flushInterval;
    $logger->flushInterval = 0;
    $start = count($logger->messages);
    $db = Craft::$app->getDb();
    $logging = $db->enableLogging;
    $db->enableLogging = true;
    Event::on(SubmissionQuery::class, SubmissionQuery::EVENT_AFTER_POPULATE_ELEMENT, $populate);
    Event::on(SubmissionQuery::class, SubmissionQuery::EVENT_BEFORE_PREPARE, $prepare);
    try {
        $result = $callback();
        // Command logs contain the executed SQL, after Craft has applied unique().
        $sql = [];
        foreach (array_slice($logger->messages, $start) as $message) {
            if ($message[2] === 'yii\\db\\Command::query' && is_string($message[0]) && str_contains($message[0], 'formie_submissions')) {
                $sql[] = $message[0];
            }
        }
        return compact('result', 'hydrated', 'scans', 'sql');
    } finally {
        Event::off(SubmissionQuery::class, SubmissionQuery::EVENT_AFTER_POPULATE_ELEMENT, $populate);
        Event::off(SubmissionQuery::class, SubmissionQuery::EVENT_BEFORE_PREPARE, $prepare);
        $db->enableLogging = $logging;
        $logger->flushInterval = $flushInterval;
    }
}

function seedReleaseRemovedHistory(Submission $template, int $count): void
{
    $db = Craft::$app->getDb();
    $element = (new Query())->from('{{%elements}}')->where(['id' => $template->id])->one();
    $site = (new Query())->from('{{%elements_sites}}')->where(['elementId' => $template->id])->one();
    $submission = (new Query())->from(Table::FORMIE_SUBMISSIONS)->where(['id' => $template->id])->one();
    unset($element['id'], $site['id']);
    $element['dateCreated'] = '2020-01-01 00:00:00';
    // Clone a real persisted row; avoid making fixture setup itself run the upload workflow.
    while ($count > 0) {
        $size = min($count, 200);
        $elements = $uids = [];
        for ($i = 0; $i < $size; $i++) {
            $element['uid'] = StringHelper::UUID();
            $uids[] = $element['uid'];
            $elements[] = array_values($element);
        }
        $db->createCommand()->batchInsert('{{%elements}}', array_keys($element), $elements)->execute();
        $ids = (new Query())->select('id')->from('{{%elements}}')->where(['uid' => $uids])->column();
        $sites = $submissions = [];
        foreach ($ids as $id) {
            $site['elementId'] = $id;
            $site['uid'] = StringHelper::UUID();
            $submission['id'] = $id;
            $submission['uid'] = StringHelper::UUID();
            $sites[] = array_values($site);
            $submissions[] = array_values($submission);
        }
        $db->createCommand()->batchInsert('{{%elements_sites}}', array_keys($site), $sites)->execute();
        $db->createCommand()->batchInsert(Table::FORMIE_SUBMISSIONS, array_keys($submission), $submissions)->execute();
        $count -= $size;
    }
}

it('keeps retained upload submission work bounded as history grows', function (int $olderRows, int $fileCount): void {
    $children = [['fields' => [['type' => FileUpload::class, 'handle' => 'document', 'label' => 'Document', 'restrictFiles' => false]]]];
    $form = formie()->form()->fileUploadField('document', ['restrictFiles' => false])
        ->groupField('group', ['rows' => $children])->repeaterField('rows', ['rows' => $children])
        ->settings(['disableCaptchas' => true])->create();
    $olderAsset = releaseRemovedAsset($this);
    $older = formie()->submission($form)->with(['document' => [$olderAsset->id]])->save();
    seedReleaseRemovedHistory($older, $olderRows);
    $uploads = Formie::$plugin->getFileUploads();
    $fresh = new Submission();
    $fresh->setForm($form);
    $assetIds = [];
    foreach (array_slice(['document', 'group.document', 'rows.0.document'], 0, $fileCount) as $path) {
        $asset = releaseRemovedAsset($this);
        $assetIds[] = (int)$asset->id;
        $field = FileUploadRetentionHelper::resolveFileUploadFieldForContentKey($form, $path);
        $uploads->trackSubmissionAsset($asset, (int)$form->id, null, $field->uid, $form, $path);
        if ($path === 'document') {
            $fresh->setFieldValueFromRequest('document', [$asset->id]);
        } elseif ($path === 'group.document') {
            $fresh->setFieldValueFromRequest('group', ['document' => [$asset->id]]);
        } else {
            $fresh->setFieldValueFromRequest('rows', [['document' => [$asset->id]]]);
        }
    }
    $measured = measureReleaseRemoved(fn() => runSubmissionCommand(submissionCommand(['form' => $form, 'submission' => $fresh])));
    expect($measured['result']->success)->toBeTrue()
        ->and($measured['scans'])->toBe(0)
        ->and($measured['hydrated'])->toBeLessThanOrEqual(3)
        ->and($measured['sql'])->not->toBeEmpty();
    foreach ($assetIds as $assetId) {
        $tracked = $uploads->getTrackedUploadByAssetId($assetId);
        expect($tracked['state'])->toBe('finalized')->and((int)$tracked['submissionId'])->toBe((int)$fresh->id);
    }
})->with([
    'one file, no cloned history' => [0, 1],
    'one file, 150 older rows' => [150, 1],
    'one file, 1000 older rows' => [1000, 1],
    'three nested files, no cloned history' => [0, 3],
    'three nested files, 150 older rows' => [150, 3],
    'three nested files, 1000 older rows' => [1000, 3],
]);

it('uses persisted references when releasing removed uploads', function (string $scope): void {
    $form = formie()->form()->fileUploadField('document', ['restrictFiles' => false])
        ->fileUploadField('otherDocument', ['restrictFiles' => false])->create();
    $asset = releaseRemovedAsset($this);
    $submission = formie()->submission($form)->with(['document' => [$asset->id]])->save();
    $uploads = Formie::$plugin->getFileUploads();
    $uploads->trackSubmissionAsset($asset, (int)$form->id, (int)$submission->id, $form->getFieldByHandle('document')->uid, $form, 'document');
    $uploads->bindPersisted($submission);
    if ($scope === 'shared') {
        formie()->submission($form)->with(['document' => [$asset->id]])->save();
    }
    if ($scope !== 'persisted') {
        $submission->setFieldValueFromRequest('document', []);
        if ($scope === 'other-field') {
            $submission->setFieldValueFromRequest('otherDocument', [$asset->id]);
        }
        expect(Craft::$app->getElements()->saveElement($submission))->toBeTrue();
        // A stale caller still carries a removed value; it must not keep the upload bound.
        $submission->setFieldValueFromRequest('document', [$asset->id]);
        $submission->setFieldValueFromRequest('otherDocument', []);
    } else {
        // Conversely, an unsaved clear must not release a persisted reference.
        $submission->setFieldValueFromRequest('document', []);
    }
    $measured = measureReleaseRemoved(fn() => $uploads->releaseRemoved($submission));
    $tracked = $uploads->getTrackedUploadByAssetId((int)$asset->id);
    $retained = $scope !== 'removed';
    expect($tracked['state'])->toBe($retained ? 'bound' : 'staged')
        ->and($tracked['submissionId'] === null ? null : (int)$tracked['submissionId'])->toBe($retained ? (int)$submission->id : null)
        ->and($measured['scans'])->toBe(0);
    if ($scope === 'removed') {
        expect((int)$tracked['expiresAt'])->toBeLessThanOrEqual(time());
    }
})->with(['removed', 'shared', 'other-field', 'persisted']);

it('does not load submissions without tracked uploads', function (): void {
    $form = formie()->form()->singleLineTextField('details')->create();
    $submission = formie()->submission($form)->with(['details' => 'No uploads'])->save();
    $measured = measureReleaseRemoved(fn() => Formie::$plugin->getFileUploads()->releaseRemoved($submission));
    expect($measured['hydrated'])->toBe(0)->and($measured['scans'])->toBe(0)->and($measured['sql'])->toBe([]);
});

it('retains large persisted upload content without sorting or scanning history', function (string $state): void {
    $db = Craft::$app->getDb();
    $sortBuffer = $db->getIsMysql() ? (int)$db->createCommand('SELECT @@session.sort_buffer_size')->queryScalar() : null;
    if ($sortBuffer !== null) {
        $db->createCommand('SET SESSION sort_buffer_size = 262144')->execute();
    }
    try {
        $form = formie()->form()->fileUploadField('document', ['restrictFiles' => false])->multiLineTextField('details')->create();
        $asset = releaseRemovedAsset($this);
        $submission = formie()->submission($form)->with(['document' => [$asset->id], 'details' => 'Large' . str_repeat(' ', 827000) . 'payload'])->save();
        $uploads = Formie::$plugin->getFileUploads();
        $uploads->trackSubmissionAsset($asset, (int)$form->id, (int)$submission->id, $form->getFieldByHandle('document')->uid, $form, 'document');
        $uploads->bindPersisted($submission);
        if ($state === 'trashed') {
            expect(Craft::$app->getElements()->deleteElement($submission))->toBeTrue();
        } elseif ($state !== 'complete') {
            $submission->{$state} = true;
            expect(Craft::$app->getElements()->saveElement($submission))->toBeTrue();
        }
        $content = (new Query())->select('content')->from(Table::FORMIE_SUBMISSIONS)->where(['id' => $submission->id])->scalar();
        expect(strlen((string)$content))->toBeGreaterThan(800000);
        $measured = measureReleaseRemoved(fn() => $uploads->releaseRemoved($submission));
        expect($measured['hydrated'])->toBe(1)->and($measured['scans'])->toBe(0)->and($measured['sql'])->not->toBeEmpty();
        foreach ($measured['sql'] as $sql) {
            expect(strtoupper($sql))->not->toContain('ORDER BY');
        }
        expect($uploads->getTrackedUploadByAssetId((int)$asset->id)['state'])->toBe('bound');
    } finally {
        if ($sortBuffer !== null) {
            $db->createCommand('SET SESSION sort_buffer_size = ' . $sortBuffer)->execute();
        }
    }
})->with(['complete', 'isIncomplete', 'isSpam', 'trashed']);
