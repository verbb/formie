<?php

declare(strict_types=1);

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Monolog\Handler\TestHandler;
use Monolog\Logger as MonologLogger;
use Psr\Log\LogLevel;
use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\controllers\SupportController;
use verbb\formie\integrations\crm\ActiveCampaign;
use verbb\formie\log\SubmissionLogTarget;
use verbb\formie\models\IntegrationExecutionContext;
use verbb\formie\models\Support;
use verbb\formie\services\Forms;
use yii\log\Logger;

beforeEach(function (): void {
    $this->originalPost = $_POST;
    $this->originalGet = $_GET;
    $this->originalForms = Formie::$plugin->getForms();
});

afterEach(function (): void {
    $_POST = $this->originalPost;
    $_GET = $this->originalGet;
    Formie::$plugin->set('forms', $this->originalForms);
    Formie::$plugin->getSettings()->logSubmissionValues = true;
});

function logPrivacyForm(): \verbb\formie\elements\Form
{
    return formie()
        ->form(['title' => 'Log Privacy'])
        ->singleLineTextField('visibleValue')
        ->singleLineTextField('privateValue', ['enableContentEncryption' => true])
        ->groupField('details', ['rows' => [['fields' => [
            ['type' => \verbb\formie\fields\SingleLineText::class, 'label' => 'Nested Private', 'handle' => 'nestedPrivate', 'enableContentEncryption' => true],
            ['type' => \verbb\formie\fields\SingleLineText::class, 'label' => 'Nested Visible', 'handle' => 'nestedVisible'],
        ]]]])
        ->create();
}

/** Exports one Formie message through a real target and returns everything it wrote. */
function logPrivacyExport(): string
{
    $handler = new TestHandler();
    $target = new SubmissionLogTarget([
        'name' => 'formie',
        'categories' => ['verbb\\formie\\*'],
        'level' => LogLevel::INFO,
        'allowLineBreaks' => true,
        'logVars' => ['_GET', '_POST'],
        'logger' => new MonologLogger('formie', [$handler]),
    ]);
    $target->messages = [['Submission triggered.', Logger::LEVEL_INFO, 'verbb\\formie\\Formie::info', microtime(true), [], memory_get_usage()]];
    $target->export();

    return implode("\n", array_map(static fn($record) => $record->message, $handler->getRecords()));
}

function logPrivacyMessages(callable $callback): string
{
    $offset = count(Craft::getLogger()->messages);
    $callback();

    return implode("\n", array_filter(array_column(array_slice(Craft::getLogger()->messages, $offset), 0), 'is_string'));
}

it('replaces the plugin log target registered by Base and writes masked values to the log file', function (): void {
    $dispatcher = Craft::getLogger()->dispatcher;
    $originalTargets = $dispatcher->targets;

    try {
        Formie::config();
        $target = $dispatcher->targets['verbb\\formie\\*'] ?? null;

        expect($target)->toBeInstanceOf(SubmissionLogTarget::class)
            ->and(Formie::$plugin->getSettings()->logSubmissionValues)->toBeTrue();

        Formie::$plugin->getSettings()->logSubmissionValues = false;
        $_POST = ['action' => 'formie/submissions/submit', 'fields' => ['email' => 'file-person@example.com']];
        $target->messages = [['Submission triggered for file test.', Logger::LEVEL_INFO, 'verbb\\formie\\Formie::info', microtime(true), [], memory_get_usage()]];
        $target->export();

        $files = glob(Craft::$app->getPath()->getLogPath() . '/formie-*.log');
        usort($files, static fn($a, $b) => filemtime($b) <=> filemtime($a));
        $contents = file_get_contents($files[0]);

        expect($contents)->toContain('Submission triggered for file test.')
            ->toContain("'action' => 'formie/submissions/submit'")
            ->toContain("'email' => '<string:23>'")
            ->not->toContain('file-person@example.com');
    } finally {
        $dispatcher->targets = $originalTargets;
    }
});

it('keeps submitted values in the request context by default while withholding encrypted fields', function (): void {
    $form = logPrivacyForm();
    $_GET = ['source' => 'newsletter'];
    $_POST = [
        'action' => 'formie/submissions/submit',
        'handle' => $form->handle,
        'fields' => [
            'visibleValue' => 'ordinary-answer',
            'privateValue' => 'encrypted-answer',
            'details' => ['nestedPrivate' => 'nested-encrypted-answer', 'nestedVisible' => 'nested-ordinary-answer'],
        ],
    ];
    $post = $_POST;
    $output = logPrivacyExport();

    expect($output)->toContain('Request context:')
        ->toContain('ordinary-answer')
        ->toContain('nested-ordinary-answer')
        ->toContain('newsletter')
        ->toContain("'privateValue' => '<encrypted>'")
        ->toContain("'nestedPrivate' => '<encrypted>'")
        ->not->toContain('encrypted-answer')
        ->and($_POST)->toBe($post);
});

