<?php

use Tests\Support\UploadTestHelper;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\enums\SubmissionOperation;
use verbb\formie\helpers\Table;
use verbb\formie\helpers\UploadAccess;
use craft\db\Query;

it('rejects copied IDs, foreign fields, expired uploads and another browser on final binding', function (string $attack) {
    WebRequestTestHelper::withWebRequestContext(function () use ($attack) {
        $volume = UploadTestHelper::ensureUploadVolume();
        $form = formie()->form()->fileUploadField('document', ['restrictFiles' => false])->fileUploadField('other', ['restrictFiles' => false])->create();
        $field = $form->getFieldByHandle('document');
        $asset = UploadTestHelper::seedAsset('ownership-' . uniqid() . '.txt', 'owned', $volume);
        $service = Formie::$plugin->getFileUploads();
        if ($attack !== 'bare') {
            $service->trackSubmissionAsset($asset, (int)$form->id, null, $attack === 'field' ? $form->getFieldByHandle('other')->uid : $field->uid, $form, $attack === 'field' ? 'other' : 'document');
        }
        if ($attack === 'expired') {
            Craft::$app->getDb()->createCommand()->update(Table::FORMIE_PENDING_UPLOADS, ['expiresAt' => time() - 1], ['assetId' => $asset->id])->execute();
        }
        if ($attack === 'browser') {
            Craft::$app->getSession()->set('formie:authority', 'a-different-browser');
        }
        $submission = new Submission();
        $submission->setForm($form);
        $submission->setFieldValue('document', [$asset->id]);
        expect(fn() => $service->bindAccepted(submissionCommand(['form' => $form, 'submission' => $submission])))
            ->toThrow(\yii\web\ForbiddenHttpException::class);
        expect($submission->id)->toBeNull();
    });
})->with(['bare', 'field', 'expired', 'browser']);

it('finalizes only accepted owned assets and lets authorized revisions retain them', function () {
    WebRequestTestHelper::withWebRequestContext(function () {
        $volume = UploadTestHelper::ensureUploadVolume();
        $form = formie()->form()->fileUploadField('document', ['restrictFiles' => false])->settings(['disableCaptchas' => true])->create();
        $field = $form->getFieldByHandle('document');
        $service = Formie::$plugin->getFileUploads();
        $asset = UploadTestHelper::seedAsset('accepted-' . uniqid() . '.txt', 'accepted', $volume);
        $unused = UploadTestHelper::seedAsset('unused-' . uniqid() . '.txt', 'unused', $volume);
        foreach ([$asset, $unused] as $file) {
            $service->trackSubmissionAsset($file, (int)$form->id, null, $field->uid, $form, 'document');
        }
        $submission = new Submission();
        $submission->setForm($form);
        $submission->setFieldValue('document', [$asset->id]);
        $result = runSubmissionCommand(submissionCommand(['form' => $form, 'submission' => $submission]));
        expect($result->success)->toBeTrue()
            ->and($service->getTrackedUploadByAssetId($asset->id)['state'])->toBe('finalized')
            ->and($service->getTrackedUploadByAssetId($unused->id)['state'])->toBe('staged');
        Craft::$app->getSession()->set('formie:authority', 'authorized-new-editor');
        $revision = runSubmissionCommand(submissionCommand(['form' => $form, 'submission' => $submission, 'operation' => SubmissionOperation::REVISE]));
        expect($revision->success)->toBeTrue()->and($submission->getFieldValue('document')->ids())->toBe([$asset->id]);
        Craft::$app->getDb()->createCommand()->update(Table::FORMIE_PENDING_UPLOADS, ['expiresAt' => time() - 1], ['assetId' => [$asset->id, $unused->id]])->execute();
        $service->purgeStalePendingUploads();
        expect(\craft\elements\Asset::find()->id($asset->id)->status(null)->one())->not->toBeNull()
            ->and(\craft\elements\Asset::find()->id($unused->id)->status(null)->one())->toBeNull();
    });
});

