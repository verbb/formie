<?php

use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\jobs\SendNotification;
use verbb\formie\models\IntegrationExecutionContext;
use verbb\formie\models\Notification;
use verbb\formie\services\IntegrationRunner;
use verbb\formie\services\Notifications;

function withQueuedDeliveryContext(callable $callback): void
{
    $sites = Craft::$app->getSites();
    $originalSite = $sites->getCurrentSite();
    $originalLanguage = Craft::$app->language;
    $originalLocale = Craft::$app->getLocale();
    $primary = $sites->getPrimarySite();
    $other = current(array_filter($sites->getAllSites(), fn($site) => $site->id !== $primary->id));
    expect($other)->not->toBeFalse();
    try {
        $sites->setCurrentSite($primary);
        $form = formie()->form()->singleLineTextField('message')->create();
        $submission = formie()->submission($form)->save();
        $sites->setCurrentSite($other);
        Craft::$app->language = 'fr';
        $locale = Craft::$app->getI18n()->getLocaleById('fr');
        Craft::$app->set('locale', $locale);
        $callback($submission);
        expect($sites->getCurrentSite())->toBe($other)
            ->and(Craft::$app->language)->toBe('fr')
            ->and(Craft::$app->getLocale())->toBe($locale);
    } finally {
        $sites->setCurrentSite($originalSite);
        Craft::$app->language = $originalLanguage;
        Craft::$app->set('locale', $originalLocale);
    }
}

it('restores the worker context after integration and dispatch attempts', function (string $step, bool $throws) {
    withQueuedDeliveryContext(function (Submission $submission) use ($step, $throws) {
        $attempts = Formie::$plugin->getDeliveryAttempts();
        $uid = $attempts->prepare(new IntegrationExecutionContext($submission->id, $submission->formId, 'missing', uniqid(), 'queued'), $step, ['handles' => [], 'triggerContext' => [], 'acceptedFingerprint' => Formie::$plugin->getIntegrationRunner()->dispatchFingerprint($submission, [])]);
        $runner = Formie::$plugin->getIntegrationRunner();
        $seen = [];
        $event = $step === 'integration' ? IntegrationRunner::EVENT_RESULT : IntegrationRunner::EVENT_BATCH_COMPLETED;
        $listener = function () use (&$seen, $throws) {
            $seen = [Craft::$app->getSites()->getCurrentSite()->id, Craft::$app->language, Craft::$app->getLocale()->id];
            if ($throws) throw new RuntimeException('Synthetic delivery listener failure');
        };
        $runner->on($event, $listener);
        try {
            if ($throws && $step === 'integration') {
                expect(fn() => $runner->runQueuedAttempt($uid))->toThrow(RuntimeException::class, 'Synthetic delivery listener failure');
            } else {
                $result = $runner->runQueuedAttempt($uid);
                expect($result->status->value)->toBe($throws ? 'unknown' : ($step === 'integration' ? 'skipped' : 'succeeded'));
            }
            expect($seen)->toBe([$submission->siteId, $submission->getSite()->language, $submission->getSite()->language]);
        } finally {
            $runner->off($event, $listener);
        }
    });
})->with([['integration', false], ['integration', true], ['dispatch', false], ['dispatch', true]]);

it('restores the worker context after notification success and failures', function (string $outcome) {
    withQueuedDeliveryContext(function (Submission $submission) use ($outcome) {
        $original = Formie::$plugin->getNotifications();
        $notification = new Notification(['formId' => $submission->formId, 'name' => 'Context test', 'handle' => 'contextTest']);
        expect($original->saveNotification($notification, false))->toBeTrue();
        $notifications = new class extends Notifications {
            public string $outcome;
            public array $seen = [];
            public function sendNotificationEmail(Notification $notification, Submission $submission, $queueJob = null, ?string $deliveryKey = null): array|bool
            {
                $this->seen = [Craft::$app->getSites()->getCurrentSite()->id, Craft::$app->language, Craft::$app->getLocale()->id];
                if ($this->outcome === 'throw') throw new RuntimeException('Synthetic notification exception');
                return $this->outcome === 'success' ? true : ['success' => false, 'status' => 'failed'];
            }
        };
        $notifications->outcome = $outcome;
        Formie::$plugin->set('notifications', $notifications);
        try {
            $uid = Formie::$plugin->getDeliveryAttempts()->prepare(new IntegrationExecutionContext($submission->id, $submission->formId, 'notification', uniqid(), 'queued'), 'notification', ['notificationId' => $notification->id]);
            $job = new SendNotification(['deliveryAttemptUid' => $uid]);
            $execute = fn() => $job->execute(new \yii\queue\sync\Queue());
            if ($outcome === 'success') $execute();
            else expect($execute)->toThrow(RuntimeException::class, $outcome === 'throw' ? 'Synthetic notification exception' : 'Notification delivery failed');
            expect($notifications->seen)->toBe([$submission->siteId, $submission->getSite()->language, $submission->getSite()->language]);
        } finally {
            Formie::$plugin->set('notifications', $original);
        }
    });
})->with(['success', 'failure', 'throw']);
