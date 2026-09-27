<?php

use craft\db\Query;
use craft\elements\Asset;
use Tests\Support\UploadTestHelper;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\elements\Submission;
use verbb\formie\enums\SubmissionOperation;
use verbb\formie\enums\workflow\Stage;
use verbb\formie\Formie;
use verbb\formie\helpers\Table;
use verbb\formie\services\SubmissionWorkflow;
use yii\base\Event;

it('finishes upload promotion and finalization before completion events and dispatch', function (bool $replay): void {
    WebRequestTestHelper::withWebRequestContext(function () use ($replay): void {
        UploadTestHelper::ensureUploadVolume();
        $form = formie()->form()->fileUploadField('document', [
            'restrictFiles' => false, 'filenameFormat' => 'promoted-' . uniqid(),
        ])->settings(['disableCaptchas' => true])->create();
        $asset = UploadTestHelper::seedAsset('completion-source-' . uniqid() . '.txt', 'completion');
        $submission = new Submission();
        $submission->setForm($form);
        $submission->setFieldValue('document', [$asset->id]);
        if ($replay) {
            $submission->isIncomplete = true;
            expect(Craft::$app->getElements()->saveElement($submission, false))->toBeTrue();
        }
        $uploads = Formie::$plugin->getFileUploads();
        $uploads->trackSubmissionAsset($asset, (int)$form->id, $submission->id, $form->getFieldByHandle('document')->uid, $form, 'document');
        if ($replay) {
            $uploads->bindPersisted($submission);
        }
        $operation = $replay ? SubmissionOperation::PAYMENT_REPLAY : SubmissionOperation::SUBMIT;
        $events = [];
        $stagingDuringSaves = [];
        $save = static function ($event) use ($form, &$stagingDuringSaves): void {
            if ((int)$event->sender->formId === (int)$form->id) {
                $state = (new ReflectionProperty(\verbb\formie\fields\FileUpload::class, '_stagedElements'))->getValue();
                $stagingDuringSaves[] = $state[$event->sender]['document'] ?? false;
            }
        };
        $complete = static function ($event) use ($form, $asset, $uploads, &$events): void {
            if ((int)$event->submission->formId !== (int)$form->id) {
                return;
            }
            expect($uploads->getTrackedUploadByAssetId((int)$asset->id)['state'])->toBe('finalized');
            $events[] = 'complete';
        };
        $dispatch = static function ($event) use ($form, $asset, $uploads, &$events): void {
            if ((int)$event->command->form->id === (int)$form->id && $event->stage === Stage::DISPATCH->value) {
                expect($uploads->getTrackedUploadByAssetId((int)$asset->id)['state'])->toBe('finalized');
                $events[] = 'dispatch';
            }
        };
        Event::on(Submission::class, Submission::EVENT_AFTER_COMPLETE, $complete);
        Event::on(Submission::class, Submission::EVENT_AFTER_SAVE, $save);
        Event::on(SubmissionWorkflow::class, SubmissionWorkflow::EVENT_BEFORE_STAGE, $dispatch);
        $assets = Craft::$app->getAssets();
        $failing = new class extends \craft\services\Assets {
            public array $observedIncomplete = [];
            public function moveAsset(Asset $asset, \craft\models\VolumeFolder $folder, string $filename = ''): bool
            {
                $row = Formie::$plugin->getFileUploads()->getTrackedUploadByAssetId((int)$asset->id);
                $this->observedIncomplete[] = (bool)(new Query())->select('isIncomplete')->from(Table::FORMIE_SUBMISSIONS)->where(['id' => $row['submissionId']])->scalar();
                throw new RuntimeException('Controlled promotion failure.');
            }
        };
        try {
            Craft::$app->set('assets', $failing);
            try {
                expect(fn() => runSubmissionCommand(submissionCommand(['form' => $form, 'submission' => $submission, 'operation' => $operation])))
                    ->toThrow(RuntimeException::class, 'Controlled promotion failure.');
            } finally {
                Craft::$app->set('assets', $assets);
            }
            expect($failing->observedIncomplete)->toBe([true])->and($events)->toBe([]);
            $saved = Submission::find()->id($submission->id)->isIncomplete(null)->one();
            expect($saved->isIncomplete)->toBeTrue()
                ->and($uploads->getTrackedUploadByAssetId((int)$asset->id)['state'])->toBe('bound')
                ->and($uploads->getTrackedUploadByAssetId((int)$asset->id)['promotionState'])->toBe('moving');
            $uploads->recoverPromotions((int)$saved->id);
            expect(Submission::find()->id($saved->id)->isIncomplete(null)->one()->isIncomplete)->toBeTrue();
            $result = runSubmissionCommand(submissionCommand(['form' => $form, 'submission' => $saved, 'operation' => $operation]));
            expect($result->success)->toBeTrue()->and($events)->toBe(['complete', 'dispatch'])
                ->and(Submission::find()->id($saved->id)->one()->isIncomplete)->toBeFalse()
                ->and($uploads->getTrackedUploadByAssetId((int)$asset->id)['promotionState'])->toBe('moved');
            if (!$replay) {
                expect($stagingDuringSaves)->toBe([true, true, true]);
                $state = (new ReflectionProperty(\verbb\formie\fields\FileUpload::class, '_stagedElements'))->getValue();
                expect(isset($state[$saved]))->toBeFalse()->and(isset($state[$submission]))->toBeFalse();
            }
        } finally {
            Event::off(Submission::class, Submission::EVENT_AFTER_COMPLETE, $complete);
            Event::off(Submission::class, Submission::EVENT_AFTER_SAVE, $save);
            Event::off(SubmissionWorkflow::class, SubmissionWorkflow::EVENT_BEFORE_STAGE, $dispatch);
        }
    });
})->with(['submit' => false, 'payment replay' => true]);