it('stores only purpose-bound upload capability hashes', function () {
    UploadTestHelper::ensureUploadVolume();
    $form = formie()->form()->fileUploadField('document', ['restrictFiles' => false])->create();
    $field = $form->getFieldByHandle('document');
    $asset = UploadTestHelper::seedAsset('capability-' . uniqid() . '.txt', 'capability');
    Formie::$plugin->getFileUploads()->trackSubmissionAsset($asset, (int)$form->id, null, $field->uid, $form, 'document');
    $view = UploadAccess::issueToken($asset->id, $form->id, $field->uid);
    $attach = UploadAccess::issueToken($asset->id, $form->id, $field->uid, purpose: 'attach');
    $delete = UploadAccess::issueToken($asset->id, $form->id, $field->uid, purpose: 'delete');
    $row = Formie::$plugin->getFileUploads()->getTrackedUploadByAssetId($asset->id);
    foreach ([$view, $attach, $delete] as $token) {
        expect(json_encode($row))->not->toContain(explode('.', $token, 2)[1]);
    }
    expect(UploadAccess::matches($asset->id, $form->id, $field->uid, $view))->toBeTrue()
        ->and(UploadAccess::matches($asset->id, $form->id, $field->uid, $view, 'delete'))->toBeFalse()
        ->and(UploadAccess::matches($asset->id, $form->id, $field->uid, $delete, 'delete'))->toBeTrue()
        ->and(UploadAccess::matches($asset->id, $form->id, $field->uid, $attach, 'attach'))->toBeTrue();
});

it('retains promotion intent after provider failure and resumes without duplicate moves', function () {
    $volume = UploadTestHelper::ensureUploadVolume();
    $form = formie()->form()->fileUploadField('document', ['restrictFiles' => false])->create();
    $asset = UploadTestHelper::seedAsset('recovery-' . uniqid() . '.txt', 'recover', $volume);
    $submission = formie()->submission($form)->with(['document' => [$asset->id]])->save();
    $service = Formie::$plugin->getFileUploads();
    $service->trackSubmissionAsset($asset, (int)$form->id, (int)$submission->id, $form->getFieldByHandle('document')->uid, $form, 'document');
    $service->bindPersisted($submission);
    $assets = Craft::$app->getAssets();
    $folder = $assets->ensureFolderByFullPathAndVolume('recovery-' . uniqid(), $volume);
    $failing = new class extends \craft\services\Assets {
        public function moveAsset(\craft\elements\Asset $asset, \craft\models\VolumeFolder $folder, string $filename = ''): bool {
            throw new RuntimeException('Controlled provider failure');
        }
    };
    Craft::$app->set('assets', $failing);
    try {
        expect(fn() => $service->promote($asset, $folder))->toThrow(RuntimeException::class, 'Controlled provider failure');
    } finally {
        Craft::$app->set('assets', $assets);
    }
    $row = $service->getTrackedUploadByAssetId($asset->id);
    expect($row['promotionState'])->toBe('moving')->and($row['failureCode'])->toBe('promotionFailed');
    $service->recoverPromotions((int)$submission->id);
    $service->recoverPromotions((int)$submission->id);
    expect($service->getTrackedUploadByAssetId($asset->id)['promotionState'])->toBe('moved')
        ->and(\craft\elements\Asset::find()->id($asset->id)->status(null)->one()->folderId)->toBe($folder->id);
});

