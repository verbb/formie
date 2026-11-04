<?php

declare(strict_types=1);

use craft\db\Query;
use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\jobs\SendNotification;
use verbb\formie\models\Notification;
use verbb\formie\services\Emails;

it('applies current site overrides when a queue worker reloads a notification', function (bool $translated, bool $changed, bool $newRequest): void {
    $form = formie()->form()->singleLineTextField('name')->create();
    $sites = Craft::$app->getSites();
    $previousSite = $sites->getCurrentSite();
    $previousLanguage = Craft::$app->language;
    $previousLocale = Craft::$app->getLocale();
    $secondarySiteIds = array_values(array_diff($sites->getAllSiteIds(), [$form->siteId]));
    if ($translated) {
        if (!Craft::$app->getIsMultiSite()) {
            test()->markTestSkipped('Requires a translated notification; source content runs in both suites.');
        }
        expect($secondarySiteIds)->not->toBeEmpty();
    }
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
        $job->execute(Craft::$app->getQueue());
        $job->execute(Craft::$app->getQueue());
        expect($attempts->get($uid)['status'])->toBe('succeeded');
        expect($emails->calls)->toHaveCount(1);
        $expected = $translated ? $localized : ['subject' => $changed ? 'Changed after enqueue' : 'Source subject', 'content' => '<p>Source content</p>'];
        expect($emails->calls[0][0])->toMatchArray($expected);
        expect($emails->calls[0][1])->toBe($siteId);
        expect($emails->calls[0][2])->toBe($sites->getSiteById($siteId)->language);
        expect($sites->getCurrentSite()->id)->toBe($previousSite->id);
        expect(Craft::$app->language)->toBe($previousLanguage);
        expect(Craft::$app->getLocale())->toBe($previousLocale);
    } finally {
        Formie::$plugin->set('emails', $original);
    }
})->with([[false, false, true], [true, false, true], [false, true, true], [true, true, true], [false, true, false], [true, true, false]]);

it('sends a queued notification whose attempt holds no accepted settings', function (): void {
    $form = formie()->form()->create();
    $submission = formie()->submission($form)->save();
    $notification = new Notification(['formId' => $form->id, 'name' => 'Missing operation', 'handle' => 'missingOperation']);
    expect(Formie::$plugin->getNotifications()->saveNotification($notification, false))->toBeTrue();
    $attempts = Formie::$plugin->getDeliveryAttempts();
    $context = new \verbb\formie\models\IntegrationExecutionContext($submission->id, $form->id, 'notification:' . $notification->uid, 'missing-operation', 'queued');
    $uid = $attempts->prepare($context, 'notification', ['notificationId' => $notification->id]);
    $original = Formie::$plugin->getEmails();
    $emails = new class extends Emails {
        public int $calls = 0;
        public function sendEmail(Notification $notification, Submission $submission, mixed $queueJob = null, bool $createSentNotification = true): array
        {
            $this->calls++;
            return ['success' => true];
        }
    };
    Formie::$plugin->set('emails', $emails);
    try {
        (new SendNotification(['deliveryAttemptUid' => $uid]))->execute(Craft::$app->getQueue());
        expect($emails->calls)->toBe(1)->and($attempts->get($uid)['status'])->toBe('succeeded');
    } finally {
        Formie::$plugin->set('emails', $original);
    }
});
