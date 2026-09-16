<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\console\controllers\{CronController, ReportsController};
use verbb\formie\models\{Report, ReportSettings, ScheduledReport, ScheduledReportDelivery};
use verbb\formie\services\{ReportScheduledDelivery, ScheduledReports};

it('skips overlapping and stale due selections across both scheduled commands', function (bool $failFirst): void {
    $form = formie()->form()->singleLineTextField('message')->create();
    formie()->submission($form)->with(['message' => 'One scheduled delivery'])->save();
    $report = new Report(['name' => 'Overlap', 'handle' => 'overlap' . uniqid()]);
    $report->setSettingsModel(ReportSettings::fromArray(['filters' => ['formIds' => [$form->id]]]));
    expect(Formie::$plugin->getReports()->saveReport($report))->toBeTrue();
    $schedule = new ScheduledReport(['name' => 'Overlap ' . uniqid(), 'reportId' => $report->id]);
    $schedule->setDeliveryModel(ScheduledReportDelivery::fromArray(['hour' => 0, 'frequency' => 'daily', 'recipients' => ['captured@example.test']]));
    expect(Formie::$plugin->getScheduledReports()->saveScheduledReport($schedule))->toBeTrue();
    $originalSchedules = Formie::$plugin->getScheduledReports();
    $originalDelivery = Formie::$plugin->getReportScheduledDelivery();
    $originalMailer = Craft::$app->getMailer();
    $schedules = new class extends ScheduledReports {
        public ScheduledReport $selected;
        public function getDueScheduledReports(?DateTime $now = null): array { return [clone $this->selected]; }
    };
    $schedules->selected = clone $schedule;
    $delivery = new class extends ReportScheduledDelivery {
        public function renderSummaryHtml(Report $report, ScheduledReport $scheduledReport, ScheduledReportDelivery $delivery, bool $testSend = false, ?array $exportResult = null): string { return '<p>Captured report</p>'; }
    };
    $mailer = new class extends \craft\mail\Mailer {
        public int $calls = 0;
        public bool $failFirst = false;
        public ScheduledReport $selected;
        public bool $overlapSent = true;
        public function send($message): bool {
            $this->calls++;
            if ($this->failFirst && $this->calls === 1) { throw new RuntimeException("Controlled mail failure"); }
            $this->overlapSent = Formie::$plugin->getReportScheduledDelivery()->sendIfDue(clone $this->selected);
            return true;
        }
    };
    $mailer->selected = clone $schedule;
    $mailer->failFirst = $failFirst;
    Formie::$plugin->set('scheduledReports', $schedules);
    Formie::$plugin->set('reportScheduledDelivery', $delivery);
    Craft::$app->set('mailer', $mailer);
    try {
        $first = new class('reports', Formie::$plugin) extends ReportsController {
            public string $out = '';
            public function stdout($string): int { $this->out .= $string; return strlen($string); }
        };
        if ($failFirst) {
            expect($first->actionRunScheduled())->toBe(1);
            expect($schedules->getFreshScheduledReportById($schedule->id)->lastSentAt)->toBeNull();
            $first->out = '';
        }
        expect($first->actionRunScheduled())->toBe(0);
        expect($first->out)->toContain('Completed: 1 sent, 0 failed.');
        expect($mailer->overlapSent)->toBeFalse();
        expect($mailer->calls)->toBe($failFirst ? 2 : 1);
        $second = new class('cron', Formie::$plugin) extends CronController {
            public string $out = '';
            public function stdout($string): int { $this->out .= $string; return strlen($string); }
        };
        $second->only = 'reports';
        expect($second->actionRun())->toBe(0);
        expect($second->out)->toContain('Skipped.', 'Completed: 0 sent, 0 failed.');
        expect($mailer->calls)->toBe($failFirst ? 2 : 1);
        expect($schedules->getScheduledReportById($schedule->id)->lastSentAt)->not->toBeNull();
    } finally {
        Formie::$plugin->set('scheduledReports', $originalSchedules);
        Formie::$plugin->set('reportScheduledDelivery', $originalDelivery);
        Craft::$app->set('mailer', $originalMailer);
    }
})->with(['successful delivery' => false, 'retry after failure' => true]);

it('rechecks a disabled or deleted schedule before delivering a stale selection', function (string $state): void {
    $report = new Report(['name' => 'Changed schedule', 'handle' => 'changedSchedule' . uniqid()]);
    $report->setSettingsModel(new ReportSettings());
    expect(Formie::$plugin->getReports()->saveReport($report))->toBeTrue();
    $schedule = new ScheduledReport(['name' => 'Changed ' . uniqid(), 'reportId' => $report->id]);
    $schedule->setDeliveryModel(ScheduledReportDelivery::fromArray(['hour' => 0, 'frequency' => 'daily', 'recipients' => ['captured@example.test']]));
    $schedules = Formie::$plugin->getScheduledReports();
    expect($schedules->saveScheduledReport($schedule))->toBeTrue();
    $selected = clone $schedules->getScheduledReportById($schedule->id);
    \craft\helpers\Db::update(\verbb\formie\helpers\Table::FORMIE_SCHEDULED_REPORTS, $state === 'disabled' ? ['enabled' => false] : ['dateDeleted' => '2026-09-01 00:00:00'], ['id' => $schedule->id]);
    $delivery = new class extends ReportScheduledDelivery {
        public function send(ScheduledReport $scheduledReport, bool $testSend = false, ?\craft\elements\User $triggeredBy = null): bool { throw new LogicException('Ineligible schedule reached delivery.'); }
    };
    expect($delivery->sendIfDue($selected))->toBeFalse();
})->with(['disabled', 'deleted']);
