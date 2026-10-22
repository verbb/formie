<?php

declare(strict_types=1);

use craft\db\Query;
use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\jobs\SendNotification;
use verbb\formie\models\Notification;
use verbb\formie\services\Emails;

it('uses the accepted site notification when a queue worker reloads it', function (bool $translated, bool $changed, bool $newRequest): void {
    $form = formie()->form()->singleLineTextField('name')->create();
    $sites = Craft::$app->getSites();
    $previousSite = $sites->getCurrentSite();
    $previousLanguage = Craft::$app->language;
    $previousLocale = Craft::$app->getLocale();
    $secondarySiteIds = array_values(array_diff($sites->getAllSiteIds(), [$form->siteId]));
    expect($secondarySiteIds)->not->toBeEmpty();
    $siteId = $translated ? (int)$secondarySiteIds[0] : (int)$form->siteId;
    $notifications = Formie::$plugin->getNotifications();
    $notification = new Notification([
        'formId' => $form->id, 'name' => 'Site notification', 'handle' => 'siteNotification',
        'enabled' => true, 'subject' => 'Source subject', 'content' => '<p>Source content</p>',
        'fromName' => 'Source sender', 'replyToName' => 'Source reply',
    ]);
    expect($notifications->saveNotification($notification, false))->toBeTrue();
    $localized = ['subject' => 'Translated subject', 'content' => 'Translated content', 'fromName' => 'Translated sender', 'replyToName' => 'Translated reply'];
    if ($translated) {
        Formie::$plugin->getFormSiteOverrides()->saveOverrides($form->id, $siteId, ['notifications' => [$notification->handle => $localized]]);
    }
    $submission = new Submission(['formId' => $form->id, 'siteId' => $siteId, 'title' => 'Queued site notification']);
    $submission->setFieldValue('name', 'Recipient');
    expect(Craft::$app->getElements()->saveElement($submission))->toBeTrue();
    $accepted = $submission->getForm()->getNotifications()[0];
    expect($accepted->subject)->toBe($translated ? $localized['subject'] : 'Source subject');
    $notifications->sendNotification($accepted, $submission, true, 'site-notification');
    $attempts = Formie::$plugin->getDeliveryAttempts();
    $uid = (new Query())->select('uid')->from($attempts::TABLE)->where(['submissionId' => $submission->id, 'executionUid' => 'site-notification'])->scalar();
    if ($changed) {
        if ($translated) {
            $localized['subject'] = 'Changed after enqueue';
            Formie::$plugin->getFormSiteOverrides()->saveOverrides($form->id, $siteId, ['notifications' => [$notification->handle => $localized]]);
        } else {
            $notification->subject = 'Changed after enqueue';
            expect($notifications->saveNotification($notification, false))->toBeTrue();
        }
    }
    if ($newRequest) {
        // Exercise both fresh workers and workers retaining the enqueue request's form cache.
        Formie::$plugin->getForms()->invalidateFormCaches();
        (new ReflectionProperty(Submission::class, '_formByIdCache'))->setValue(null, []);
    }
    $original = Formie::$plugin->getEmails();
    $emails = new class extends Emails {
        public array $calls = [];
        public function sendEmail(Notification $notification, Submission $submission, mixed $queueJob = null, bool $createSentNotification = true): array
        {
            $this->calls[] = [$notification->getAttributes(), Craft::$app->getSites()->getCurrentSite()->id, Craft::$app->language];
            return ['success' => true];
        }
    };
    Formie::$plugin->set('emails', $emails);
    try {
        $job = new SendNotification(['deliveryAttemptUid' => $uid]);
        if ($changed) {
            expect(fn() => $job->execute(Craft::$app->getQueue()))->toThrow(RuntimeException::class, 'Nothing was sent.');
            expect($attempts->supportBundle($uid)['result']['code'])->toBe('operation_stale');
            expect($attempts->supportBundle($uid)['result']['message'])->toContain('retrying this queue job will not send it');
            expect($emails->calls)->toBe([]);
        } else {
            $job->execute(Craft::$app->getQueue());
            $job->execute(Craft::$app->getQueue());
            expect($attempts->get($uid)['status'])->toBe('succeeded');
            expect($emails->calls)->toHaveCount(1);
            expect($emails->calls[0][0])->toMatchArray($translated ? $localized : ['subject' => 'Source subject', 'content' => '<p>Source content</p>']);
            expect($emails->calls[0][1])->toBe($siteId);
            expect($emails->calls[0][2])->toBe($sites->getSiteById($siteId)->language);
        }
        expect($sites->getCurrentSite()->id)->toBe($previousSite->id);
        expect(Craft::$app->language)->toBe($previousLanguage);
        expect(Craft::$app->getLocale())->toBe($previousLocale);
    } finally {
        Formie::$plugin->set('emails', $original);
    }
})->with([[false, false, true], [true, false, true], [false, true, true], [true, true, true], [false, true, false], [true, true, false]]);

it('explains a missing accepted notification operation without sending', function (): void {
    $form = formie()->form()->create();
    $submission = formie()->submission($form)->save();
    $notification = new Notification(['formId' => $form->id, 'name' => 'Missing operation', 'handle' => 'missingOperation']);
    expect(Formie::$plugin->getNotifications()->saveNotification($notification, false))->toBeTrue();
    $attempts = Formie::$plugin->getDeliveryAttempts();
    $context = new \verbb\formie\models\IntegrationExecutionContext($submission->id, $form->id, 'notification:' . $notification->uid, 'missing-operation', 'queued');
    $uid = $attempts->prepare($context, 'notification', ['notificationId' => $notification->id]);
    $calls = 0;
    $result = $attempts->executePrepared($uid, 'unused', function () use (&$calls) {
        $calls++;
        return \verbb\formie\models\IntegrationResult::succeeded();
    });
    expect($calls)->toBe(0);
    expect($result->code)->toBe('operation_stale');
    expect($result->message)->toContain('no accepted operation', 'starting a new delivery');
    expect($result->retryable)->toBeFalse();
});
