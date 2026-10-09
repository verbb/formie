<?php

declare(strict_types=1);

use Tests\Support\MaliciousPayloads;
use verbb\formie\Formie;
use verbb\formie\fields\FileUpload;
use verbb\formie\fields\SingleLineText;
use verbb\formie\helpers\References;
use verbb\formie\models\Notification;
use verbb\formie\references\ReferenceOutputContext;
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
    $document = new DOMDocument();
    @$document->loadHTML($body ?: '<html></html>');
    $xpath = new DOMXPath($document);
    expect($xpath->query('//script|//*[@onerror or @onload]')->length)->toBe(0);
    foreach ($xpath->query('//@href|//@src') as $attribute) {
        expect(strtolower($attribute->value))->not->toStartWith('javascript:')->not->toStartWith('data:text/html');
    }

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

it('keeps plain field links and images inert in every html summary', function (string $summaryVariable): void {
    $payload = '<a data-attacker-link href="https://evil.example/login">Verify account</a><img data-attacker-image src="https://evil.example/tracker.gif">';
    $form = formie()
        ->form(['title' => 'Email Summary Escaping Security'])
        ->singleLineTextField('fullName')
        ->multiLineTextField('message')
        ->create();
    $submission = formie()->submission($form)->with([
        'fullName' => $payload,
        'message' => $payload,
    ])->save();
    $summary = References::parseContent($summaryVariable, $submission, [
        'outputContext' => ReferenceOutputContext::Html,
    ]);
    $notification = new Notification([
        'name' => 'Security Email Escaped Summary',
        'handle' => 'securityEmailEscapedSummary' . uniqid(),
        'to' => 'recipient@example.test',
        'from' => 'sender@example.test',
        'subject' => 'Security Subject',
        'content' => $summaryVariable,
    ]);
    $result = Formie::$plugin->getEmails()->renderEmail($notification, $submission);
    $body = (string)$result['email']->getSymfonyEmail()->getHtmlBody();
    $document = new DOMDocument();
    @$document->loadHTML($body ?: '<html></html>');
    $xpath = new DOMXPath($document);

    expect($result)->not->toHaveKey('error')
        ->and($summary)->toContain('&lt;a')
        ->and($summary)->toContain('&lt;img')
        ->and($body)->toContain('Verify account')
        ->and($xpath->query('//a[@href="https://evil.example/login"] | //img[@src="https://evil.example/tracker.gif"]')->length)->toBe(0);
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
    expect($result)->toHaveKey('error');

})->group('security');

it('resolves env aliases authored in notification email settings', function (): void {
    withEmailEnvOverrides([
        'FORMIE_SECURITY_RECIPIENT' => 'recipient-env@example.test',
    ], function (): void {
        $originalAllowlist = Formie::$plugin->getSettings()->referenceEnvironmentAllowlist;
        Formie::$plugin->getSettings()->referenceEnvironmentAllowlist = ['FORMIE_SECURITY_RECIPIENT'];
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

        Formie::$plugin->getSettings()->referenceEnvironmentAllowlist = $originalAllowlist;
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