it('repairs a physical move interrupted before asset metadata commits', function () {
    $volume = UploadTestHelper::ensureUploadVolume();
    $form = formie()->form()->fileUploadField('document', ['restrictFiles' => false])->create();
    $asset = UploadTestHelper::seedAsset('physical-recovery-' . uniqid() . '.txt', 'recover physical move', $volume);
    $submission = formie()->submission($form)->with(['document' => [$asset->id]])->save();
    $service = Formie::$plugin->getFileUploads();
    $service->trackSubmissionAsset($asset, (int)$form->id, (int)$submission->id, $form->getFieldByHandle('document')->uid, $form, 'document');
    $service->bindPersisted($submission);
    $assets = Craft::$app->getAssets();
    $folder = $assets->ensureFolderByFullPathAndVolume('physical-recovery-' . uniqid(), $volume);
    $failing = new class extends \craft\services\Assets {
        public function moveAsset(\craft\elements\Asset $asset, \craft\models\VolumeFolder $folder, string $filename = ''): bool {
            $asset->getVolume()->renameFile($asset->getPath(), $folder->path . ($filename ?: $asset->filename));
            throw new RuntimeException('Interrupted after provider move');
        }
    };
    Craft::$app->set('assets', $failing);
    try {
        expect(fn() => $service->promote($asset, $folder))->toThrow(RuntimeException::class, 'Interrupted after provider move');
    } finally {
        Craft::$app->set('assets', $assets);
    }
    expect($volume->fileExists($folder->path . $asset->filename))->toBeTrue();
    $service->recoverPromotions((int)$submission->id);
    $service->recoverPromotions((int)$submission->id);
    $recovered = \craft\elements\Asset::find()->id($asset->id)->status(null)->one();
    expect($recovered->folderId)->toBe($folder->id)
        ->and(file_get_contents($recovered->getCopyOfFile()))->toBe('recover physical move')
        ->and($service->getTrackedUploadByAssetId($asset->id)['promotionState'])->toBe('moved');
});

it('records the exact collision filename that Craft preserves during promotion', function (string $uid, bool $longName) {
    $volume = UploadTestHelper::ensureUploadVolume();
    $form = formie()->form()->fileUploadField('document', ['restrictFiles' => false])->create();
    $asset = UploadTestHelper::seedAsset('collision-source-' . uniqid() . '.txt', 'owned collision content', $volume);
    $submission = formie()->submission($form)->with(['document' => [$asset->id]])->save();
    $uploads = Formie::$plugin->getFileUploads();
    $uploads->trackSubmissionAsset($asset, $form->id, $submission->id, $form->getFieldByHandle('document')->uid, $form, 'document');
    $uploads->bindPersisted($submission);
    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_PENDING_UPLOADS, ['uid' => $uid], ['assetId' => $asset->id])->execute();
    $folder = Craft::$app->getAssets()->ensureFolderByFullPathAndVolume('collision-' . uniqid(), $volume);
    $filename = ($longName ? str_repeat('a', 251) : 'collision') . '.txt';
    $stream = fopen('php://temp', 'r+');
    fwrite($stream, 'existing destination');
    rewind($stream);
    $volume->writeFileFromStream($folder->path . $filename, $stream);
    fclose($stream);

    $uploads->promote($asset, $folder, $filename);
    $uploads->recoverPromotions((int)$submission->id);

    $row = $uploads->getTrackedUploadByAssetId($asset->id);
    $saved = \craft\elements\Asset::find()->id($asset->id)->status(null)->one();
    $existing = $volume->getFileStream($folder->path . $filename);
    $existingContent = stream_get_contents($existing);
    fclose($existing);
    expect($row['promotionState'])->toBe('moved')
        ->and($row['promotionFilename'])->toBe($saved->filename)
        ->and($saved->filename)->toEndWith('_' . substr(hash('sha256', $uid), 0, 12) . '.txt')
        ->and(strlen($saved->filename))->toBeLessThanOrEqual(255)
        ->and(file_get_contents($saved->getCopyOfFile()))->toBe('owned collision content')
        ->and($existingContent)->toBe('existing destination');
})->with([['abcdefghijk_', false], ['abcdefghijk-', false], ['long-file-uid', true]]);

