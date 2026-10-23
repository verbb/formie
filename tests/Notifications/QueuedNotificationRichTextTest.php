<?php

declare(strict_types=1);

use craft\db\Query;
use craft\helpers\FileHelper;
use craft\helpers\Json;
use craft\helpers\Template;
use Tiptap\Core\Node;
use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\events\MailRenderEvent;
use verbb\formie\events\RegisterTiptapExtensionsEvent;
use verbb\formie\jobs\SendNotification;
use verbb\formie\models\Notification;
use verbb\formie\services\Emails;
use verbb\formie\services\TiptapExtensions;
use verbb\tiptap\RichText;
use yii\mail\BaseMailer;

class QueuedNotificationCtaNode extends Node
{
    public static $name = 'notificationTestCta';

    public function renderHTML($node, $HTMLAttributes = [])
    {
        return ['a', ['href' => $node->attrs->href], 0];
    }

    public function renderText($node)
    {
        return htmlspecialchars($node->attrs->label, ENT_QUOTES, 'UTF-8');
    }
}

it('preserves formatted content and custom nodes through queued email rendering', function (string $format, bool $translated, bool $renderHook): void {
    $nodes = [
        ['type' => 'paragraph', 'content' => [
            ['type' => 'text', 'text' => 'Bold text', 'marks' => [['type' => 'bold']]],
            ['type' => 'text', 'text' => ' and '],
            ['type' => 'text', 'text' => 'italic text', 'marks' => [['type' => 'italic']]],
        ]],
        ['type' => 'bulletList', 'content' => [
            ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'List item']]]]],
        ]],
        ['type' => 'notificationTestCta', 'attrs' => ['label' => 'Read more', 'href' => 'https://example.test/details']],
    ];
    $content = match ($format) {
        'html' => '<p><strong>Bold text</strong> and <em>italic text</em></p><ul><li>List item</li></ul><a href="https://example.test/details" data-notification-cta="true">Read more</a>',
        'document' => ['type' => 'doc', 'content' => $nodes],
        'json' => Json::encode(['type' => 'doc', 'content' => $nodes]),
        default => $nodes,
    };
    $form = formie()->form()->create();
    $siteIds = array_values(array_diff(Craft::$app->getSites()->getAllSiteIds(), [$form->siteId]));
    expect($siteIds)->not->toBeEmpty();
    $siteId = $translated ? (int)$siteIds[0] : (int)$form->siteId;
    $notifications = Formie::$plugin->getNotifications();
    $source = new Notification([
        'formId' => $form->id, 'name' => 'Rich notification', 'handle' => 'richNotification',
        'enabled' => true, 'subject' => 'Rich content', 'from' => 'sender@example.test', 'to' => 'recipient@example.test',
        'content' => $translated ? 'Source content' : $content,
    ]);
    expect($notifications->saveNotification($source, false))->toBeTrue();
    if ($translated) {
        Formie::$plugin->getFormSiteOverrides()->saveOverrides($form->id, $siteId, ['notifications' => [$source->handle => ['content' => $content]]]);
    }

    $originalExtensions = Formie::$plugin->getTiptapExtensions();
    $extensions = new TiptapExtensions();
    if (!$renderHook) {
        $extensions->on(TiptapExtensions::EVENT_REGISTER_EXTENSIONS, static function (RegisterTiptapExtensionsEvent $event): void {
            $event->registerExtension('tests/notification-cta', new QueuedNotificationCtaNode());
        });
    }
    Formie::$plugin->set('tiptapExtensions', $extensions);
    $emails = Formie::$plugin->getEmails();
    $renderedContent = [];
    $render = static function (MailRenderEvent $event) use (&$renderedContent, $renderHook): void {
        $renderedContent[] = $event->notification->content;
        if ($renderHook) {
            // Match custom modules that render their own nodes from the original document.
            $event->renderVariables['contentHtml'] = Template::raw(RichText::from($event->notification->content)->toHtml(additionalExtensions: [new QueuedNotificationCtaNode()]));
        }
    };
    $emails->on(Emails::EVENT_MODIFY_RENDER_VARIABLES, $render);
    $mailer = Craft::$app->getMailer();
    $originalTransport = $mailer->useFileTransport;
    $originalPath = $mailer->fileTransportPath;
    $mailPath = sys_get_temp_dir() . '/formie-rich-notification-' . bin2hex(random_bytes(8));
    $mailer->useFileTransport = true;
    $mailer->fileTransportPath = $mailPath;
    $messages = [];
    $capture = static function ($event) use (&$messages): void {
        $messages[] = $event->message->getSymfonyEmail()->getHtmlBody();
    };
    $mailer->on(BaseMailer::EVENT_BEFORE_SEND, $capture);

    try {
        $submission = new Submission(['formId' => $form->id, 'siteId' => $siteId, 'title' => 'Rich content submission']);
        expect(Craft::$app->getElements()->saveElement($submission))->toBeTrue();
        $accepted = $submission->getForm()->getNotifications()[0];
        $notifications->sendNotification($accepted, $submission, true, 'rich-content');
        $attempts = Formie::$plugin->getDeliveryAttempts();
        $uid = (new Query())->select('uid')->from($attempts::TABLE)->where(['submissionId' => $submission->id, 'executionUid' => 'rich-content'])->scalar();
        Formie::$plugin->getForms()->invalidateFormCaches();
        (new ReflectionProperty(Submission::class, '_formByIdCache'))->setValue(null, []);

        $job = new SendNotification(['deliveryAttemptUid' => $uid]);
        $job->execute(Craft::$app->getQueue());
        $job->execute(Craft::$app->getQueue());
        expect($attempts->get($uid)['status'])->toBe('succeeded');
        expect($messages)->toHaveCount(1);
        expect($messages[0])->toContain('<strong>Bold text</strong>', '<em>italic text</em>', '<ul>', 'List item', 'href="https://example.test/details"', 'Read more');
        expect($renderedContent)->toHaveCount(1);
        if ($format !== 'html') {
            $document = Json::decode($renderedContent[0]);
            // JSON storage may reorder object keys; node order and values must survive.
            expect($document['content'] ?? $document)->toEqual($nodes);
        }
        // Applying a site override must not alter the stored source notification.
        expect($notifications->getNotificationById($source->id)->content)->toBe($source->content);
    } finally {
        $mailer->off(BaseMailer::EVENT_BEFORE_SEND, $capture);
        $mailer->useFileTransport = $originalTransport;
        $mailer->fileTransportPath = $originalPath;
        $emails->off(Emails::EVENT_MODIFY_RENDER_VARIABLES, $render);
        Formie::$plugin->set('tiptapExtensions', $originalExtensions);
        FileHelper::removeDirectory($mailPath);
    }
})->with([
    'source HTML' => ['html', false, false],
    'translated HTML' => ['html', true, false],
    'source document' => ['document', false, false],
    'translated document' => ['document', true, false],
    'translated node list' => ['nodes', true, false],
    'translated JSON' => ['json', true, false],
    'source render hook' => ['document', false, true],
    'translated document render hook' => ['document', true, true],
    'translated node list render hook' => ['nodes', true, true],
    'translated JSON render hook' => ['json', true, true],
]);
