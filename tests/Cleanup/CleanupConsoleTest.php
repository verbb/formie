<?php

declare(strict_types=1);

use craft\console\Application;
use craft\console\Controller;
use craft\services\Gc;
use Tests\Support\UploadTestHelper;
use verbb\formie\elements\SentNotification;
use verbb\formie\elements\Submission;
use verbb\formie\Formie;
use verbb\formie\helpers\Table;
use verbb\formie\services\Cleanup;

beforeEach(function (): void {
    UploadTestHelper::ensureUploadVolume();
    $this->cleanupTransaction = Craft::$app->getDb()->beginTransaction();
    $settings = Formie::$plugin->getSettings();
    $settings->maxIncompleteSubmissionAge = 1;
    $settings->maxSentNotificationsAge = 1;
    $settings->saveSpam = true;
    $settings->spamLimit = 1;

    $this->cleanupForm = formie()->form(['dataRetention' => 'days', 'dataRetentionValue' => '1'])
        ->singleLineTextField('name')->create();
    $this->expiredSubmission = formie()->submission($this->cleanupForm)->with(['name' => 'Expired'])->save();
    $this->recentSubmission = formie()->submission($this->cleanupForm)->with(['name' => 'Recent'])->save();
    $this->expiredDraft = formie()->submission($this->cleanupForm)->with(['name' => 'Draft'])->save();
    $this->expiredDraft->isIncomplete = true;
    expect(Craft::$app->getElements()->saveElement($this->expiredDraft, false))->toBeTrue();

    // Incomplete-submission cleanup emits its detailed output when pruning spam.
    foreach (['Old spam', 'Recent spam'] as $name) {
        $spam = formie()->submission($this->cleanupForm)->with(['name' => $name])->save();
        $spam->isSpam = true;
        expect(Craft::$app->getElements()->saveElement($spam, false))->toBeTrue();
    }

    $this->expiredNotification = new SentNotification([
        'title' => 'Expired notice', 'formId' => (string)$this->cleanupForm->id,
        'subject' => 'Stored notice', 'to' => 'recipient@example.test',
        'from' => 'sender@example.test', 'body' => 'Stored content', 'success' => true,
    ]);
    expect(Craft::$app->getElements()->saveElement($this->expiredNotification))->toBeTrue();
    Craft::$app->getDb()->createCommand()->update('{{%elements}}', [
        'dateCreated' => '2000-01-01 00:00:00', 'dateUpdated' => '2000-01-01 00:00:00',
    ], ['id' => [$this->expiredSubmission->id, $this->expiredDraft->id, $this->expiredNotification->id]], [], false)->execute();

    formie()->form()->fileUploadField('documents', [
        'assetDataRetention' => 'days', 'assetDataRetentionValue' => '1',
    ])->create();

    $this->expiredProgress = Formie::$plugin->getSubmissionProgress()->upsertPageState($this->cleanupForm);
    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_SUBMISSION_PROGRESS,
        ['expiresAt' => time() - 60], ['id' => $this->expiredProgress->id])->execute();
});

afterEach(function (): void {
    $this->cleanupTransaction->rollBack();
    Formie::$plugin->getForms()->invalidateFormCaches();
});

it('runs each verbose pruner with a console application', function (string $task): void {
    expect(Craft::$app)->toBeInstanceOf(Application::class);
    Formie::$plugin->getCleanup()->runTask($task, Craft::$app);
})->with([
    Cleanup::TASK_INCOMPLETE_SUBMISSIONS,
    Cleanup::TASK_DATA_RETENTION_SUBMISSIONS,
    Cleanup::TASK_SENT_NOTIFICATIONS,
    Cleanup::TASK_FILE_UPLOAD_ASSET_RETENTION,
]);

it('finishes cleanup through GC, explicit controllers and silent calls', function (string $mode): void {
    $controller = new class('cleanup-test', Craft::$app) extends Controller {
        public string $output = '';

        public function stdout($string): int
        {
            $this->output .= $string;
            return strlen($string);
        }
    };

    if ($mode === 'gc') {
        // Exercise Formie's actual GC listener without running unrelated core GC tasks.
        Craft::$app->getGc()->trigger(Gc::EVENT_RUN);
    } else {
        Formie::$plugin->getCleanup()->runAll($mode === 'controller' ? $controller : null);
    }

    expect(Submission::find()->id($this->expiredSubmission->id)->status(null)->one())->toBeNull()
        ->and(Submission::find()->id($this->expiredDraft->id)->status(null)->isIncomplete(null)->one())->toBeNull()
        ->and(SentNotification::find()->id($this->expiredNotification->id)->one())->toBeNull()
        ->and(Submission::find()->id($this->recentSubmission->id)->one())->not->toBeNull()
        ->and(Formie::$plugin->getSubmissionProgress()->loadProgress($this->expiredProgress->id))->toBeNull();

    if ($mode === 'controller') {
        expect($controller->output)->toContain('Preparing to prune', 'Pruned sent notification with ID:',
            'Starting data retention checks', 'Starting file upload asset retention', 'Pruned progress rows:');
    } else {
        expect($controller->output)->toBe('');
    }
})->with(['gc', 'controller', 'silent']);