it('rejects arbitrary final asset IDs through every submission adapter', function (string $transport) {
    $volume = UploadTestHelper::ensureUploadVolume();
    $form = formie()->form()->fileUploadField('document', ['restrictFiles' => false])->settings(['disableCaptchas' => true])->create();
    $asset = UploadTestHelper::seedAsset('foreign-final-' . uniqid() . '.txt', 'foreign', $volume);
    WebRequestTestHelper::withWebRequestContext(function ($request) use ($transport, $form, $asset) {
        $token = $form->getRequestToken();
        if (in_array($transport, ['html', 'ajax'], true)) {
            $request->setBodyParams(['formieHoneypot' => '', 'formStartedAt' => (string)((int)(microtime(true) * 1000) - 60000), 'handle' => $form->handle, 'requestToken' => $token, 'fields' => ['document' => [$asset->id]]]);
            $result = Formie::$plugin->getSubmissionRequests()->executeManaged(new \verbb\formie\models\ManagedSubmissionRequest([
                'handle' => $form->handle, 'requestToken' => $token, 'expectedVersion' => 0,
            ]), \verbb\formie\enums\SubmissionAuthorityType::VISITOR);
            expect($result->response->outcome->type->value)->toBe('validationFailed');
        } else {
            $input = ['handle' => $form->handle, 'session' => ['version' => 0, 'tokens' => ['request' => $token, 'csrf' => ['name' => $request->csrfParam, 'value' => $request->getCsrfToken()]]], 'values' => ['document' => [$asset->id]]];
            if ($transport === 'graphql') {
                $gql = Craft::$app->getGql();
                try { $previous = $gql->getActiveSchema(); } catch (\craft\errors\GqlException) { $previous = null; }
                $gql->setActiveSchema(new \craft\models\GqlSchema(['name' => 'Upload parity', 'scope' => ['formieForms.' . $form->uid . ':read', 'formieSubmissions.' . $form->uid . ':create']]));
                try {
                    $result = \verbb\formie\gql\resolvers\ClientFormResolver::submitForm(null, ['input' => $input]);
                } finally { $gql->setActiveSchema($previous); }
            } else {
                $result = runClientSubmission(new \verbb\formie\client\models\SubmitRequest($input))->toArrayRecursive();
            }
            expect($result['outcome'])->toBe('validationFailed');
        }
        expect((int)Submission::find()->formId($form->id)->isIncomplete(null)->isSpam(null)->status(null)->count())->toBe(0);
    }, ['method' => 'POST', 'headers' => ['Accept' => $transport === 'html' ? 'text/html' : 'application/json']]);
})->with(['html', 'ajax', 'rest', 'graphql']);

it('accepts the same structured staged-upload reference through every submission adapter', function (string $transport) {
    WebRequestTestHelper::withWebRequestContext(function ($request) use ($transport) {
        $volume = UploadTestHelper::ensureUploadVolume();
        $form = formie()->form()->fileUploadField('document', ['restrictFiles' => false])->settings(['disableCaptchas' => true])->create();
        $field = $form->getFieldByHandle('document');
        $uploads = Formie::$plugin->getFileUploads();
        $asset = UploadTestHelper::seedStagedAsset($form, $field->uid, 'document', 'structured-' . $transport . '-' . uniqid() . '.txt', 'structured');
        $tracked = $uploads->getTrackedUploadByAssetId((int)$asset->id);
        $reference = [[
            'uploadUid' => $tracked['uid'],
            'attachToken' => UploadAccess::issueToken((int)$asset->id, (int)$form->id, $field->uid, purpose: 'attach'),
        ]];
        $session = Formie::$plugin->getClientSessionService()->issueInitialSession($form)->toArrayRecursive();

        if (in_array($transport, ['html', 'ajax'], true)) {
            $request->setBodyParams([
                'formieHoneypot' => '',
                'formStartedAt' => (string)((int)(microtime(true) * 1000) - 60000),
                'formieUploadPayloadVersion' => 4,
                'handle' => $form->handle,
                'requestToken' => $session['tokens']['request'],
                'expectedVersion' => 0,
                'fields' => ['document' => $reference],
            ]);
            $execution = Formie::$plugin->getSubmissionRequests()->executeManaged(new \verbb\formie\models\ManagedSubmissionRequest([
                'handle' => $form->handle,
                'requestToken' => $session['tokens']['request'],
                'expectedVersion' => 0,
                'uploadPayloadVersion' => 4,
            ]), \verbb\formie\enums\SubmissionAuthorityType::VISITOR);
            $success = $execution->response->success;
        } else {
            $input = [
                'handle' => $form->handle,
                'session' => $session,
                'values' => ['document' => $reference],
            ];

            if ($transport === 'graphql') {
                $gql = Craft::$app->getGql();
                try { $previous = $gql->getActiveSchema(); } catch (\craft\errors\GqlException) { $previous = null; }
                $gql->setActiveSchema(new \craft\models\GqlSchema(['name' => 'Structured upload parity', 'scope' => ['formieForms.' . $form->uid . ':read', 'formieSubmissions.' . $form->uid . ':create']]));
                try {
                    $result = \verbb\formie\gql\resolvers\ClientFormResolver::submitForm(null, ['input' => $input]);
                } finally { $gql->setActiveSchema($previous); }
            } else {
                $result = runClientSubmission(new \verbb\formie\client\models\SubmitRequest($input))->toArrayRecursive();
            }
            $success = $result['success'];
        }

        $saved = Submission::find()->formId($form->id)->isIncomplete(false)->isSpam(false)->status(null)->one();
        expect($success)->toBeTrue()
            ->and($saved)->not->toBeNull()
            ->and($saved->getFieldValue('document')->ids())->toBe([(int)$asset->id])
            ->and($uploads->getTrackedUploadByAssetId((int)$asset->id)['state'])->toBe('finalized');
    }, ['method' => 'POST', 'headers' => ['Accept' => $transport === 'html' ? 'text/html' : 'application/json']]);
})->with(['html', 'ajax', 'rest', 'graphql']);

