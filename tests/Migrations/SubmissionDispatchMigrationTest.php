<?php

use craft\db\Query;
use verbb\formie\Formie;
use verbb\formie\migrations\m260927_050000_submission_dispatches;
use verbb\formie\models\IntegrationExecutionContext;
use verbb\formie\models\IntegrationResult;
use verbb\formie\services\DeliveryAttempts;
use verbb\formie\services\SubmissionDispatches;

it('backfills historical runs without republishing or changing prepared delivery identities', function () {
    $form = formie()->form()->singleLineTextField('message')->create();
    $submission = formie()->submission($form)->save();
    $attempts = Formie::$plugin->getDeliveryAttempts();
    $pendingUid = $attempts->prepare(new IntegrationExecutionContext($submission->id, $form->id, 'pending', 'legacy-pending'), 'integration');
    $unknownUid = $attempts->prepare(new IntegrationExecutionContext($submission->id, $form->id, 'unknown', 'legacy-unknown'), 'integration');
    $attempts->execute($unknownUid, fn() => IntegrationResult::unknown());
    $before = (new Query())->from(DeliveryAttempts::TABLE)->where(['submissionId' => $submission->id])->orderBy('id')->all();
    Craft::$app->getDb()->createCommand()->delete(SubmissionDispatches::TABLE, ['submissionId' => $submission->id])->execute();
    $published = 0;
    $listener = static function () use (&$published) { $published++; };
    $queue = Craft::$app->getQueue();
    $queue->on(\yii\queue\Queue::EVENT_BEFORE_PUSH, $listener);
    try {
        $migration = new m260927_050000_submission_dispatches();
        $backfill = new ReflectionMethod($migration, '_backfillHistory');
        $backfill->invoke($migration);
        $backfill->invoke($migration);
        $runs = Formie::$plugin->getSubmissionDispatches();
        expect($runs->get($submission->id, 'legacy-pending')->status)->toBe('scheduled')
            ->and($runs->get($submission->id, 'legacy-unknown')->status)->toBe('needs-attention')
            ->and($runs->get($submission->id, 'legacy-pending')->schedulingComplete)->toBeTrue()
            ->and($published)->toBe(0)
            ->and((int)(new Query())->from(SubmissionDispatches::TABLE)->where(['submissionId' => $submission->id, 'kind' => 'completion', 'schedulingComplete' => true])->count())->toBe(1)
            ->and((new Query())->from(DeliveryAttempts::TABLE)->where(['submissionId' => $submission->id])->orderBy('id')->all())->toBe($before)
            ->and($attempts->get($pendingUid)['status'])->toBe('pending')
            ->and($attempts->get($unknownUid)['status'])->toBe('unknown');
    } finally {
        $queue->off(\yii\queue\Queue::EVENT_BEFORE_PUSH, $listener);
    }
});
