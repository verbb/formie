<?php

use craft\db\Query;
use Tests\Support\UploadTestHelper;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\elements\Submission;
use verbb\formie\Formie;

it('stages uploads without creating destination folders before the submission is saved', function (bool $existing): void {
    WebRequestTestHelper::withWebRequestContext(function () use ($existing): void {
        $volume = UploadTestHelper::ensureUploadVolume();
        $reference = 'a' . bin2hex(random_bytes(5));
        $form = formie()->form()->singleLineTextField('eventTitle', ['reference' => $reference])->fileUploadField('document', [
            'restrictFiles' => false,
            'uploadLocationSubpath' => 'activities-' . uniqid() . '/{submission:id}-{field:' . $reference . '}',
        ])->create();
        $submission = new Submission();
        $submission->setForm($form);
        $submission->setFieldValue('eventTitle', 'My event');
        $field = $form->getFieldByHandle('document');

        if ($existing) {
            $asset = UploadTestHelper::seedStagedAsset($form, $field->uid, 'document', 'event.txt', 'Event upload');
            $submission->setFieldValue('document', [$asset->id]);
        } else {
            $submission->setFieldValueFromRequest('document', [[
                'filename' => 'event.txt',
                'fileData' => 'data:text/plain;base64,' . base64_encode('Event upload'),
            ]]);
        }

        $folders = (new Query())->from('{{%volumefolders}}')->where(['volumeId' => $volume->id]);
        $before = $folders->count();
        $uploads = Formie::$plugin->getFileUploads();

        expect($uploads->stageAccepted(submissionCommand(['form' => $form, 'submission' => $submission])))->toBeTrue();
        $assets = $submission->getFieldValue('document')->all();

        expect($submission->id)->toBeNull()
            ->and($assets)->toHaveCount(1)
            ->and($assets[0]->folderId)->toBe($uploads->getStagingFolder()->id)
            ->and(file_get_contents($assets[0]->getCopyOfFile()))->toBe('Event upload')
            ->and($folders->count())->toBe($before);
    });
})->with(['new file bytes' => false, 'existing staged asset' => true]);

it('creates only the final upload folder and retains the uploaded file on completion', function (string $subpath, string $expected): void {
    WebRequestTestHelper::withWebRequestContext(function () use ($subpath, $expected): void {
        $volume = UploadTestHelper::ensureUploadVolume();
        $reference = 'a' . bin2hex(random_bytes(5));
        $prefix = 'activities-' . uniqid();
        $form = formie()->form()->singleLineTextField('eventTitle', ['reference' => $reference])->fileUploadField('document', [
            'restrictFiles' => false,
            'uploadLocationSubpath' => $prefix . '/' . str_replace('REF', $reference, $subpath),
        ])->settings(['disableCaptchas' => true])->create();
        $submission = new Submission();
        $submission->setForm($form);
        $submission->setFieldValue('eventTitle', 'My event');
        $submission->setFieldValueFromRequest('document', [[
            'filename' => 'event.txt',
            'fileData' => 'data:text/plain;base64,' . base64_encode('Event upload'),
        ]]);

        expect(runSubmissionCommand(submissionCommand(['form' => $form, 'submission' => $submission]))->success)->toBeTrue();
        $saved = Submission::find()->id($submission->id)->one();
        $assets = $saved->getFieldValue('document')->all();
        $path = $prefix . '/' . str_replace('ID', (string)$saved->id, $expected) . '/';
        $descendants = (new Query())->select('path')->from('{{%volumefolders}}')
            ->where(['volumeId' => $volume->id])->andWhere(['like', 'path', $prefix . '/%', false])->column();

        expect($assets)->toHaveCount(1)
            ->and(Craft::$app->getAssets()->getFolderById($assets[0]->folderId)->path)->toBe($path)
            ->and(file_get_contents($assets[0]->getCopyOfFile()))->toBe('Event upload')
            ->and(Formie::$plugin->getFileUploads()->getTrackedUploadByAssetId((int)$assets[0]->id)['state'])->toBe('finalized')
            ->and($descendants)->toEqualCanonicalizing([$prefix . '/', $path]);
    });
})->with([
    'submission ID with title' => ['{submission:id}-{field:REF}', 'ID-My-event'],
    'submission ID alone' => ['{submission:id}', 'ID'],
    'static folder' => ['fixed', 'fixed'],
]);
