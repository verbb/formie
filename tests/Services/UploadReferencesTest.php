<?php

declare(strict_types=1);

use craft\db\Query;
use craft\helpers\Db;
use Tests\Support\UploadTestHelper;
use verbb\formie\fields\FileUpload;
use verbb\formie\Formie;
use verbb\formie\helpers\Table;
use verbb\formie\helpers\UploadReferenceHelper;
use verbb\formie\records\Submission as SubmissionRecord;
use verbb\formie\services\UploadReferences;

beforeEach(function(): void {
    $this->uploadIndexTransaction = Craft::$app->getDb()->beginTransaction();
    $this->uploadIndexAssets = [];
    UploadTestHelper::ensureUploadVolume();
});

afterEach(function(): void {
    try {
        foreach ($this->uploadIndexAssets as $asset) {
            if ($asset->getVolume()->fileExists($asset->getPath())) {
                $asset->getVolume()->deleteFile($asset->getPath());
            }
        }
    } finally {
        $this->uploadIndexTransaction->rollBack();
        Formie::$plugin->getForms()->invalidateFormCaches();
        Formie::$plugin->getFields()->resetFieldRegistryCache();
    }
});

function uploadIndexAsset(object $test): \craft\elements\Asset
{
    $asset = UploadTestHelper::seedAsset('upload-index-' . uniqid() . '.txt', 'indexed file');
    $test->uploadIndexAssets[] = $asset;
    return $asset;
}

function uploadIndexPairs(int $id): array
{
    return array_map('intval', (new Query())->select('assetId')->from(Table::FORMIE_SUBMISSION_ASSETS)->where(['submissionId' => $id])->orderBy(['assetId' => SORT_ASC])->column());
}

it('indexes saved uploads and detects both hits and misses without loading submissions', function(): void {
    $form = formie()->form()->fileUploadField('document', ['restrictFiles' => false])->create();
    $asset = uploadIndexAsset($this);
    $submission = formie()->submission($form)->with(['document' => [$asset->id]])->save();
    expect(uploadIndexPairs((int)$submission->id))->toBe([(int)$asset->id]);
    $db = Craft::$app->getDb();
    $logger = Craft::getLogger();
    $logging = $db->enableLogging;
    $flushInterval = $logger->flushInterval;
    $db->enableLogging = true;
    $logger->flushInterval = 0;
    $start = count($logger->messages);
    try {
        expect(Formie::$plugin->getFileUploads()->isReferenced((int)$asset->id))->toBeTrue()
            ->and(Formie::$plugin->getFileUploads()->isReferenced(2147483000))->toBeFalse();
        foreach (array_slice($logger->messages, $start) as $message) {
            if ($message[2] === 'yii\\db\\Command::query' && is_string($message[0])) {
                expect($message[0])->not->toContain('content');
            }
        }
    } finally {
        $db->enableLogging = $logging;
        $logger->flushInterval = $flushInterval;
    }
});

it('updates direct records atomically and ignores stale no-op records', function(): void {
    $form = formie()->form()->fileUploadField('document', ['restrictFiles' => false])->create();
    $asset = uploadIndexAsset($this);
    $submission = formie()->submission($form)->with(['document' => [$asset->id]])->save();
    $stale = SubmissionRecord::findOne($submission->id);
    $record = SubmissionRecord::findOne($submission->id);
    $record->content = [];
    expect($record->save(false))->toBeTrue()->and(uploadIndexPairs((int)$submission->id))->toBe([]);
    expect($stale->save(false))->toBeTrue()->and(uploadIndexPairs((int)$submission->id))->toBe([]);
    $nested = Craft::$app->getDb()->beginTransaction();
    $record->content = [$form->getFieldByHandle('document')->uid => [$asset->id]];
    $record->save(false);
    expect(uploadIndexPairs((int)$submission->id))->toBe([(int)$asset->id]);
    $nested->rollBack();
    expect(uploadIndexPairs((int)$submission->id))->toBe([]);
});

it('protects unreadable uploads and removes exceptions after repair', function(): void {
    $form = formie()->form()->fileUploadField('document', ['restrictFiles' => false])->create();
    $asset = uploadIndexAsset($this);
    $submission = formie()->submission($form)->with(['document' => [$asset->id]])->save();
    $record = SubmissionRecord::findOne($submission->id);
    $record->content = [$form->getFieldByHandle('document')->uid => ['__formie_enc' => 2, 'k' => 'missing', 'c' => 'invalid']];
    $record->save(false);
    $references = Formie::$plugin->getUploadReferences();
    expect(uploadIndexPairs((int)$submission->id))->toBe([(int)$asset->id])
        ->and($references->referenceState(2147483000))->toBe(UploadReferences::UNKNOWN);
    $called = false;
    expect($references->withDeletionCheck(2147483000, function() use (&$called): bool { $called = true; return true; }))->toBeFalse()->and($called)->toBeFalse();
    $record->content = [];
    $record->save(false);
    expect($references->referenceState(2147483000))->toBe(UploadReferences::UNREFERENCED);
});

