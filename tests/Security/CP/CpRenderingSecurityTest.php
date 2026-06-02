<?php

declare(strict_types=1);

use Tests\Support\MaliciousPayloads;
use Tests\Support\WebRequestTestHelper;
use Craft;
use craft\web\View;
use yii\web\ForbiddenHttpException;
use verbb\formie\controllers\SentNotificationsController;
use verbb\formie\elements\SentNotification;
use verbb\formie\fields\FileUpload;
use verbb\formie\fields\Recipients;
use verbb\formie\fields\values\RecipientsFieldValue;
use verbb\formie\models\Notification;

function renderCpTemplate(string $template, array $variables): string
{
    $view = Craft::$app->getView();
    $oldTemplateMode = $view->getTemplateMode();
    $view->setTemplateMode(View::TEMPLATE_MODE_CP);

    try {
        return $view->renderTemplate($template, $variables);
    } finally {
        $view->setTemplateMode($oldTemplateMode);
    }
}

it('renders the send notification modal with the submission id and notification options', function (): void {
    $form = formie()
        ->form(['title' => 'CP Send Notification Security'])
        ->singleLineTextField('fullName')
        ->create();

    $submission = formie()
        ->submission($form)
        ->with(['fullName' => 'Security Tester'])
        ->save();

    $notification = new Notification([
        'name' => 'Admin Notification',
        'handle' => 'adminNotification' . uniqid(),
        'enabled' => true,
        'subject' => 'Security Subject',
        'to' => 'admin@example.test',
    ]);

    $form->setNotifications([$notification]);
    expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();

    $html = renderCpTemplate('formie/submissions/_includes/send-notification-modal', [
        'submission' => $submission,
        'notifications' => $form->getNotifications(),
    ]);

    expect($html)
        ->toContain('<h2>Send Email Notification</h2>')
        ->toContain('name="submissionId" value="' . $submission->id . '"')
        ->toContain('name="notificationId"')
        ->toContain('Admin Notification');
})->group('security');

it('renders the resend notification modal with stored recipients and preview chrome', function (): void {
    $sentNotification = new SentNotification([
        'id' => 123,
        'to' => 'alerts@example.test',
        'htmlBody' => '<p>Preview body</p>',
    ]);

    $html = renderCpTemplate('formie/sent-notifications/_includes/resend-modal', [
        'sentNotification' => $sentNotification,
    ]);

    expect($html)
        ->toContain('<h2>Resend Email Notification</h2>')
        ->toContain('name="to"')
        ->toContain('value="alerts@example.test"')
        ->toContain('name="id" value="123"')
        ->toContain('fui-email-preview')
        ->toContain('id="fui-email-meta-to"');
})->group('security');

it('escapes hostile sent notification html when embedding iframe srcdoc previews', function (): void {
    $payload = '<img src=x onerror=alert("xss")>" autofocus="autofocus';
    $sentNotification = new SentNotification([
        'htmlBody' => $payload,
        'to' => 'alerts@example.test',
    ]);

    $html = renderCpTemplate('formie/sent-notifications/_includes/preview', [
        'sentNotification' => $sentNotification,
    ]);

    expect($html)
        ->toContain('srcdoc="&lt;img src=x onerror=alert(&quot;xss&quot;)&gt;&quot; autofocus=&quot;autofocus"')
        ->toContain('sandbox="allow-same-origin"')
        ->and($html)->not->toContain('srcdoc="<img src=x onerror=alert("xss")>" autofocus="autofocus"')
        ->and($html)->not->toContain('autofocus="autofocus"');
})->group('security');

