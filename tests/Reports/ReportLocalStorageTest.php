<?php

declare(strict_types=1);

use craft\db\Query;
use craft\elements\User;
use craft\helpers\Json;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\Formie;
use verbb\formie\controllers\ReportsController;
use verbb\formie\controllers\ScheduledReportsController;
use verbb\formie\helpers\ProjectConfigHelper;
use verbb\formie\helpers\Table;
use verbb\formie\models\Report;
use verbb\formie\models\ReportSettings;
use verbb\formie\models\ScheduledReport;
use verbb\formie\models\ScheduledReportDelivery;
use verbb\formie\services\Permissions;
use yii\web\ForbiddenHttpException;

function reportStorageUser(array $grants = []): User
{
    $name = 'reportStorage' . bin2hex(random_bytes(6));
    $user = new User(['username' => $name, 'email' => $name . '@example.test']);
    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();
    Craft::$app->set('userPermissions', new \craft\services\UserPermissions());
    expect(Craft::$app->getUserPermissions()->saveUserPermissions($user->id, [
        'accessCp',
        'accessPlugin-formie',
        Permissions::PERM_ACCESS_SUBMISSIONS,
        Permissions::PERM_ACCESS_REPORTS,
        ...$grants,
    ]))->toBeTrue();

    return $user;
}

function newStoredReport(string $handle, array $filters = []): Report
{
    $report = new Report(['name' => ucfirst($handle), 'handle' => $handle]);
    $report->setSettingsModel(ReportSettings::fromArray(['filters' => $filters]));

    return $report;
}

function savedStoredReport(array $filters = []): Report
{
    $report = newStoredReport('stored' . uniqid(), $filters);
    expect(Formie::$plugin->getReports()->saveReport($report))->toBeTrue();

    return $report;
}

function saveStoredSchedule(Report $report): ScheduledReport
{
    $schedule = new ScheduledReport(['name' => 'Schedule ' . uniqid(), 'reportId' => $report->id]);
    $schedule->setDeliveryModel(ScheduledReportDelivery::fromArray(['recipients' => ['team@example.test'], 'frequency' => 'daily']));
    expect(Formie::$plugin->getScheduledReports()->saveScheduledReport($schedule))->toBeTrue();

    return $schedule;
}

// Production: project config is read-only, as it is with admin changes off.
function onProduction(callable $callback): mixed
{
    $general = Craft::$app->getConfig()->getGeneral();
    $projectConfig = Craft::$app->getProjectConfig();
    $originalAllow = $general->allowAdminChanges;
    $originalReadOnly = $projectConfig->readOnly;
    $general->allowAdminChanges = false;
    $projectConfig->readOnly = true;

    try {
        return $callback();
    } finally {
        $general->allowAdminChanges = $originalAllow;
        $projectConfig->readOnly = $originalReadOnly;
    }
}

function asReportUser(?User $user, callable $callback, string $method = 'POST'): mixed
{
    return WebRequestTestHelper::withWebRequestContext(function ($request) use ($user, $callback): mixed {
        $request->setIsCpRequest(true);
        $identity = $user ? User::find()->id($user->id)->one() : User::find()->admin(true)->one();
        Craft::$app->getUser()->setIdentity($identity);

        return $callback($identity, $request);
    }, ['method' => $method]);
}

it('saves, reloads and deletes reports with project config read-only', function (): void {
    $form = formie()->form()->singleLineTextField('message')->create();
    $before = Craft::$app->getProjectConfig()->get('formie.reports');

    onProduction(function () use ($form, $before): void {
        $reports = Formie::$plugin->getReports();
        $report = newStoredReport('production' . uniqid(), ['formIds' => [$form->id]]);

        expect($reports->saveReport($report))->toBeTrue()
            ->and($report->id)->toBeGreaterThan(0)
            ->and($reports->getReportById($report->id)->getSettingsModel()->filters['formIds'])->toBe([$form->id]);

        $report->name = 'Renamed on production';
        expect($reports->saveReport($report))->toBeTrue()
            ->and($reports->getReportById($report->id)->name)->toBe('Renamed on production')
            ->and($reports->deleteReport($report))->toBeTrue()
            ->and($reports->getReportById($report->id))->toBeNull()
            ->and(Craft::$app->getProjectConfig()->get('formie.reports'))->toBe($before);
    });
});

