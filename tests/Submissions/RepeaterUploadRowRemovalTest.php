<?php

declare(strict_types=1);

use Tests\Support\UploadTestHelper;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\fields\FileUpload;
use verbb\formie\helpers\UploadAccess;
use verbb\formie\models\SubmissionUploadClaims;

it('preserves a staged repeater upload after earlier rows are removed', function (int $removedRow) {
    WebRequestTestHelper::withWebRequestContext(function () use ($removedRow) {
        UploadTestHelper::ensureUploadVolume();
        $form = formie()->form()->repeaterField('guests', ['rows' => [['fields' => [[
            'type' => FileUpload::class, 'handle' => 'document', 'label' => 'Document', 'restrictFiles' => false,
        ]]]]])->create();
        $field = $form->getFieldByHandle('guests')->getFields($removedRow)[0];
        $asset = UploadTestHelper::seedStagedAsset($form, $field->uid, 'guests.' . $removedRow . '.document', 'row-removal.txt', 'retained');
        $uploads = Formie::$plugin->getFileUploads();
        $row = $uploads->getTrackedUploadByAssetId((int)$asset->id);
        $reference = [[
            'uploadUid' => $row['uid'],
            'attachToken' => UploadAccess::issueToken($asset->id, $form->id, $field->uid, purpose: 'attach'),
        ]];
        $submission = new Submission();
        $submission->setForm($form);
        $submission->getContentState()->uploadClaims = new SubmissionUploadClaims();
        $submission->setFieldValueFromRequest('guests', [$removedRow => ['document' => $reference]]);
        expect($submission->getContentState()->requestFieldPaths)->toBe(['guests.0.document' => 'guests.' . $removedRow . '.document']);
        expect($submission->getFieldValue('guests.0.document')->ids())->toBe([(int)$asset->id]);
        $submission->isIncomplete = true;
        $command = submissionCommand(['form' => $form, 'submission' => $submission,
            'authority' => new \verbb\formie\models\SubmissionAuthority(\verbb\formie\enums\SubmissionAuthorityType::VISITOR, (int)$form->id, null, 'row-removal'),
        ]);
        expect($uploads->bindAccepted($command))->toBe([(int)$asset->id]);
        expect(Craft::$app->getElements()->saveElement($submission, false))->toBeTrue();
        $uploads->bindPersisted($submission);
        expect($uploads->getTrackedUploadByAssetId((int)$asset->id)['contentKey'])->toBe('guests.0.document');

        foreach ([$removedRow, 0] as $postedRow) {
            $saved = Submission::find()->id($submission->id)->isIncomplete(null)->status(null)->one();
            $saved->setForm($form);
            $saved->getContentState()->uploadClaims = new SubmissionUploadClaims();
            $saved->setFieldValueFromRequest('guests', [$postedRow => ['document' => $reference]]);
            expect($saved->getFieldValue('guests.0.document')->ids())->toBe([(int)$asset->id]);
            expect($uploads->bindAccepted(submissionCommand([
                'form' => $form, 'submission' => $saved,
                'authority' => new \verbb\formie\models\SubmissionAuthority(\verbb\formie\enums\SubmissionAuthorityType::VISITOR, (int)$form->id, $saved->id, 'row-removal'),
            ])))->toBe([]);
        }
    });
})->with([1, 3]);

it('stages new file bytes from sparse repeater rows and returns reusable upload capabilities', function () {
    WebRequestTestHelper::withWebRequestContext(function () {
        UploadTestHelper::ensureUploadVolume();
        $form = formie()->form()->repeaterField('guests', ['rows' => [['fields' => [[
            'type' => FileUpload::class, 'handle' => 'document', 'label' => 'Document', 'restrictFiles' => false,
        ]]]]])->create();
        $submission = new Submission();
        $submission->setForm($form);
        $submission->setFieldValueFromRequest('guests', [2 => ['document' => [[
            'filename' => 'sparse-bytes.txt', 'fileData' => 'data:text/plain;base64,' . base64_encode('Sparse row bytes'),
        ]]]]);
        $uploads = Formie::$plugin->getFileUploads();
        expect($uploads->stageAccepted(submissionCommand(['form' => $form, 'submission' => $submission])))->toBeTrue();
        $assets = $submission->getFieldValue('guests.0.document')->all();
        expect($assets)->toHaveCount(1)->and(file_get_contents($assets[0]->getCopyOfFile()))->toBe('Sparse row bytes');
        $events = $form->getSubmitData();
        $event = array_values(array_filter($events, fn($event) => ($event['event'] ?? '') === 'formie:file-upload:uploaded'))[0];
        $reference = $event['data']['uploadedAssets'][0];
        expect($reference['assetId'])->toBe((int)$assets[0]->id)
            ->and($reference['attachToken'])->not->toBeNull()
            ->and($reference['viewToken'])->not->toBeNull();
        $retry = new Submission();
        $retry->setForm($form);
        $retry->getContentState()->uploadClaims = new SubmissionUploadClaims();
        $retry->setFieldValueFromRequest('guests', [2 => ['document' => [[
            'uploadUid' => $reference['uploadUid'], 'attachToken' => $reference['attachToken'],
        ]]]]);
        expect($uploads->bindAccepted(submissionCommand([
            'form' => $form, 'submission' => $retry,
            'authority' => new \verbb\formie\models\SubmissionAuthority(\verbb\formie\enums\SubmissionAuthorityType::VISITOR, (int)$form->id, null, 'retry'),
        ])))->toBe([(int)$assets[0]->id]);
    });
});

it('renders retained upload envelopes with matching identity and capability indexes', function (bool $nested) {
    WebRequestTestHelper::withWebRequestContext(function () use ($nested) {
        UploadTestHelper::ensureUploadVolume();
        $builder = formie()->form();
        $form = $nested
            ? $builder->repeaterField('guests', ['rows' => [['fields' => [[
                'type' => FileUpload::class, 'handle' => 'document', 'label' => 'Document', 'restrictFiles' => false,
            ]]]]])->create()
            : $builder->fileUploadField('document', ['restrictFiles' => false])->create();
        $field = $nested ? $form->getFieldByHandle('guests')->getFields(0)[0] : $form->getFieldByHandle('document');
        $path = $nested ? 'guests.0.document' : 'document';
        $asset = UploadTestHelper::seedStagedAsset($form, $field->uid, $path, 'render-retained.txt', 'retained');
        $submission = new Submission();
        $submission->setForm($form);
        $submission->setFieldValue($nested ? 'guests' : 'document', $nested ? [['document' => [$asset->id]]] : [$asset->id]);
        $form->setCurrentSubmission($submission);
        $html = (string)Formie::$plugin->getRendering()->renderField($form, $nested ? 'guests' : 'document');
        $document = new DOMDocument();
        @$document->loadHTML($html);
        $inputs = (new DOMXPath($document))->query('//input[@data-formie-file-upload-entry]');
        $query = [];
        foreach ($inputs as $input) {
            $query[] = urlencode($input->getAttribute('name')) . '=' . urlencode($input->getAttribute('value'));
        }
        parse_str(implode('&', $query), $posted);
        $value = $nested ? $posted['fields']['guests'][0]['document'] : $posted['fields']['document'];
        expect($value)->toHaveCount(1)
            ->and(array_keys($value[0]))->toBe(['uploadUid', 'attachToken']);
    });
})->with([false, true]);