it('accepts an exact bound upload capability when an incomplete submission revisits a page', function () {
    WebRequestTestHelper::withWebRequestContext(function () {
        $volume = UploadTestHelper::ensureUploadVolume();
        $form = formie()->form()->fileUploadField('document', ['restrictFiles' => false])->create();
        $field = $form->getFieldByHandle('document');
        $submission = formie()->submission($form)->with([])->save();
        $submission->isIncomplete = true;
        expect(Craft::$app->getElements()->saveElement($submission))->toBeTrue();

        $progress = Formie::$plugin->getSubmissionProgress()->upsertProgressState($form, $submission);
        $asset = UploadTestHelper::seedStagedAsset(
            $form,
            $field->uid,
            'document',
            'bound-revisit-' . uniqid() . '.txt',
            'bound revisit',
            (int)$submission->id,
        );
        $uploads = Formie::$plugin->getFileUploads();
        $tracked = $uploads->getTrackedUploadByAssetId((int)$asset->id);
        $submission->setFieldValue('document', [$asset->id]);
        expect(Craft::$app->getElements()->saveElement($submission))->toBeTrue();
        Craft::$app->getDb()->createCommand()->update(Table::FORMIE_PENDING_UPLOADS, [
            'state' => 'bound',
        ], ['id' => $tracked['id']])->execute();
        $destination = Craft::$app->getAssets()->ensureFolderByFullPathAndVolume('bound-revisit-' . uniqid(), $volume);
        $uploads->promote($asset, $destination);

        $submission = Submission::find()->id($submission->id)->siteId($form->siteId)->isIncomplete(null)->status(null)->one();
        $submission->setForm($form);
        $reference = $field->getSubmissionUploadReference($asset, $submission);

        $submission->getContentState()->uploadClaims = new \verbb\formie\models\SubmissionUploadClaims();
        $value = $field->normalizeValueFromRequest([[
            'uploadUid' => $reference['uploadUid'],
            'attachToken' => $reference['attachToken'],
        ]], $submission);
        $submission->setFieldValue('document', $value);

        expect($value->ids())->toBe([(int)$asset->id])
            ->and((int)$tracked['progressId'])->toBe((int)$progress->id)
            ->and($reference['uploadUid'])->toBe($tracked['uid'])
            ->and($reference['attachToken'])->not->toBeNull()
            ->and((int)$asset->folderId)->toBe((int)$destination->id)
            ->and($uploads->bindAccepted(submissionCommand(['form' => $form, 'submission' => $submission])))->toBe([])
            ->and($uploads->getTrackedUploadByAssetId((int)$asset->id)['state'])->toBe('bound');
    });
});

