<?php

declare(strict_types=1);

use craft\elements\db\ElementQueryInterface;
use verbb\formie\Formie;
use verbb\formie\models\{Report, ReportSettings, ScheduledReport, ScheduledReportDelivery};
use verbb\formie\services\{ReportExport, ReportScheduledDelivery};

it('preserves the scheduled cursor and removes temporary exports when delivery fails', function (string $failure): void {
    $report = new Report(['name' => 'Failure contract', 'handle' => 'failure' . uniqid()]);
    $report->setSettingsModel(new ReportSettings());
    expect(Formie::$plugin->getReports()->saveReport($report))->toBeTrue();
    $schedule = new ScheduledReport(['name' => 'Failure schedule ' . uniqid(), 'reportId' => $report->id]);
    $schedule->setDeliveryModel(ScheduledReportDelivery::fromArray(['recipients' => ['captured@example.test']]));
    expect(Formie::$plugin->getScheduledReports()->saveScheduledReport($schedule))->toBeTrue();
    Formie::$plugin->getScheduledReports()->markSent($schedule, new DateTime('2026-09-01'));
    $originalExporter = Formie::$plugin->getReportExport();
    $originalMailer = Craft::$app->getMailer();
    $exporter = new class extends ReportExport {
        public string $failure;
        public ?string $path = null;
        public function export(Report $report, string $format, ?ElementQueryInterface $query = null, int $chunkSize = self::DEFAULT_CHUNK_SIZE, ?array $columnOverride = null): array {
            if ($this->failure === 'export') { throw new RuntimeException('Synthetic export failure'); }
            if ($this->failure === 'missing') { return ['path' => '/nonexistent/formie-audit.csv']; }
            $this->path = tempnam(Craft::$app->getPath()->getTempPath(), 'delivery-failure-');
            file_put_contents($this->path, 'Synthetic private rows');
            return ['path' => $this->path, 'filename' => 'report.csv', 'mimeType' => 'text/csv'];
        }
    };
    $exporter->failure = $failure;
    $mailer = new class extends \craft\mail\Mailer {
        public int $calls = 0;
        public function send($message): bool { $this->calls++; throw new RuntimeException('Synthetic mailer failure'); }
    };
    $service = new class extends ReportScheduledDelivery {
        public string $failure;
        public function renderSummaryHtml(Report $report, ScheduledReport $scheduledReport, ScheduledReportDelivery $delivery, bool $testSend = false, ?array $exportResult = null): string {
            if ($this->failure === 'render') { throw new RuntimeException('Synthetic template failure'); }
            return '<p>Captured report</p>';
        }
    };
    $service->failure = $failure;
    Formie::$plugin->set('reportExport', $exporter);
    Craft::$app->set('mailer', $mailer);
    try {
        expect(fn() => $service->send($schedule))->toThrow(RuntimeException::class);
        expect((new \craft\db\Query())->select('lastSentAt')->from(\verbb\formie\helpers\Table::FORMIE_SCHEDULED_REPORTS)->where(['id' => $schedule->id])->scalar())->toBe('2026-09-01 00:00:00');
        expect($mailer->calls)->toBe($failure === 'mailer' ? 1 : 0);
        if ($exporter->path) { expect(is_file($exporter->path))->toBeFalse(); }
    } finally {
        Formie::$plugin->set('reportExport', $originalExporter);
        Craft::$app->set('mailer', $originalMailer);
        if ($exporter->path && is_file($exporter->path)) { unlink($exporter->path); }
    }
})->with(['export', 'missing', 'render', 'mailer']);

it('removes a partially written CSV when reading submissions fails', function (): void {
    $report = new Report(['name' => 'Partial export ' . uniqid(), 'handle' => 'partial' . uniqid()]);
    $report->setSettingsModel(new ReportSettings());
    $query = new class(\verbb\formie\elements\Submission::class) extends \verbb\formie\elements\db\SubmissionQuery {
        public function all($db = null): array { throw new RuntimeException('Synthetic database read failure'); }
    };
    $exporter = Formie::$plugin->getReportExport();
    $pattern = Craft::$app->getPath()->getTempPath() . '/' . $exporter->resolveBasename($report) . '-*.csv';
    expect(fn() => $exporter->export($report, 'csv', $query))->toThrow(RuntimeException::class, 'Synthetic database');
    expect(glob($pattern))->toBe([]);
});

it('removes a direct report export after the download response is sent', function (): void {
    $report = new Report(['name' => 'Direct cleanup ' . uniqid(), 'handle' => 'directCleanup' . uniqid()]);
    $report->setSettingsModel(new ReportSettings());
    expect(Formie::$plugin->getReports()->saveReport($report))->toBeTrue();
    \Tests\Support\WebRequestTestHelper::withWebRequestContext(function () use ($report): void {
        Craft::$app->getUser()->setIdentity(\craft\elements\User::find()->admin(true)->one());
        $level = ob_get_level();
        ob_start();
        $path = null;
        try {
            $response = (new \verbb\formie\controllers\ReportsController('reports', Formie::$plugin))->actionExport($report->id);
            $path = stream_get_meta_data($response->stream[0])['uri'];
            expect(is_file($path))->toBeTrue();
            fclose($response->stream[0]);
            $response->stream = null;
            $response->trigger(\craft\web\Response::EVENT_AFTER_SEND);
            expect(is_file($path))->toBeFalse();
        } finally {
            while (ob_get_level() > $level) { ob_end_clean(); }
            if ($path && is_file($path)) { unlink($path); }
        }
    });
});

it('retains submissions arriving during email delivery for the next incremental export', function (): void {
    $form = formie()->form()->singleLineTextField('message')->create();
    formie()->submission($form)->with(['message' => 'Already exported'])->save();
    $report = new Report(['name' => 'Delivery window', 'handle' => 'deliveryWindow' . uniqid()]);
    $report->setSettingsModel(ReportSettings::fromArray(['filters' => ['formIds' => [$form->id]]]));
    expect(Formie::$plugin->getReports()->saveReport($report))->toBeTrue();
    $schedule = new ScheduledReport(['name' => 'Window ' . uniqid(), 'reportId' => $report->id]);
    $schedule->setDeliveryModel(ScheduledReportDelivery::fromArray(['recipients' => ['captured@example.test']]));
    expect(Formie::$plugin->getScheduledReports()->saveScheduledReport($schedule))->toBeTrue();
    $originalMailer = Craft::$app->getMailer();
    $mailer = new class extends \craft\mail\Mailer {
        public $form;
        public ?int $arrivingId = null;
        public function send($message): bool {
            sleep(1);
            $this->arrivingId = (int)formie()->submission($this->form)->with(['message' => 'Arrived after export'])->save()->id;
            sleep(1);
            return true;
        }
    };
    $mailer->form = $form;
    $service = new class extends ReportScheduledDelivery {
        public function renderSummaryHtml(Report $report, ScheduledReport $scheduledReport, ScheduledReportDelivery $delivery, bool $testSend = false, ?array $exportResult = null): string {
            return '<p>Captured report</p>';
        }
    };
    Craft::$app->set('mailer', $mailer);
    try {
        expect($service->send($schedule))->toBeTrue();
        $persisted = Formie::$plugin->getScheduledReports()->getScheduledReportById($schedule->id);
        expect(array_map('intval', $service->buildSubmissionQuery($report, $persisted)->ids()))->toContain($mailer->arrivingId);
    } finally {
        Craft::$app->set('mailer', $originalMailer);
    }
});