it('requires sent notification access permission before returning resend modal content', function (): void {
    $originalUser = Craft::$app->getUser()->getIdentity();
    Craft::$app->getUser()->setIdentity(null);

    try {
        WebRequestTestHelper::withWebRequestContext(function (): void {
            $controller = new SentNotificationsController('formie-sent-notifications-security', Craft::$app);

            expect(fn() => $controller->actionGetResendModalContent())
                ->toThrow(ForbiddenHttpException::class);
        }, [
            'method' => 'POST',
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);
    } finally {
        Craft::$app->getUser()->setIdentity($originalUser);
    }
})->group('security');

it('escapes stored plain-text values when rendering frontend summary blocks', function (): void {
    $form = formie()
        ->form(['title' => 'Summary Sink Security'])
        ->singleLineTextField('fullName')
        ->summaryField('summary')
        ->create();

    $submission = formie()
        ->submission($form)
        ->with(['fullName' => MaliciousPayloads::storedXssProbe()])
        ->save();

    $field = $form->getFieldByHandle('summary');
    $html = renderCpTemplate('formie/_special/form-template/fields/summary', [
        'form' => $form,
        'field' => $field,
        'submission' => $submission,
        'value' => null,
    ]);

    expect($html)
        ->toContain('safe-text')
        ->toContain('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;')
        ->and($html)->toContain('onerror=')
        ->and($html)->not->toContain('<script>alert("xss")</script>');
})->group('security');

it('renders sanitized rich-text summaries as html without executing hostile payloads', function (): void {
    $payload = '<p>Safe <strong>content</strong></p><script>alert("xss")</script><img src=x onerror=alert("xss")>';
    $form = formie()
        ->form(['title' => 'Rich Text Summary Sink Security'])
        ->multiLineTextField('bio', ['useRichText' => true])
        ->summaryField('summary')
        ->create();

    $submission = formie()
        ->submission($form)
        ->with(['bio' => $payload])
        ->save();

    $field = $form->getFieldByHandle('summary');
    $html = renderCpTemplate('formie/_special/form-template/fields/summary', [
        'form' => $form,
        'field' => $field,
        'submission' => $submission,
        'value' => null,
    ]);

    expect($html)
        ->toContain('<p>Safe <strong>content</strong></p>')
        ->and($html)->not->toContain('<script')
        ->and($html)->not->toContain('onerror=')
        ->and($html)->not->toContain('&lt;p&gt;Safe');
})->group('security');

it('escapes hostile stored values in the cp submission edit field sink', function (): void {
    $form = formie()
        ->form(['title' => 'CP Submission Edit Sink'])
        ->singleLineTextField('fullName')
        ->create();

    $submission = formie()
        ->submission($form)
        ->with(['fullName' => MaliciousPayloads::attributeBreakoutProbe()])
        ->save();

    $field = $form->getFieldByHandle('fullName');
    $html = (string)$field?->getSubmissionHtml($submission->getFieldValue('fullName'), $submission);

    expect($html)
        ->toContain('value="&quot; autofocus onfocus=&quot;alert(&#039;xss&#039;)&quot; data-breakout=&quot;1"')
        ->and($html)->not->toContain('value="" autofocus onfocus="alert')
        ->and($html)->not->toContain('data-breakout="1"');
})->group('security');

it('escapes hostile stored values in hidden cp submission edit sinks', function (): void {
    $form = formie()
        ->form(['title' => 'CP Hidden Submission Edit Sink'])
        ->hiddenField('trackingCode')
        ->create();

    $submission = formie()
        ->submission($form)
        ->with(['trackingCode' => MaliciousPayloads::attributeBreakoutProbe()])
        ->save();

    $field = $form->getFieldByHandle('trackingCode');
    $html = (string)$field?->getSubmissionHtml($submission->getFieldValue('trackingCode'), $submission);

    expect($html)
        ->toContain('value="&quot; autofocus onfocus=&quot;alert(&#039;xss&#039;)&quot; data-breakout=&quot;1"')
        ->and($html)->not->toContain('value="" autofocus onfocus="alert')
        ->and($html)->not->toContain('data-breakout="1"');
})->group('security');

it('escapes hostile stored values in the cp submission index preview sink', function (): void {
    $form = formie()
        ->form(['title' => 'CP Submission Index Sink'])
        ->singleLineTextField('fullName')
        ->create();

    $submission = formie()
        ->submission($form)
        ->with(['fullName' => MaliciousPayloads::storedXssProbe()])
        ->save();

    $field = $form->getFieldByHandle('fullName');
    $html = $field?->getPreviewHtml($submission->getFieldValue('fullName'), $submission);

    expect($html)
        ->toContain('safe-text')
        ->and($html)->not->toContain('<script')
        ->and($html)->not->toContain('onerror=');
})->group('security');

it('returns an empty preview for unset single-option field values in cp indexes', function (): void {
    $form = formie()
        ->form(['title' => 'CP Empty Single Option Preview'])
        ->dropdownField('topic', [
            'options' => [
                ['label' => 'General', 'value' => 'general'],
                ['label' => 'Support', 'value' => 'support'],
            ],
        ])
        ->create();

    $submission = formie()
        ->submission($form)
        ->with([])
        ->save();

    $field = $form->getFieldByHandle('topic');
    $html = $field?->getPreviewHtml($submission->getFieldValue('topic'), $submission);

    expect($html)->toBe('');
})->group('security');

it('escapes hostile option labels in single-option cp previews', function (): void {
    $form = formie()
        ->form(['title' => 'CP Single Option Label Preview'])
        ->dropdownField('topic', [
            'options' => [
                ['label' => '<script>alert("xss")</script>General', 'value' => 'general'],
                ['label' => 'Support', 'value' => 'support'],
            ],
        ])
        ->create();

    $submission = formie()
        ->submission($form)
        ->with(['topic' => 'general'])
        ->save();

    $field = $form->getFieldByHandle('topic');
    $html = $field?->getPreviewHtml($submission->getFieldValue('topic'), $submission);

    expect($html)
        ->toContain('alert(&quot;xss&quot;)General')
        ->and($html)->not->toContain('<script>alert("xss")</script>General')
        ->and($html)->not->toContain('<script');
})->group('security');

it('escapes hostile recipients labels in cp previews', function (): void {
    $field = new Recipients([
        'displayType' => 'dropdown',
        'handle' => 'notify',
        'label' => 'Notify',
    ]);

    $value = new RecipientsFieldValue(
        'dropdown',
        'ops',
        '<script>alert("xss")</script>Operations',
        true,
        [],
        []
    );

    $html = $field->getPreviewHtml($value, new SentNotification());

    expect($html)
        ->toContain('alert(&quot;xss&quot;)Operations')
        ->and($html)->not->toContain('<script>alert("xss")</script>Operations')
        ->and($html)->not->toContain('<script');
})->group('security');

it('escapes hostile sent notification preview text for index columns', function (): void {
    $sentNotification = new SentNotification([
        'body' => '<script>alert("xss")</script> safe-text',
    ]);

    $method = new ReflectionMethod(SentNotification::class, 'attributeHtml');
    $method->setAccessible(true);
    $html = $method->invoke($sentNotification, 'preview');

    expect($html)
        ->toContain('alert(&quot;xss&quot;) safe-text')
        ->and($html)->not->toContain('<script')
        ->and($html)->not->toContain('</script>');
})->group('security');

it('escapes hostile metadata when rendering the sent notification preview metadata sink', function (): void {
    $sentNotification = new SentNotification([
        'id' => 321,
        'to' => 'victim@example.test<script>alert("xss")</script>',
        'subject' => '<script>alert("xss")</script>Subject',
        'from' => 'sender@example.test',
        'fromName' => MaliciousPayloads::attributeBreakoutProbe(),
        'htmlBody' => '<p>Preview body</p>',
        'info' => ['apiMessage' => '<script>alert("xss")</script>Provider Error'],
    ]);

    $html = renderCpTemplate('formie/sent-notifications/_includes/preview', [
        'sentNotification' => $sentNotification,
    ]);

    expect($html)
        ->toContain('victim@example.test&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;')
        ->toContain('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;Subject')
        ->toContain('&quot; autofocus onfocus=&quot;alert(&#039;xss&#039;)&quot; data-breakout=&quot;1')
        ->and($html)->not->toContain('<script>alert("xss")</script>Subject');
})->group('security');

it('drops unsafe file metadata urls from summary links while preserving filenames', function (): void {
    $field = new FileUpload([
        'handle' => 'documents',
    ]);

    $value = new class {
        public function all(): array
        {
            return [
                new class {
                    public string $filename = 'invoice.pdf';
                    public string $url = 'java&#x73;cript:alert("xss")';
                },
            ];
        }
    };

    $html = (string)$field->getValueForSummary($value, null);

    expect($html)
        ->toContain('invoice.pdf')
        ->and($html)->not->toContain('href=')
        ->and($html)->not->toContain('javascript:');
})->group('security');