it('keeps the request shape and control parameters when submitted values are opted out', function (): void {
    Formie::$plugin->getSettings()->logSubmissionValues = false;
    $form = logPrivacyForm();
    $_GET = ['email' => 'prefilled@example.com', 'siteId' => '2'];
    $_POST = [
        'action' => 'formie/submissions/submit',
        'handle' => $form->handle,
        'pageId' => '12',
        'customParam' => 'custom-answer',
        'fields' => [
            'visibleValue' => 'ordinary-answer',
            'blankValue' => '',
            'privateValue' => 'encrypted-answer',
            'list' => ['first-choice', 'second-choice'],
            'details' => ['nestedVisible' => 'nested-ordinary-answer'],
        ],
    ];
    $output = logPrivacyExport();

    expect($output)->toContain("'action' => 'formie/submissions/submit'")
        ->toContain("'handle' => '{$form->handle}'")
        ->toContain("'pageId' => '12'")
        ->toContain("'siteId' => '2'")
        ->toContain("'visibleValue' => '<string:15>'")
        ->toContain("'blankValue' => '<empty>'")
        ->toContain("'customParam' => '<string:13>'")
        ->toContain("'email' => '<string:21>'")
        ->toContain("'nestedVisible' => '<string:22>'")
        ->not->toContain('ordinary-answer')
        ->not->toContain('encrypted-answer')
        ->not->toContain('first-choice')
        ->not->toContain('custom-answer')
        ->not->toContain('prefilled@example.com');
});

it('masks every submitted value when the form cannot be inspected during log export', function (): void {
    Formie::$plugin->set('forms', new class() extends Forms {
        public function getFormByHandle(string $handle, int $siteId = null): ?\verbb\formie\elements\Form
        {
            throw new RuntimeException('Database unavailable');
        }
    });
    $_POST = ['handle' => 'anyForm', 'fields' => ['visibleValue' => 'ordinary-answer']];

    expect(logPrivacyExport())->toContain("'visibleValue' => '<string:15>'")
        ->toContain("'handle' => 'anyForm'")
        ->not->toContain('ordinary-answer');
});

it('leaves request parameters unchanged for forms without encrypted fields and for other requests', function (): void {
    $form = formie()->form(['title' => 'No Encryption'])->singleLineTextField('visibleValue')->create();
    $_POST = ['handle' => $form->handle, 'fields' => ['visibleValue' => 'ordinary-answer']];
    expect(logPrivacyExport())->toContain('ordinary-answer');

    $_POST = ['entryId' => '4', 'fields' => ['title' => 'entry-content']];
    expect(logPrivacyExport())->toContain('entry-content');
});

it('always masks raw request bodies written to the log context', function (): void {
    $handler = new TestHandler();
    $logger = new MonologLogger('formie', [$handler]);
    new SubmissionLogTarget(['name' => 'formie', 'logger' => $logger]);

    $logger->info('Request context:', ['body' => '{"variables":{"email":"person@example.com"}}']);
    $logger->info('Request context:', ['body' => 'plain=person@example.com']);

    expect($handler->getRecords()[0]->context['body'])->toBe('{"variables":{"email":"<string:18>"}}')
        ->and($handler->getRecords()[1]->context['body'])->toBe('<string:24>');
});

it('never writes submitted values to spam log entries', function (bool $logValues): void {
    Formie::$plugin->getSettings()->logSubmissionValues = $logValues;
    $form = logPrivacyForm();
    $submission = formie()->submission($form)->with([
        'visibleValue' => 'ordinary-answer',
        'privateValue' => 'encrypted-answer',
    ])->save();
    $submission->spamReason = 'Honeypot';

    $output = logPrivacyMessages(fn() => Formie::$plugin->getSubmissions()->logSpam($submission));

    expect($output)->toContain('Honeypot')
        ->not->toContain('ordinary-answer')
        ->not->toContain('encrypted-answer');
})->with([true, false]);