it('saves schedules with project config read-only and keeps their last send', function (): void {
    onProduction(function (): void {
        $schedules = Formie::$plugin->getScheduledReports();
        $schedule = saveStoredSchedule(savedStoredReport());
        $sentAt = new DateTime('2026-10-01 08:00:00');
        $schedules->markSent($schedule, $sentAt);

        $loaded = $schedules->getScheduledReportById($schedule->id);
        $loaded->name = 'Renamed schedule';
        expect($schedules->saveScheduledReport($loaded))->toBeTrue();

        $reloaded = $schedules->getScheduledReportById($schedule->id);
        expect($reloaded->name)->toBe('Renamed schedule')
            ->and($reloaded->lastSentAt?->format('Y-m-d'))->toBe('2026-10-01')
            ->and($reloaded->getDeliveryModel()->recipients)->toBe(['team@example.test'])
            ->and($schedules->deleteScheduledReport($reloaded))->toBeTrue()
            ->and($schedules->getScheduledReportById($schedule->id))->toBeNull();
    });
});

it('rejects a schedule for a report that does not exist', function (): void {
    $schedule = new ScheduledReport(['name' => 'Orphan ' . uniqid(), 'reportId' => 999999]);
    $schedule->setDeliveryModel(ScheduledReportDelivery::fromArray(['recipients' => ['team@example.test']]));

    expect(Formie::$plugin->getScheduledReports()->saveScheduledReport($schedule))->toBeFalse()
        ->and($schedule->getErrors('reportId'))->not->toBeEmpty();
});

it('ignores project config changes to reports', function (): void {
    $report = savedStoredReport();
    $reports = Formie::$plugin->getReports();
    $event = new craft\events\ConfigEvent(['tokenMatches' => [$report->uid], 'newValue' => ['name' => 'From YAML', 'handle' => 'fromYaml', 'sortOrder' => 1]]);
    $reports->handleChangedReport($event);
    $reports->handleDeletedReport($event);

    expect($reports->getReportById($report->id)->name)->toBe($report->name)
        ->and(ProjectConfigHelper::rebuildProjectConfig())->not->toHaveKey('reports')
        ->and(ProjectConfigHelper::rebuildProjectConfig())->not->toHaveKey('scheduledReports');
});

it('keeps old report entries through a rebuild so older environments never see them removed', function (): void {
    $projectConfig = Craft::$app->getProjectConfig();
    $reportEntry = ['name' => 'Deployed', 'handle' => 'deployed', 'sortOrder' => 1, 'settings' => ['filters' => ['formIds' => [5]]]];
    $scheduleEntry = ['name' => 'Deployed schedule', 'enabled' => true, 'delivery' => ['frequency' => 'daily']];
    $reportUid = craft\helpers\StringHelper::UUID();
    $scheduleUid = craft\helpers\StringHelper::UUID();
    $projectConfig->set('formie.reports.' . $reportUid, $reportEntry);
    $projectConfig->set('formie.scheduledReports.' . $scheduleUid, $scheduleEntry);

    try {
        $rebuilt = ProjectConfigHelper::rebuildProjectConfig();

        expect($rebuilt['reports'][$reportUid]['handle'])->toBe('deployed')
            ->and($rebuilt['reports'][$reportUid]['settings']['filters']['formIds'])->toBe([5])
            ->and($rebuilt['scheduledReports'][$scheduleUid]['name'])->toBe('Deployed schedule')
            ->and(Formie::$plugin->getReports()->getReportByUid($reportUid))->toBeNull();
    } finally {
        $projectConfig->remove('formie.reports.' . $reportUid);
        $projectConfig->remove('formie.scheduledReports.' . $scheduleUid);
    }
});