it('resumes bounded backfill and reconciles stale positives as well as missing pairs', function(): void {
    $form = formie()->form()->fileUploadField('document', ['restrictFiles' => false])->create();
    $asset = uploadIndexAsset($this);
    $first = formie()->submission($form)->with(['document' => [$asset->id]])->save();
    $second = formie()->submission($form)->with(['document' => [$asset->id]])->save();
    Db::delete(Table::FORMIE_SUBMISSION_ASSETS, ['submissionId' => $first->id]);
    Db::update(Table::FORMIE_SUBMISSIONS, ['content' => []], ['id' => $second->id]);
    $references = Formie::$plugin->getUploadReferences();
    $references->invalidate((int)$form->id);
    expect($references->referenceState(2147483000))->toBe(UploadReferences::UNKNOWN);
    expect($references->rebuildBatch((int)$form->id, 1))->toBeFalse();
    expect(uploadIndexPairs((int)$first->id))->toBe([(int)$asset->id]);
    expect($references->rebuildBatch((int)$form->id, 1))->toBeFalse();
    expect(uploadIndexPairs((int)$second->id))->toBe([]);
    expect($references->rebuildBatch((int)$form->id, 1))->toBeTrue();
    expect($references->verify((int)$form->id))->toBe(['checked' => 2, 'mismatched' => 0, 'unknown' => 0]);
    expect($references->referenceState(2147483000))->toBe(UploadReferences::UNREFERENCED);
});

it('extracts nested and scalar uploads without resolving assets or decoding unrelated siblings', function(): void {
    $children = [['fields' => [
        ['type' => FileUpload::class, 'handle' => 'document', 'label' => 'Document'],
        ['type' => \verbb\formie\fields\SingleLineText::class, 'handle' => 'text', 'label' => 'Text'],
    ]]];
    $form = formie()->form()->groupField('group', ['rows' => $children])->repeaterField('rows', ['rows' => $children])->create();
    $group = $form->getFieldByHandle('group');
    $repeater = $form->getFieldByHandle('rows');
    $groupFields = $group->getFields();
    $rowFields = $repeater->getFields();
    $locked = ['__formie_enc' => 2, 'k' => 'missing', 'c' => 'invalid'];
    $content = [
        $group->uid => [$groupFields[0]->uid => '123', $groupFields[1]->uid => $locked],
        $repeater->uid => [[$rowFields[0]->uid => [456]], [$rowFields[0]->uid => [123]]],
        'unknown' => [999],
    ];
    $schema = UploadReferenceHelper::schema($form);
    expect(UploadReferenceHelper::extract($content, $schema))->toBe(['ids' => [123, 456], 'unknown' => false]);
    expect(UploadReferenceHelper::extract($content, $schema, 'rows.0.document'))->toBe(['ids' => [123], 'unknown' => false]);
    $content[$group->uid] = $locked;
    expect(UploadReferenceHelper::extract($content, $schema)['unknown'])->toBeTrue();
});

it('keeps asset mutexes until the outer transaction ends', function(): void {
    $references = Formie::$plugin->getUploadReferences();
    $nested = Craft::$app->getDb()->beginTransaction();
    expect($references->acquireAsset(2147483001))->toBeTrue();
    $references->releaseAsset(2147483001);
    $nested->commit();
    expect(Craft::$app->getMutex()->isAcquired('formie.upload.2147483001'))->toBeTrue();
    $this->uploadIndexTransaction->rollBack();
    expect(Craft::$app->getMutex()->isAcquired('formie.upload.2147483001'))->toBeFalse();
    $this->uploadIndexTransaction = Craft::$app->getDb()->beginTransaction();
});

it('invalidates upload schema changes but leaves label-only edits ready', function(): void {
    $form = formie()->form()->fileUploadField('document', ['restrictFiles' => false])->create();
    $asset = uploadIndexAsset($this);
    formie()->submission($form)->with(['document' => [$asset->id]])->save();
    $state = fn() => (new Query())->from(Table::FORMIE_UPLOAD_REFERENCE_FORMS)->where(['formId' => $form->id])->one();
    $generation = (int)$state()['generation'];
    $field = $form->getFieldByHandle('document');
    $field->label = 'New document label';
    expect(Formie::$plugin->getFields()->saveField($field))->toBeTrue()
        ->and((int)$state()['generation'])->toBe($generation)->and($state()['state'])->toBe('ready');
    $field->handle = 'renamedDocument';
    expect(Formie::$plugin->getFields()->saveField($field))->toBeTrue()
        ->and((int)$state()['generation'])->toBeGreaterThan($generation)->and($state()['state'])->toBe('pending');
    expect(Formie::$plugin->getUploadReferences()->rebuildBatch((int)$form->id))->toBeTrue();
    expect(Formie::$plugin->getUploadReferences()->verify((int)$form->id)['mismatched'])->toBe(0);
});