it('keeps the single element write for ordinary submissions without uploads', function (): void {
    WebRequestTestHelper::withWebRequestContext(function (): void {
        $form = formie()->form()->singleLineTextField('message')->settings(['disableCaptchas' => true])->create();
        $submission = new Submission();
        $submission->setForm($form);
        $submission->setFieldValue('message', 'One write');
        $writes = 0;
        $handler = static function ($event) use ($form, &$writes): void {
            if ((int)$event->sender->formId === (int)$form->id) {
                $writes++;
            }
        };
        Event::on(Submission::class, Submission::EVENT_AFTER_SAVE, $handler);
        try {
            expect(runSubmissionCommand(submissionCommand(['form' => $form, 'submission' => $submission]))->success)->toBeTrue()
                ->and($writes)->toBe(1);
        } finally {
            Event::off(Submission::class, Submission::EVENT_AFTER_SAVE, $handler);
        }
    });
});

it('retains incomplete state and promoted uploads when the completion save fails', function (): void {
    WebRequestTestHelper::withWebRequestContext(function (): void {
        UploadTestHelper::ensureUploadVolume();
        $form = formie()->form()->fileUploadField('document', ['restrictFiles' => false])->settings(['disableCaptchas' => true])->create();
        $asset = UploadTestHelper::seedAsset('completion-commit-' . uniqid() . '.txt', 'completion');
        $submission = new Submission();
        $submission->setForm($form);
        $submission->setFieldValue('document', [$asset->id]);
        $uploads = Formie::$plugin->getFileUploads();
        $uploads->trackSubmissionAsset($asset, (int)$form->id, null, $form->getFieldByHandle('document')->uid, $form, 'document');
        $handler = static function ($event) use ($form): void {
            if ((int)$event->sender->formId === (int)$form->id && !$event->sender->isIncomplete) {
                $event->isValid = false;
            }
        };
        Event::on(Submission::class, Submission::EVENT_BEFORE_SAVE, $handler);
        try {
            expect(fn() => runSubmissionCommand(submissionCommand(['form' => $form, 'submission' => $submission])))
                ->toThrow(RuntimeException::class, 'Unable to persist upload-backed completion.');
        } finally {
            Event::off(Submission::class, Submission::EVENT_BEFORE_SAVE, $handler);
        }
        $saved = Submission::find()->id($submission->id)->isIncomplete(null)->one();
        expect($saved->isIncomplete)->toBeTrue()->and($submission->isIncomplete)->toBeTrue()
            ->and($uploads->getTrackedUploadByAssetId((int)$asset->id)['state'])->toBe('bound')
            ->and($uploads->getTrackedUploadByAssetId((int)$asset->id)['promotionState'])->toBe('moved');
        expect(runSubmissionCommand(submissionCommand(['form' => $form, 'submission' => $saved]))->success)->toBeTrue()
            ->and($uploads->getTrackedUploadByAssetId((int)$asset->id)['state'])->toBe('finalized');
    });
});