it('flags reports pointing at forms that no longer exist', function (): void {
    $form = formie()->form()->singleLineTextField('message')->create();
    $trashed = formie()->form()->singleLineTextField('message')->create();
    expect(Craft::$app->getElements()->deleteElement($trashed))->toBeTrue();
    $query = Formie::$plugin->getReportQuery();
    $present = savedStoredReport(['formIds' => [$form->id]]);
    $missing = savedStoredReport(['formIds' => [$form->id, 999999]]);
    $deleted = savedStoredReport(['formIds' => [$trashed->id]]);
    $every = savedStoredReport(['formIds' => '*']);

    expect($query->hasMissingForms($present))->toBeFalse()
        ->and($query->hasMissingForms($missing))->toBeTrue()
        ->and($query->hasMissingForms($deleted))->toBeTrue()
        ->and($query->hasMissingForms($every))->toBeFalse();

    asReportUser(null, function (User $admin) use ($present, $missing): void {
        $viewer = Formie::$plugin->getReportViewer();

        expect($viewer->getViewConfig($present, $admin)['hasMissingForms'])->toBeFalse()
            ->and($viewer->getViewConfig($missing, $admin)['hasMissingForms'])->toBeTrue()
            ->and(Formie::$plugin->getReportEditor()->getEditorConfig($missing)['hasMissingForms'])->toBeTrue();
    });
});

it('lets someone recreate a report with a deleted reports handle', function (): void {
    $reports = Formie::$plugin->getReports();
    $handle = 'reused' . uniqid();
    $first = newStoredReport($handle);
    expect($reports->saveReport($first))->toBeTrue();
    $live = newStoredReport($handle);
    expect($reports->saveReport($live))->toBeFalse()
        ->and($live->getErrors('handle'))->not->toBeEmpty()
        ->and($reports->getAllReportHandles())->toContain($handle);

    $reports->deleteReport($first);
    expect($reports->getAllReportHandles())->not->toContain($handle);

    $second = newStoredReport($handle);
    expect($reports->saveReport($second))->toBeTrue()
        ->and($reports->getReportByHandle($handle)->id)->toBe($second->id)
        ->and((new Query())->from(Table::FORMIE_REPORTS)->where(['id' => $first->id])->exists())->toBeFalse();
});

it('keeps firing report events in order', function (): void {
    $reports = Formie::$plugin->getReports();
    $seen = [];
    $record = function ($event) use (&$seen): void { $seen[] = $event->name; };

    foreach ([$reports::EVENT_BEFORE_SAVE_REPORT, $reports::EVENT_AFTER_SAVE_REPORT, $reports::EVENT_BEFORE_DELETE_REPORT, $reports::EVENT_BEFORE_APPLY_REPORT_DELETE, $reports::EVENT_AFTER_DELETE_REPORT] as $name) {
        $reports->on($name, $record);
    }

    try {
        $report = newStoredReport('events' . uniqid());
        $reports->saveReport($report);
        $reports->deleteReport($report);
    } finally {
        foreach ([$reports::EVENT_BEFORE_SAVE_REPORT, $reports::EVENT_AFTER_SAVE_REPORT, $reports::EVENT_BEFORE_DELETE_REPORT, $reports::EVENT_BEFORE_APPLY_REPORT_DELETE, $reports::EVENT_AFTER_DELETE_REPORT] as $name) {
            $reports->off($name, $record);
        }
    }

    expect($seen)->toBe(['beforeSaveReport', 'afterSaveReport', 'beforeDeleteReport', 'beforeApplyReportDelete', 'afterDeleteReport']);
});

it('lets a report manager create and edit on production, except reports with schedules', function (): void {
    $form = formie()->form()->singleLineTextField('message')->create();
    $manager = reportStorageUser([Permissions::PERM_MANAGE_REPORTS, Permissions::PERM_VIEW_SUBMISSIONS]);
    $editable = savedStoredReport(['formIds' => [$form->id]]);
    $scheduled = savedStoredReport(['formIds' => [$form->id]]);
    saveStoredSchedule($scheduled);

    onProduction(fn() => asReportUser($manager, function (User $identity, $request) use ($form, $editable, $scheduled): void {
        $permissions = Formie::$plugin->getPermissions();
        $viewer = Formie::$plugin->getReportViewer();

        expect($permissions->canEditReport($identity, $editable))->toBeTrue()
            ->and($permissions->canEditReport($identity, $scheduled))->toBeFalse()
            ->and($viewer->getDashboardConfig($identity)['canManageReports'])->toBeTrue()
            ->and($viewer->getViewConfig($editable, $identity)['canEdit'])->toBeTrue()
            ->and($viewer->getViewConfig($scheduled, $identity)['canEdit'])->toBeFalse()
            ->and(Formie::$plugin->getReportEditor()->getEditorConfig($scheduled)['canEdit'])->toBeFalse();

        $controller = new ReportsController('reports', Formie::$plugin);
        $request->setBodyParams(['name' => 'Client report', 'handle' => 'clientReport' . uniqid(), 'formIds' => [$form->id]]);
        $created = $controller->actionCreate()->data;

        expect($created['success'])->toBeTrue()
            ->and(Formie::$plugin->getReports()->getReportById($created['report']['id'])->getSettingsModel()->filters['formIds'])->toBe([$form->id]);

        foreach (['actionSave', 'actionDelete'] as $action) {
            $request->setBodyParams(['id' => $scheduled->id, 'name' => 'Changed', 'handle' => $scheduled->handle, 'settings' => Json::encode(['name' => 'Changed', 'handle' => $scheduled->handle])]);
            expect(fn() => $controller->$action())->toThrow(ForbiddenHttpException::class);
        }

        expect(Formie::$plugin->getReports()->getReportById($scheduled->id)->name)->toBe($scheduled->name);
    }));
});