it('reconciles again correctly after a nested savepoint rollback', function(): void {
    $form = formie()->form()->fileUploadField('document', ['restrictFiles' => false])->create();
    $first = uploadIndexAsset($this);
    $second = uploadIndexAsset($this);
    $submission = formie()->submission($form)->with(['document' => [$first->id]])->save();
    $nested = Craft::$app->getDb()->beginTransaction();
    $record = SubmissionRecord::findOne($submission->id);
    $record->content = [$form->getFieldByHandle('document')->uid => [$second->id]];
    $record->save(false);
    $nested->rollBack();
    $record = SubmissionRecord::findOne($submission->id);
    $record->content = [];
    $record->save(false);
    expect(uploadIndexPairs((int)$submission->id))->toBe([]);
});

it('rolls back an attachment to a deleted asset and retains unresolved historical values', function(): void {
    $form = formie()->form()->fileUploadField('document', ['restrictFiles' => false])->create();
    $submission = formie()->submission($form)->save();
    $uid = $form->getFieldByHandle('document')->uid;
    $record = SubmissionRecord::findOne($submission->id);
    $record->content = [$uid => [2147483000]];
    expect(fn() => $record->save(false))->toThrow(RuntimeException::class, 'no longer exists');
    expect(uploadIndexPairs((int)$submission->id))->toBe([]);
    // Legacy/imported unresolved IDs remain stored usage when historical content is rebuilt.
    Db::update(Table::FORMIE_SUBMISSIONS, ['content' => [$uid => [2147483000]]], ['id' => $submission->id]);
    Formie::$plugin->getUploadReferences()->reconcile((int)$submission->id);
    expect(uploadIndexPairs((int)$submission->id))->toBe([2147483000]);
});

it('protects relocated uploads hidden by unreadable or malformed former parents', function(): void {
    $schema = ['upload-uid' => ['kind' => 'upload', 'handle' => 'document', 'uploads' => true]];
    $unreadable = ['__formie_enc' => 2, 'k' => 'missing', 'c' => 'bad'];
    expect(UploadReferenceHelper::extract(['old-group-uid' => $unreadable], $schema))->toBe(['ids' => [], 'unknown' => true]);
    expect(UploadReferenceHelper::extract(['upload-uid' => ['__formie_enc' => 99, 'c' => 'bad']], $schema)['unknown'])->toBeTrue();
    $readable = \verbb\formie\content\FieldStorageCodec::encode(['upload-uid' => [123]], true);
    expect(UploadReferenceHelper::extract(['old-group-uid' => $readable], $schema))->toBe(['ids' => [123], 'unknown' => false]);
});

it('does not promote an upload using a conservative unknown reference', function(): void {
    $form = formie()->form()->fileUploadField('document', ['restrictFiles' => false])->create();
    $asset = uploadIndexAsset($this);
    $submission = formie()->submission($form)->with(['document' => [$asset->id]])->save();
    $uploads = Formie::$plugin->getFileUploads();
    $uploads->trackSubmissionAsset($asset, (int)$form->id, (int)$submission->id, $form->getFieldByHandle('document')->uid, $form, 'document');
    Db::update(Table::FORMIE_PENDING_UPLOADS, ['state' => 'bound', 'promotionState' => 'moving', 'promotionFolderId' => $asset->folderId], ['assetId' => $asset->id]);
    $record = SubmissionRecord::findOne($submission->id);
    $record->content = [$form->getFieldByHandle('document')->uid => ['__formie_enc' => 2, 'k' => 'missing', 'c' => 'bad']];
    $record->save(false);
    expect($uploads->isReferenced((int)$asset->id))->toBeTrue();
    expect(fn() => $uploads->recoverPromotions((int)$submission->id))->toThrow(RuntimeException::class, 'requires reconciliation');
});

it('reports an out-of-range stored asset ID instead of failing backfill', function(): void {
    $form = formie()->form()->fileUploadField('document', ['restrictFiles' => false])->create();
    $submission = formie()->submission($form)->save();
    Db::update(Table::FORMIE_SUBMISSIONS, ['content' => [$form->getFieldByHandle('document')->uid => ['999999999999999999999999']]], ['id' => $submission->id]);
    $references = Formie::$plugin->getUploadReferences();
    $references->invalidate((int)$form->id);
    expect($references->rebuildBatch((int)$form->id))->toBeTrue()
        ->and($references->referenceState(2147483000))->toBe(UploadReferences::UNKNOWN);
});


it('treats persisted missing-field placeholders as unknown upload coverage', function(): void {
    $schema = UploadReferenceHelper::schemaFromLayouts(1, [1 => [[
        'uid' => 'missing-field',
        'type' => \verbb\formie\fields\MissingField::class,
        'handle' => 'missing',
        'settings' => [],
    ]]]);
    expect(UploadReferenceHelper::extract(['missing-field' => [123]], $schema))->toBe(['ids' => [], 'unknown' => true]);
});