it('binds upload capabilities to their purpose and exact field context', function (string $attack) {
    WebRequestTestHelper::withWebRequestContext(function () use ($attack) {
        $volume = UploadTestHelper::ensureUploadVolume();
        $form = formie()->form()->fileUploadField('document', ['restrictFiles' => false])->fileUploadField('other', ['restrictFiles' => false])->create();
        $field = $form->getFieldByHandle('document');
        $asset = UploadTestHelper::seedAsset('scoped-' . $attack . '-' . uniqid() . '.txt', 'scoped', $volume);
        $uploads = Formie::$plugin->getFileUploads();
        $owner = $attack === 'submission' ? formie()->submission($form)->save() : null;
        $uploads->trackSubmissionAsset($asset, (int)$form->id, $owner?->id, $field->uid, $form, 'document');
        $tracked = $uploads->getTrackedUploadByAssetId((int)$asset->id);
        $token = UploadAccess::issueToken((int)$asset->id, (int)$form->id, $field->uid, purpose: $attack === 'purpose' ? 'view' : 'attach');

        if ($attack === 'browser') {
            Craft::$app->getSession()->set('formie:authority', 'different-upload-browser');
        }

        $submission = new Submission();
        $submission->setForm($form);
        $submission->getContentState()->uploadClaims = new \verbb\formie\models\SubmissionUploadClaims();
        $target = $attack === 'field' ? $form->getFieldByHandle('other') : $field;

        expect(fn() => $target->normalizeValueFromRequest([[
            'uploadUid' => $tracked['uid'],
            'attachToken' => $token,
        ]], $submission))->toThrow(\yii\web\ForbiddenHttpException::class);
    });
})->with(['purpose', 'field', 'submission', 'browser']);

it('requires the client bootstrap capability before accepting a cross-origin upload body', function (bool $validToken) {
    WebRequestTestHelper::withWebRequestContext(function ($request) use ($validToken) {
        UploadTestHelper::ensureUploadVolume();
        $form = formie()->form()->fileUploadField('document', ['restrictFiles' => false])->create();
        $request->setBodyParams([
            'handle' => $form->handle,
            'fieldHandle' => 'document',
            'uploadCreateToken' => $validToken ? UploadAccess::issueCreateToken($form) : 'invalid',
        ]);
        $controller = new \verbb\formie\controllers\FileUploadController('file-upload', Formie::$plugin);
        $property = new ReflectionProperty($controller, '_requestProfile');
        $property->setValue($controller, \verbb\formie\helpers\BrowserRequestProfile::CROSS_ORIGIN);

        expect(fn() => $controller->actionUpload())->toThrow(
            \yii\web\BadRequestHttpException::class,
            $validToken ? 'No file was uploaded.' : 'Invalid upload creation capability.',
        );
    }, ['method' => 'POST', 'headers' => ['Accept' => 'application/json']]);
})->with([false, true]);

it('preserves submitted file order when staged references and retained ids are mixed', function () {
    WebRequestTestHelper::withWebRequestContext(function () {
        $volume = UploadTestHelper::ensureUploadVolume();
        $form = formie()->form()->fileUploadField('document', ['restrictFiles' => false])->create();
        $field = $form->getFieldByHandle('document');
        $retained = UploadTestHelper::seedAsset('ordered-retained-' . uniqid() . '.txt', 'retained', $volume);
        $uploads = Formie::$plugin->getFileUploads();
        $staged = UploadTestHelper::seedStagedAsset($form, $field->uid, 'document', 'ordered-staged-' . uniqid() . '.txt', 'staged');
        $tracked = $uploads->getTrackedUploadByAssetId((int)$staged->id);
        $submission = new Submission();
        $submission->setForm($form);
        $submission->getContentState()->uploadClaims = new \verbb\formie\models\SubmissionUploadClaims();

        $value = $field->normalizeValueFromRequest([
            [
                'uploadUid' => $tracked['uid'],
                'attachToken' => UploadAccess::issueToken((int)$staged->id, (int)$form->id, $field->uid, purpose: 'attach'),
            ],
            ['assetId' => (int)$retained->id],
        ], $submission);

        expect($value->ids())->toBe([(int)$staged->id, (int)$retained->id]);
    });
});