it('keeps report changes behind the manage permission', function (): void {
    $viewer = reportStorageUser([Permissions::PERM_EXPORT_SUBMISSIONS]);
    $report = savedStoredReport();

    onProduction(fn() => asReportUser($viewer, function (User $identity) use ($report): void {
        $controller = new ReportsController('reports', Formie::$plugin);

        expect(Formie::$plugin->getPermissions()->canEditReport($identity, $report))->toBeFalse()
            ->and(fn() => $controller->beforeAction($controller->createAction('save')))->toThrow(ForbiddenHttpException::class)
            ->and(fn() => $controller->beforeAction($controller->createAction('delete')))->toThrow(ForbiddenHttpException::class);
    }, 'GET'));
});

it('limits schedule changes to administrators, including on production', function (): void {
    $manager = reportStorageUser([Permissions::PERM_MANAGE_REPORTS, Permissions::PERM_ACCESS_SETTINGS, Permissions::PERM_TEST_SCHEDULED_REPORTS]);

    onProduction(function () use ($manager): void {
        $permissions = Formie::$plugin->getPermissions();

        expect($permissions->canManageScheduledReports(new User(['admin' => true])))->toBeTrue();

        asReportUser($manager, function (User $identity) use ($permissions): void {
            $controller = new ScheduledReportsController('scheduled-reports', Formie::$plugin);

            expect($permissions->canManageScheduledReports($identity))->toBeFalse()
                ->and(fn() => $controller->actionEdit())->toThrow(ForbiddenHttpException::class);
        });
    });
});

it('keeps form choices within what a limited manager can view', function (): void {
    $visible = formie()->form()->settings(['usePerFormPermissions' => true])->singleLineTextField('message')->create();
    $other = formie()->form()->settings(['usePerFormPermissions' => true])->singleLineTextField('message')->create();
    $hidden = formie()->form()->settings(['usePerFormPermissions' => true])->singleLineTextField('message')->create();
    $permissions = Formie::$plugin->getPermissions();
    $manager = reportStorageUser([
        Permissions::PERM_MANAGE_REPORTS,
        $permissions->scopedPermission(Permissions::PERM_VIEW_SUBMISSIONS, $permissions->formScope($visible)),
        $permissions->scopedPermission(Permissions::PERM_VIEW_SUBMISSIONS, $permissions->formScope($other)),
    ]);

    asReportUser($manager, function (User $identity, $request) use ($visible, $other, $hidden): void {
        $editor = Formie::$plugin->getReportEditor();

        // A forged form is dropped, and a form the report already has is kept when hidden from them.
        expect($editor->resolveEditableFormIds([$visible->id, $hidden->id], [], $identity))->toBe([$visible->id])
            ->and($editor->resolveEditableFormIds([$other->id], [$visible->id, $hidden->id], $identity))->toBe([$other->id, $hidden->id])
            ->and($editor->resolveEditableFormIds('*', [], $identity))->toBe('*');

        $controller = new ReportsController('reports', Formie::$plugin);
        $request->setBodyParams(['name' => 'Forged', 'handle' => 'forged' . uniqid(), 'formIds' => [$hidden->id]]);
        expect($controller->actionCreate()->data['success'])->toBeFalse();
    });
});
