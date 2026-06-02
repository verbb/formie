<?php

declare(strict_types=1);

use Tests\Support\MaliciousPayloads;
use verbb\formie\Formie;
use verbb\formie\fields\FileUpload;
use verbb\formie\fields\SingleLineText;
use verbb\formie\helpers\References;
use verbb\formie\models\Notification;
use craft\web\View;

function renderEmailTemplate(string $template, array $variables): string
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

function withEmailEnvOverrides(array $values, callable $callback): mixed
{
    $original = [];

    foreach ($values as $name => $value) {
        $original[$name] = getenv($name);

        if ($value === null) {
            putenv((string)$name);
            unset($_ENV[$name], $_SERVER[$name]);
            continue;
        }

        putenv("{$name}={$value}");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }

    try {
        return $callback();
    } finally {
        foreach ($original as $name => $value) {
            if ($value === false) {
                putenv((string)$name);
                unset($_ENV[$name], $_SERVER[$name]);
                continue;
            }

            putenv("{$name}={$value}");
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

function expectEmailHtmlToBeXssSafe(string $body): void
{
    expect($body)
        ->not->toContain('<script')
        ->not->toContain('onerror=')
        ->not->toContain('onload=')
        ->not->toContain('javascript:')
        ->not->toContain('data:text/html');
}

it('sanitizes notification html content before it becomes an email body', function (): void {
    $form = formie()
        ->form(['title' => 'Email Rendering Security'])
        ->singleLineTextField('fullName')
        ->create();

    $submission = formie()->submission($form)->with([
        'fullName' => 'Security Tester',
    ])->save();

    $notification = new Notification([
        'name' => 'Security Email',
        'handle' => 'securityEmail' . uniqid(),
        'to' => 'recipient@example.test',
        'from' => 'sender@example.test',
        'subject' => 'Security Subject',
        'content' => MaliciousPayloads::storedXssProbe(),
    ]);

    $result = Formie::$plugin->getEmails()->renderEmail($notification, $submission);
    $body = (string)$result['email']->getSymfonyEmail()->getHtmlBody();

    expect($result)->not->toHaveKey('error')
        ->and($body)->toContain('safe-text');
    expectEmailHtmlToBeXssSafe($body);
})->group('security');

it('sanitizes single field reference content before it becomes an email body', function (): void {
    $form = formie()
        ->form(['title' => 'Email Single Field Variable Security'])
        ->singleLineTextField('fullName')
        ->create();

    $field = $form->getFieldByHandle('fullName');
    $submission = formie()->submission($form)->with([
        'fullName' => MaliciousPayloads::storedXssProbe(),
    ])->save();

    $notification = new Notification([
        'name' => 'Security Email Single Field',
        'handle' => 'securityEmailSingleField' . uniqid(),
        'to' => 'recipient@example.test',
        'from' => 'sender@example.test',
        'subject' => 'Security Subject',
        'content' => References::field((string)$field?->reference),
    ]);

    $result = Formie::$plugin->getEmails()->renderEmail($notification, $submission);
    $body = (string)$result['email']->getSymfonyEmail()->getHtmlBody();

    expect($result)->not->toHaveKey('error')
        ->and($body)->toContain('safe-text');
    expectEmailHtmlToBeXssSafe($body);
})->group('security');

it('sanitizes all-fields summary content before it becomes an email body', function (): void {
    $form = formie()
        ->form(['title' => 'Email All Fields Security'])
        ->singleLineTextField('fullName')
        ->create();

    $submission = formie()->submission($form)->with([
        'fullName' => MaliciousPayloads::storedXssProbe(),
    ])->save();

    $notification = new Notification([
        'name' => 'Security Email All Fields',
        'handle' => 'securityEmailAllFields' . uniqid(),
        'to' => 'recipient@example.test',
        'from' => 'sender@example.test',
        'subject' => 'Security Subject',
        'content' => '{allFields}',
    ]);

    $result = Formie::$plugin->getEmails()->renderEmail($notification, $submission);
    $body = (string)$result['email']->getSymfonyEmail()->getHtmlBody();

    expect($result)->not->toHaveKey('error')
        ->and($body)->toContain('<strong>FullName</strong>')
        ->and($body)->toContain('safe-text');
    expectEmailHtmlToBeXssSafe($body);
})->group('security');

it('sanitizes every all-fields style summary variable before it becomes an email body', function (string $summaryVariable): void {
    $form = formie()
        ->form(['title' => 'Email Summary Variable Matrix Security'])
        ->singleLineTextField('fullName')
        ->create();

    $submission = formie()->submission($form)->with([
        'fullName' => MaliciousPayloads::storedXssProbe(),
    ])->save();

    $notification = new Notification([
        'name' => 'Security Email Summary Matrix',
        'handle' => 'securityEmailSummaryMatrix' . uniqid(),
        'to' => 'recipient@example.test',
        'from' => 'sender@example.test',
        'subject' => 'Security Subject',
        'content' => $summaryVariable,
    ]);

    $result = Formie::$plugin->getEmails()->renderEmail($notification, $submission);
    $body = (string)$result['email']->getSymfonyEmail()->getHtmlBody();

    expect($result)->not->toHaveKey('error')
        ->and($body)->toContain('<strong>FullName</strong>')
        ->and($body)->toContain('safe-text');
    expectEmailHtmlToBeXssSafe($body);
})->with([
    '{allFields}',
    '{allContentFields}',
    '{allVisibleFields}',
])->group('security');

it('sanitizes rich and nested field summary html before it becomes an email body', function (): void {
    $payload = MaliciousPayloads::storedXssProbe();
    $optionPayload = '<img src=x onerror=alert("xss")>Safe Option';
    $rows = [[
        'fields' => [[
            'type' => SingleLineText::class,
            'handle' => 'innerText',
            'label' => 'Inner Text',
        ]],
    ]];

    $form = formie()
        ->form(['title' => 'Email Rich Summary Security'])
        ->singleLineTextField('fullName')
        ->multiLineTextField('bio', [
            'useRichText' => true,
        ])
        ->dropdownField('topic', [
            'options' => [
                ['label' => $optionPayload, 'value' => 'unsafe-option'],
            ],
        ])
        ->groupField('details', [
            'rows' => $rows,
        ])
        ->tableField('lineItems', [
            'columns' => [
                'description' => [
                    'heading' => $optionPayload,
                    'type' => 'singleline',
                ],
            ],
        ])
        ->create();

    $submission = formie()->submission($form)->with([
        'fullName' => $payload,
        'bio' => $payload,
        'topic' => 'unsafe-option',
        'details' => [
            'innerText' => $payload,
        ],
        'lineItems' => [[
            'description' => $payload,
        ]],
    ])->save();

    $notification = new Notification([
        'name' => 'Security Email Rich Summary',
        'handle' => 'securityEmailRichSummary' . uniqid(),
        'to' => 'recipient@example.test',
        'from' => 'sender@example.test',
        'subject' => 'Security Subject',
        'content' => "{allFields}\n{allContentFields}\n{allVisibleFields}",
    ]);

    $result = Formie::$plugin->getEmails()->renderEmail($notification, $submission);
    $body = (string)$result['email']->getSymfonyEmail()->getHtmlBody();

    expect($result)->not->toHaveKey('error')
        ->and($body)->toContain('safe-text')
        ->and($body)->toContain('Safe Option')
        ->and($body)->toContain('<strong>FullName</strong>')
        ->and($body)->toContain('<strong>Bio</strong>')
        ->and($body)->toContain('<strong>Topic</strong>')
        ->and($body)->toContain('<strong>Details</strong>')
        ->and($body)->toContain('<strong>LineItems</strong>');
    expectEmailHtmlToBeXssSafe($body);
})->group('security');

it('removes unsafe url protocols from notification email html attributes', function (): void {
    $form = formie()
        ->form(['title' => 'Email Unsafe Protocol Security'])
        ->singleLineTextField('fullName')
        ->create();

    $submission = formie()->submission($form)->with([
        'fullName' => 'Security Tester',
    ])->save();

    $notification = new Notification([
        'name' => 'Security Email Unsafe Protocols',
        'handle' => 'securityEmailUnsafeProtocols' . uniqid(),
        'to' => 'recipient@example.test',
        'from' => 'sender@example.test',
        'subject' => 'Security Subject',
        'content' => '<p><a href="javascript:alert(1)">Unsafe link</a><img src="data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg=="></p>',
    ]);

    $result = Formie::$plugin->getEmails()->renderEmail($notification, $submission);
    $body = (string)$result['email']->getSymfonyEmail()->getHtmlBody();

    expect($result)->not->toHaveKey('error')
        ->and($body)->toContain('Unsafe link')
        ->and($body)->not->toContain('href="javascript:')
        ->and($body)->not->toContain('src="data:text/html')
        ->and($body)->not->toContain('<script');
})->group('security');

it('filters reply-to display names before they become outbound email headers', function (): void {
    $form = formie()
        ->form(['title' => 'Email Reply-To Header Security'])
        ->singleLineTextField('fullName')
        ->create();

    $submission = formie()->submission($form)->with([
        'fullName' => 'Security Tester',
    ])->save();

    $notification = new Notification([
        'name' => 'Security Email Reply-To',
        'handle' => 'securityEmailReplyTo' . uniqid(),
        'to' => 'recipient@example.test',
        'from' => 'sender@example.test',
        'subject' => 'Security Subject',
        'replyTo' => 'reply@example.test',
        'replyToName' => " <script>alert('xss')</script><b>Reply Sender</b>\r\n",
        'content' => 'Hello',
    ]);

    $result = Formie::$plugin->getEmails()->renderEmail($notification, $submission);
    $replyTo = $result['email']->getReplyTo();
    $replyToName = $replyTo['reply@example.test'] ?? null;

    expect($result)->not->toHaveKey('error')
        ->and($replyToName)->toBe('Reply Sender')
        ->and($replyToName)->not->toContain('<script')
        ->and($replyToName)->not->toContain("\n");
})->group('security');

it('resolves env aliases authored in notification email settings', function (): void {
    withEmailEnvOverrides([
        'FORMIE_SECURITY_RECIPIENT' => 'recipient-env@example.test',
    ], function (): void {
        $form = formie()
            ->form(['title' => 'Email Authored Env Security'])
            ->singleLineTextField('fullName')
            ->create();

        $submission = formie()->submission($form)->with([
            'fullName' => 'Security Tester',
        ])->save();

        $notification = new Notification([
            'name' => 'Security Email Authored Env',
            'handle' => 'securityEmailAuthoredEnv' . uniqid(),
            'to' => '$FORMIE_SECURITY_RECIPIENT',
            'from' => 'sender@example.test',
            'subject' => 'Security Subject',
            'content' => 'Hello',
        ]);

        $result = Formie::$plugin->getEmails()->renderEmail($notification, $submission);

        expect($result)->not->toHaveKey('error')
            ->and($result['email']->getTo())->toHaveKey('recipient-env@example.test');
    });
})->group('security');

it('does not resolve env aliases supplied through notification reference values', function (): void {
    withEmailEnvOverrides([
        'FORMIE_SECURITY_SECRET' => 'leaked-secret-value',
    ], function (): void {
        $form = formie()
            ->form(['title' => 'Email Submitted Env Security'])
            ->singleLineTextField('fullName')
            ->create();

        $field = $form->getFieldByHandle('fullName');
        $submission = formie()->submission($form)->with([
            'fullName' => '$FORMIE_SECURITY_SECRET',
        ])->save();

        $notification = new Notification([
            'name' => 'Security Email Submitted Env',
            'handle' => 'securityEmailSubmittedEnv' . uniqid(),
            'to' => 'recipient@example.test',
            'from' => 'sender@example.test',
            'subject' => 'Subject ' . References::field((string)$field?->reference),
            'content' => 'Hello',
        ]);

        $result = Formie::$plugin->getEmails()->renderEmail($notification, $submission);

        expect($result)->not->toHaveKey('error')
            ->and($result['email']->getSubject())->toContain('$FORMIE_SECURITY_SECRET')
            ->and($result['email']->getSubject())->not->toContain('leaked-secret-value');
    });
})->group('security');

it('drops unsafe element urls from email field links while preserving labels', function (): void {
    $field = new FileUpload([
        'handle' => 'documents',
        'emailFieldSummaryValue' => 'url',
    ]);

    $value = new class {
        public function all(): array
        {
            return [
                new class {
                    public string $title = 'Quarterly Report';

                    public function getUrl(): string
                    {
                        return MaliciousPayloads::encodedJavascriptProtocolProbe();
                    }

                    public function getCpEditUrl(): string
                    {
                        return 'https://example.test/cp';
                    }
                },
            ];
        }
    };

    $html = renderEmailTemplate('formie/_special/email-template/fields/file-upload', [
        'field' => $field,
        'value' => $value,
    ]);

    expect($html)
        ->toContain('Quarterly Report')
        ->and($html)->not->toContain('href=')
        ->and($html)->not->toContain('javascript:');
})->group('security');