it('does not let payment replay introduce a staged upload', function () {
    WebRequestTestHelper::withWebRequestContext(function () {
        UploadTestHelper::ensureUploadVolume();
        $form = formie()->form()->fileUploadField('document', ['restrictFiles' => false])->create();
        $field = $form->getFieldByHandle('document');
        $submission = new Submission(['isIncomplete' => true]);
        $submission->setForm($form);
        expect(Craft::$app->getElements()->saveElement($submission, false))->toBeTrue();
        $asset = UploadTestHelper::seedStagedAsset(
            $form,
            $field->uid,
            'document',
            'payment-replay-' . uniqid() . '.txt',
            'payment replay',
            (int)$submission->id,
        );
        $submission->setFieldValue('document', [$asset->id]);

        expect(fn() => Formie::$plugin->getFileUploads()->bindAccepted(submissionCommand([
            'form' => $form,
            'submission' => $submission,
            'operation' => SubmissionOperation::PAYMENT_REPLAY,
        ])))->toThrow(\yii\web\ForbiddenHttpException::class, 'Payment replay cannot introduce an upload.');
    });
});

it('does not exchange a view capability for delete authority in another browser', function () {
    WebRequestTestHelper::withWebRequestContext(function ($request) {
        $volume = UploadTestHelper::ensureUploadVolume();
        $form = formie()->form()->fileUploadField('document', ['restrictFiles' => false])->create();
        $field = $form->getFieldByHandle('document');
        $asset = UploadTestHelper::seedAsset('view-only-' . uniqid() . '.txt', 'view only', $volume);
        Formie::$plugin->getFileUploads()->trackSubmissionAsset($asset, $form->id, null, $field->uid, $form, 'document');
        $view = UploadAccess::issueToken($asset->id, $form->id, $field->uid);
        Craft::$app->getSession()->set('formie:authority', 'view-capability-holder');
        $request->setBodyParams(['handle' => $form->handle, 'fieldHandle' => 'document', 'assetIds' => [$asset->id], 'uploadTokens' => [$asset->id => $view]]);
        $controller = new \verbb\formie\controllers\FileUploadController('file-upload', Formie::$plugin);
        $data = $controller->actionHydrate()->data;
        expect($data['assets'][0]['assetId'])->toBe($asset->id)->and($data['assets'][0]['deleteToken'])->toBeNull();
    }, ['method' => 'POST', 'headers' => ['Accept' => 'application/json']]);
});

it('persists recovery evidence and stops before Dispatch when promotion fails', function () {
    $volume = UploadTestHelper::ensureUploadVolume();
    $form = formie()->form()->fileUploadField('document', ['restrictFiles' => false, 'uploadLocationSubpath' => 'dispatch-recovery-' . uniqid()])->settings(['disableCaptchas' => true])->create();
    $asset = UploadTestHelper::seedAsset('dispatch-recovery-' . uniqid() . '.txt', 'do not dispatch', $volume);
    $uploads = Formie::$plugin->getFileUploads();
    $uploads->trackSubmissionAsset($asset, $form->id, null, $form->getFieldByHandle('document')->uid, $form, 'document');
    $submission = new Submission();
    $submission->setForm($form);
    $submission->setFieldValue('document', [$asset->id]);
    $stages = [];
    $observe = function ($event) use (&$stages) { $stages[] = $event->stage; };
    \yii\base\Event::on(\verbb\formie\services\SubmissionWorkflow::class, \verbb\formie\services\SubmissionWorkflow::EVENT_BEFORE_STAGE, $observe);
    $assets = Craft::$app->getAssets();
    Craft::$app->set('assets', new class extends \craft\services\Assets {
        public function moveAsset(\craft\elements\Asset $asset, \craft\models\VolumeFolder $folder, string $filename = ''): bool {
            throw new RuntimeException('No dispatch after failed promotion');
        }
    });
    try {
        expect(fn() => runSubmissionCommand(submissionCommand(['form' => $form, 'submission' => $submission, 'operationId' => 'interrupted-promotion'])))->toThrow(RuntimeException::class, 'No dispatch after failed promotion');
    } finally {
        Craft::$app->set('assets', $assets);
        \yii\base\Event::off(\verbb\formie\services\SubmissionWorkflow::class, \verbb\formie\services\SubmissionWorkflow::EVENT_BEFORE_STAGE, $observe);
    }
    expect($stages)->not->toContain(\verbb\formie\enums\workflow\Stage::DISPATCH);
    $receipt = (new Query())->from(Table::FORMIE_SUBMISSION_OPERATIONS)->where(['submissionId' => $submission->id])->one();
    expect($receipt['state'])->toBe('processing')->and($uploads->getTrackedUploadByAssetId($asset->id)['promotionState'])->toBe('moving');
    $uploads->recoverPromotions($submission->id);
    expect($uploads->getTrackedUploadByAssetId($asset->id)['promotionState'])->toBe('moved');
});