it('keeps integration payloads by default and masks them when submitted values are opted out', function (): void {
    $integration = new ActiveCampaign(['name' => 'ActiveCampaign']);
    // A translated label must not defeat masking, so nothing depends on the English wording.
    $message = 'Fehlende Antwort {"id":null}. Gesendete Nutzlast {"email":"person@example.com","tags":["vip"],"age":42}';

    expect(logPrivacyMessages(fn() => Integration::error($integration, $message)))->toContain('person@example.com');

    Formie::$plugin->getSettings()->logSubmissionValues = false;
    $output = logPrivacyMessages(function() use ($integration, $message): void {
        Integration::info($integration, $message);
        Integration::warning($integration, $message);
        Integration::error($integration, $message);
    });

    expect($output)->toContain('Gesendete Nutzlast {"email":"<string:18>","tags":["<string:3>"],"age":"<number>"}')
        ->not->toContain('person@example.com')
        ->not->toContain('vip');
});

it('masks integration payloads for forms with encrypted fields even when values are logged', function (): void {
    $form = logPrivacyForm();
    $plainForm = formie()->form(['title' => 'No Encryption'])->singleLineTextField('visibleValue')->create();
    $integration = new ActiveCampaign(['name' => 'ActiveCampaign']);
    $message = 'Sent payload {"email":"person@example.com"}';

    $integration->setDeliveryContext(new IntegrationExecutionContext(1, (int)$plainForm->id, 'crm', 'uid-1'), 'attempt-1');
    expect(logPrivacyMessages(fn() => Integration::error($integration, $message)))->toContain('person@example.com');

    $integration->setDeliveryContext(new IntegrationExecutionContext(1, (int)$form->id, 'crm', 'uid-2'), 'attempt-2');
    expect(logPrivacyMessages(fn() => Integration::error($integration, $message)))->toContain('"email":"<string:18>"')
        ->not->toContain('person@example.com');
});

it('masks provider JSON in API errors and truncated JSON that cannot be parsed', function (): void {
    Formie::$plugin->getSettings()->logSubmissionValues = false;
    $integration = new ActiveCampaign(['name' => 'ActiveCampaign']);
    $exception = new RequestException('Failed', new Request('POST', 'https://example.com'), new Response(422, [], '{"error":"person@example.com is taken"}'));

    $output = logPrivacyMessages(fn() => Integration::apiError($integration, $exception, false));

    expect($output)->toContain('HTTP 422')
        ->toContain('"error":"<string:')
        ->not->toContain('person@example.com');

    $truncated = logPrivacyMessages(fn() => Integration::error($integration, 'Response {"email":"person@example.com","notes":"long...'));

    expect($truncated)->toContain('Response')
        ->not->toContain('person@example.com');
});

it('leaves braces that are not JSON untouched when masking integration messages', function (): void {
    Formie::$plugin->getSettings()->logSubmissionValues = false;
    $integration = new ActiveCampaign(['name' => 'ActiveCampaign']);

    expect(logPrivacyMessages(fn() => Integration::error($integration, 'Unknown {placeholder} in [template]')))
        ->toContain('Unknown {placeholder} in [template]');
});

it('attaches Formie logs to support requests unless they are excluded', function (): void {
    $logPath = Craft::$app->getPath()->getLogPath();
    $logFile = $logPath . '/formie-privacy-test.log';
    $otherFile = $logPath . '/web-privacy-test.log';
    file_put_contents($logFile, 'formie');
    file_put_contents($otherFile, 'web');
    $zipPath = tempnam(sys_get_temp_dir(), 'formie-support');

    $controller = new SupportController('formie-support-logs', Craft::$app);
    $method = new ReflectionMethod(SupportController::class, '_addLogFiles');

    $names = function(Support $support) use ($controller, $method, $zipPath): array {
        @unlink($zipPath);
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE);
        // Keeps an otherwise empty archive valid so it can be reopened and listed.
        $zip->addFromString('note.txt', 'note');
        $method->invoke($controller, $zip, $support);
        $zip->close();

        $zip->open($zipPath);
        $found = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $found[] = $zip->getNameIndex($i);
        }

        $zip->close();

        return $found;
    };

    try {
        $support = new Support();

        expect($support->includeLogs)->toBeTrue()
            ->and($names($support))->toContain('logs/formie-privacy-test.log')
            ->not->toContain('logs/web-privacy-test.log');

        $support->includeLogs = false;

        expect($names($support))->toBe(['note.txt']);
    } finally {
        @unlink($logFile);
        @unlink($otherFile);
        @unlink($zipPath);
    }
});