it('adopts legacy upload metadata only through an authorised retained field relation', function () {
    $volume = UploadTestHelper::ensureUploadVolume();
    $form = formie()->form()->fileUploadField('document', ['restrictFiles' => false])->settings(['disableCaptchas' => true])->create();
    $asset = UploadTestHelper::seedAsset('legacy-retain-' . uniqid() . '.txt', 'legacy content', $volume);
    $submission = formie()->submission($form)->with(['document' => [$asset->id]])->save();
    $uploads = Formie::$plugin->getFileUploads();
    $uploads->trackSubmissionAsset($asset, $form->id, $submission->id, $form->getFieldByHandle('document')->uid, $form, 'document');
    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_PENDING_UPLOADS, [
        'siteId' => null, 'contentKey' => null, 'browserHash' => null, 'expiresAt' => time() - 1,
    ], ['assetId' => $asset->id])->execute();
    $result = runSubmissionCommand(submissionCommand(['form' => $form, 'submission' => $submission, 'operation' => SubmissionOperation::REVISE]));
    $row = $uploads->getTrackedUploadByAssetId($asset->id);
    expect($result->success)->toBeTrue()->and($row['state'])->toBe('finalized')
        ->and($row['contentKey'])->toBe('document')->and((int)$row['siteId'])->toBe((int)$form->siteId)
        ->and(file_get_contents($asset->getCopyOfFile()))->toBe('legacy content');
});

it('does not reclaim an expired claim while its submission persistence lock is active', function () {
    $volume = UploadTestHelper::ensureUploadVolume();
    $form = formie()->form()->fileUploadField('document', ['restrictFiles' => false])->create();
    $asset = UploadTestHelper::seedAsset('active-claim-' . uniqid() . '.txt', 'active claim', $volume);
    $uploads = Formie::$plugin->getFileUploads();
    $uploads->trackSubmissionAsset($asset, $form->id, null, $form->getFieldByHandle('document')->uid, $form, 'document');
    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_PENDING_UPLOADS, ['state' => 'bound', 'expiresAt' => time() - 1], ['assetId' => $asset->id])->execute();
    $submission = new Submission();
    $submission->setForm($form);
    $submission->setFieldValue('document', [$asset->id]);
    $uploads->withUploadLocks($submission, function () use ($uploads, $asset) {
        $uploads->purgeStalePendingUploads();
        expect(\craft\elements\Asset::find()->id($asset->id)->status(null)->one())->not->toBeNull()
            ->and($uploads->getTrackedUploadByAssetId($asset->id))->not->toBeNull();
    });
    $uploads->purgeStalePendingUploads();
    expect(\craft\elements\Asset::find()->id($asset->id)->status(null)->one())->toBeNull()
        ->and($uploads->getTrackedUploadByAssetId($asset->id))->toBeNull();
});
