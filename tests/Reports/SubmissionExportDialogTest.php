<?php

declare(strict_types=1);

use craft\elements\User;
use craft\helpers\Db;
use craft\helpers\Json;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\Formie;
use verbb\formie\controllers\ReportsController;
use verbb\formie\helpers\Table;
use verbb\formie\models\Report;
use verbb\formie\models\ReportSettings;
use verbb\formie\services\Permissions;
use yii\mail\BaseMailer;
use yii\web\ForbiddenHttpException;

function submissionExportUser(array $grants, bool $reportsAccess = true): User
{
    $name = 'submissionExport' . bin2hex(random_bytes(6));
    $user = new User(['username' => $name, 'email' => $name . '@example.test']);
    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();
    Craft::$app->set('userPermissions', new \craft\services\UserPermissions());
    // Keep Reports access optional to exercise the submissions-only grant as Craft saves it.
    expect(Craft::$app->getUserPermissions()->saveUserPermissions($user->id, [
        'accessCp',
        'accessPlugin-formie',
        Permissions::PERM_ACCESS_SUBMISSIONS,
        ...($reportsAccess ? [Permissions::PERM_ACCESS_REPORTS] : []),
        ...$grants,
    ]))->toBeTrue();

    return $user;
}

// Site access and form permissions read the signed-in user, so run as one in a CP request.
function asSubmissionExporter(callable $callback, ?User $user = null, string $method = 'POST'): mixed
{
    return WebRequestTestHelper::withWebRequestContext(function ($request) use ($callback, $user): mixed {
        $request->setIsCpRequest(true);
        $identity = $user ? User::find()->id($user->id)->one() : User::find()->admin(true)->one();
        Craft::$app->getUser()->setIdentity($identity);

        return $callback($identity, $request);
    }, ['method' => $method]);
}

function submissionExportCount(array $params, array $viewer = []): int
{
    $report = Formie::$plugin->getReportExport()->createSubmissionExportReport($params);

    return Formie::$plugin->getReportExport()->getExportRowCount(Formie::$plugin->getReportQuery()->buildViewerQuery($report, null, $viewer));
}

function submissionExportCsv(array $params, ?array $columns = null, array $viewer = []): array
{
    $service = Formie::$plugin->getReportExport();
    $report = $service->createSubmissionExportReport($params);
    $query = Formie::$plugin->getReportQuery()->buildViewerQuery($report, null, $viewer);
    $export = $service->export($report, 'csv', $query, columnOverride: $service->getSubmissionExportColumns($report, $columns, Craft::$app->getUser()->getIdentity()));

    try {
        $contents = preg_replace('/^\xEF\xBB\xBF/', '', (string)file_get_contents($export['path']));
        $rows = array_map('str_getcsv', array_values(array_filter(explode("\n", $contents))));
    } finally {
        unlink($export['path']);
    }

    return ['filename' => $export['filename'], 'headers' => array_shift($rows), 'rows' => $rows];
}

it('allows queued exports without a saved report', function (): void {
    $column = Craft::$app->getDb()->getTableSchema(Table::FORMIE_REPORT_EXPORTS, true)->getColumn('reportId');

    expect($column->allowNull)->toBeTrue();
});

it('exports every matching submission with no saved report or date limit', function (): void {
    $form = formie()->form()->singleLineTextField('message')->create();
    $old = formie()->submission($form)->with(['message' => 'Old answer'])->save();
    formie()->submission($form)->with(['message' => 'New answer'])->save();
    // A saved report defaults to the last month. An index export must not.
    Db::update('{{%elements}}', ['dateCreated' => '2019-01-01 00:00:00'], ['id' => $old->id]);
    $reportCount = count(Formie::$plugin->getReports()->getAllReports());

    asSubmissionExporter(function () use ($form, $reportCount): void {
        $export = submissionExportCsv(['formIds' => [$form->id]]);
        $messages = array_column($export['rows'], array_search('Message', $export['headers'], true));
        sort($messages);

        expect($messages)->toBe(['New answer', 'Old answer'])
            ->and($export['filename'])->toStartWith($form->handle . '-submissions-')
            ->and(Formie::$plugin->getReports()->getAllReports())->toHaveCount($reportCount);
    });
});

it('counts the same submissions it exports for each submission type', function (array $flags, array $expected): void {
    $form = formie()->form()->singleLineTextField('message')->create();

    foreach (['complete' => [false, false], 'incomplete' => [true, false], 'spam' => [false, true], 'incompleteSpam' => [true, true]] as $name => [$incomplete, $spam]) {
        $submission = formie()->submission($form)->with(['message' => $name])->save();
        Db::update('{{%formie_submissions}}', ['isIncomplete' => $incomplete, 'isSpam' => $spam], ['id' => $submission->id]);
    }

    asSubmissionExporter(function () use ($form, $flags, $expected): void {
        $params = array_merge(['formIds' => [$form->id], 'includeComplete' => false, 'includeIncomplete' => false, 'includeSpam' => false], $flags);
        $export = submissionExportCsv($params);
        $messages = array_column($export['rows'], array_search('Message', $export['headers'], true));
        sort($messages);
        sort($expected);

        expect(submissionExportCount($params))->toBe(count($expected))->and($messages)->toBe($expected);
    });
})->with([
    'complete' => [['includeComplete' => true], ['complete']],
    'incomplete' => [['includeIncomplete' => true], ['incomplete']],
    'spam' => [['includeSpam' => true], ['spam', 'incompleteSpam']],
    'combined from form data' => [['includeComplete' => '1', 'includeIncomplete' => 'true', 'includeSpam' => '0'], ['complete', 'incomplete']],
    'all' => [['includeComplete' => true, 'includeIncomplete' => true, 'includeSpam' => true], ['complete', 'incomplete', 'spam', 'incompleteSpam']],
    'none' => [[], []],
]);

it('applies dates, statuses and sites', function (): void {
    $form = formie()->form()->singleLineTextField('message')->create();
    $first = formie()->submission($form)->with(['message' => 'First answer'])->save();
    $second = formie()->submission($form)->with(['message' => 'Second answer'])->save();
    Db::update('{{%elements}}', ['dateCreated' => '2019-01-15 00:00:00'], ['id' => $first->id]);
    $statuses = Formie::$plugin->getSubmissionStatuses()->getAllStatuses();
    $other = null;

    if (count($statuses) > 1) {
        $other = $statuses[0]->id === $second->statusId ? $statuses[1] : $statuses[0];
        Db::update('{{%formie_submissions}}', ['statusId' => $other->id], ['id' => $second->id]);
    }

    asSubmissionExporter(function () use ($form, $first, $other): void {
        $params = ['formIds' => [$form->id]];

        expect(submissionExportCount($params))->toBe(2)
            ->and(submissionExportCount($params, ['startDate' => '2019-01-01 00:00:00', 'endDate' => '2019-01-31 23:59:59']))->toBe(1);

        if ($other) {
            expect(submissionExportCount([...$params, 'statusIds' => [(string)$other->id]]))->toBe(1)
                ->and(submissionExportCount([...$params, 'statusIds' => Json::encode([$other->id])]))->toBe(1);
        }

        expect(submissionExportCount([...$params, 'siteId' => (string)$first->siteId]))->toBe(2);

        foreach (Craft::$app->getSites()->getAllSiteIds() as $siteId) {
            if ((int)$siteId !== (int)$first->siteId) {
                expect(submissionExportCount([...$params, 'siteId' => (string)$siteId]))->toBe(0);
            }
        }
    });
});

it('limits exports to the sites a user can edit', function (): void {
    $form = formie()->form()->singleLineTextField('message')->create();
    formie()->submission($form)->with(['message' => 'Answer'])->save();
    $user = submissionExportUser([Permissions::PERM_EXPORT_SUBMISSIONS, Permissions::PERM_VIEW_SUBMISSIONS]);

    // Craft has no per-site grants to check on a single-site install: its only site is always editable.
    $multiSite = Craft::$app->getIsMultiSite();
    $editableSiteIds = $multiSite ? [] : [(int)Craft::$app->getSites()->getPrimarySite()->id];

    asSubmissionExporter(function () use ($form, $multiSite, $editableSiteIds): void {
        expect(array_map('intval', Craft::$app->getSites()->getEditableSiteIds()))->toBe($editableSiteIds)
            ->and(submissionExportCount(['formIds' => [$form->id]]))->toBe($multiSite ? 0 : 1);
    }, $user);
});

it('keeps requested columns in order and ignores unavailable ones', function (): void {
    $form = formie()->form()->singleLineTextField('message')->singleLineTextField('secret')->create();
    formie()->submission($form)->with(['message' => 'Hello', 'secret' => 'Hidden'])->save();

    asSubmissionExporter(function () use ($form): void {
        $export = submissionExportCsv(['formIds' => [$form->id]], [
            ['type' => 'field', 'handle' => 'message', 'label' => 'Forged label', 'enabled' => true],
            ['type' => 'attribute', 'handle' => 'title', 'enabled' => true],
            ['type' => 'field', 'handle' => 'secret', 'enabled' => false],
            ['type' => 'field', 'handle' => 'notAField', 'enabled' => true],
            ['type' => 'attribute', 'handle' => 'notAnAttribute', 'enabled' => true],
        ]);

        expect($export['headers'])->toBe(['Message', 'Title'])
            ->and($export['rows'][0][0])->toBe('Hello');
    });
});

it('only exports forms the user can view', function (): void {
    $allowed = formie()->form()->settings(['usePerFormPermissions' => true])->singleLineTextField('allowedField')->create();
    $denied = formie()->form()->settings(['usePerFormPermissions' => true])->singleLineTextField('deniedField')->create();
    formie()->submission($allowed)->with(['allowedField' => 'Allowed value'])->save();
    formie()->submission($denied)->with(['deniedField' => 'Denied value'])->save();
    $permissions = Formie::$plugin->getPermissions();
    $user = submissionExportUser([
        Permissions::PERM_EXPORT_SUBMISSIONS,
        'editSite:' . Craft::$app->getSites()->getPrimarySite()->uid,
        $permissions->scopedPermission(Permissions::PERM_VIEW_SUBMISSIONS, $permissions->formScope($allowed)),
    ]);

    asSubmissionExporter(function (User $identity) use ($allowed, $denied): void {
        $service = Formie::$plugin->getReportExport();
        $report = fn(mixed $formIds) => $service->createSubmissionExportReport(['formIds' => $formIds]);
        $handles = fn(mixed $formIds) => array_column($service->getSubmissionExportColumns($report($formIds), null, $identity), 'handle');

        foreach (['*', [$allowed->id, $denied->id]] as $formIds) {
            $export = submissionExportCsv(['formIds' => $formIds]);

            expect($handles($formIds))->toContain('allowedField')->not->toContain('deniedField')
                ->and($export['rows'])->toHaveCount(1)
                ->and(implode(',', $export['rows'][0]))->toContain('Allowed value');
        }

        $forged = submissionExportCsv(['formIds' => [$denied->id]], [
            ['type' => 'field', 'handle' => 'deniedField', 'enabled' => true],
        ]);

        // No rows, no field column, and no form name in the filename or ready email.
        expect($forged['rows'])->toBe([])
            ->and($handles([$denied->id]))->not->toContain('deniedField')
            ->and($forged['filename'])->not->toContain($denied->handle)
            ->and($report([$denied->id])->name)->not->toBe($denied->title);
    }, $user);
});

it('runs queued exports from the stored report for anyone allowed to export', function (string $grant, bool $allowed): void {
    $form = formie()->form()->singleLineTextField('message')->create();
    formie()->submission($form)->with(['message' => 'Queued value'])->save();
    $old = formie()->submission($form)->with(['message' => 'Excluded value'])->save();
    Db::update('{{%elements}}', ['dateCreated' => '2019-01-01 00:00:00'], ['id' => $old->id]);
    $user = submissionExportUser(array_filter([
        Permissions::PERM_VIEW_SUBMISSIONS,
        'editSite:' . Craft::$app->getSites()->getPrimarySite()->uid,
        $grant,
    ]), $grant !== Permissions::PERM_EXPORT_SUBMISSIONS_FROM_INDEX);
    $service = Formie::$plugin->getReportExport();
    $files = Formie::$plugin->getReportExportFiles();

    // Queue the export as an administrator, then run it the way a worker does: with no signed-in user.
    $exportFile = asSubmissionExporter(function (User $admin) use ($form, $service, $files, $user) {
        $report = $service->createSubmissionExportReport(['formIds' => [$form->id]]);
        $viewer = ['startDate' => '2020-01-01 00:00:00', 'endDate' => null];
        $columns = $service->getSubmissionExportColumns($report, [['type' => 'field', 'handle' => 'message', 'enabled' => true]], $admin);

        return $files->createPending($report, 'csv', $service->buildExportContext($viewer, $columns, report: $report), user: $user);
    });
    $exportFile = $files->getExportFileById($exportFile->id);

    expect($exportFile->reportId)->toBeNull()
        ->and($exportFile->context['report']['handle'])->toBe($form->handle);

    try {
        if (!$allowed) {
            expect(fn() => $service->runQueuedExport($exportFile))->toThrow(RuntimeException::class, 'permission');

            return;
        }

        $result = $service->runQueuedExport($exportFile);
        $contents = (string)file_get_contents($result['path']);

        expect($contents)->toContain('Queued value')->not->toContain('Excluded value')
            ->and($result['filename'])->toStartWith($form->handle . '-submissions-');
        unlink($result['path']);
    } finally {
        $files->deleteExportFile($exportFile);
    }
})->with([
    'export grant' => [Permissions::PERM_EXPORT_SUBMISSIONS, true],
    'submissions-only grant' => [Permissions::PERM_EXPORT_SUBMISSIONS_FROM_INDEX, true],
    'report manager' => [Permissions::PERM_MANAGE_REPORTS, true],
    'no export grant' => ['', false],
]);

it('sends the ready email for an export without a saved report', function (): void {
    $form = formie()->form()->singleLineTextField('message')->create();
    $service = Formie::$plugin->getReportExport();
    $files = Formie::$plugin->getReportExportFiles();
    $pending = asSubmissionExporter(function () use ($form, $service, $files) {
        $report = $service->createSubmissionExportReport(['formIds' => [$form->id]]);

        return $files->createPending($report, 'csv', $service->buildExportContext(report: $report), notifyEmail: 'export@example.test');
    });
    $path = tempnam(sys_get_temp_dir(), 'submission-export-');
    file_put_contents($path, "message\n");
    $exportFile = $files->markReady($files->getExportFileById($pending->id), $path, 'export.csv', 'text/csv');
    $mailer = Craft::$app->getMailer();
    $subjects = [];
    $handler = function ($event) use (&$subjects): void {
        $subjects[] = $event->message->getSubject();
        $event->isValid = false;
    };
    $mailer->on(BaseMailer::EVENT_BEFORE_SEND, $handler);

    try {
        $service->sendReadyNotification($exportFile);
        expect($subjects)->toHaveCount(1)->and($subjects[0])->toContain((string)$form->title);
    } finally {
        $mailer->off(BaseMailer::EVENT_BEFORE_SEND, $handler);
        $files->deleteExportFile($exportFile);
    }
});

it('requires export permission for the index export actions', function (array $grants, bool $allowed): void {
    $user = submissionExportUser($grants, !in_array(Permissions::PERM_EXPORT_SUBMISSIONS_FROM_INDEX, $grants, true));

    asSubmissionExporter(function () use ($allowed): void {
        $controller = new ReportsController('reports', Formie::$plugin);

        foreach (['submission-export', 'submission-export-options', 'submission-export-columns', 'submission-export-count'] as $id) {
            $action = $controller->createAction($id);

            if ($allowed) {
                expect($controller->beforeAction($action))->toBeTrue();
            } else {
                expect(fn() => $controller->beforeAction($action))->toThrow(ForbiddenHttpException::class);
            }
        }
    }, $user, 'GET');
})->with([
    'export grant' => [[Permissions::PERM_EXPORT_SUBMISSIONS], true],
    'submissions-only grant' => [[Permissions::PERM_EXPORT_SUBMISSIONS_FROM_INDEX], true],
    'report manager' => [[Permissions::PERM_MANAGE_REPORTS], true],
    'report viewer' => [[], false],
]);

it('returns dialog options, columns and counts from the controller', function (): void {
    $form = formie()->form()->singleLineTextField('message')->create();
    formie()->submission($form)->with(['message' => 'Counted'])->save();

    asSubmissionExporter(function (User $admin, $request) use ($form): void {
        $controller = new ReportsController('reports', Formie::$plugin);
        $options = $controller->actionSubmissionExportOptions()->data;

        expect(array_column($options['forms'], 'value'))->toContain((string)$form->id)
            ->and($options['statuses'])->not->toBeEmpty()
            ->and($options['asyncExportRowThreshold'])->toBeGreaterThan(0);

        $request->setBodyParams(['formIds' => [$form->id], 'includeComplete' => true, 'includeIncomplete' => true, 'includeSpam' => false]);

        expect($controller->actionSubmissionExportCount()->data['count'])->toBe(1)
            ->and(array_column($controller->actionSubmissionExportColumns()->data['columns'], 'handle'))->toContain('message');
    });
});

it('leaves saved report exports attached to their report', function (): void {
    $form = formie()->form()->singleLineTextField('message')->create();
    $report = new Report(['name' => 'Saved', 'handle' => 'savedExport' . uniqid()]);
    $report->setSettingsModel(ReportSettings::fromArray(['filters' => ['formIds' => [$form->id]]]));
    expect(Formie::$plugin->getReports()->saveReport($report))->toBeTrue();
    $files = Formie::$plugin->getReportExportFiles();
    $pending = $files->createPending($report, 'csv', Formie::$plugin->getReportExport()->buildExportContext(report: $report));
    $exportFile = $files->getExportFileById($pending->id);

    expect($exportFile->reportId)->toBe((int)$report->id)
        ->and($exportFile->context['report'] ?? null)->toBeNull();

    $files->deleteExportFile($exportFile);
});

it('exports immediately or through the queue without access to reports', function (bool $queued): void {
    $form = formie()->form()->settings(['usePerFormPermissions' => true])->singleLineTextField('message')->create();
    $denied = formie()->form()->settings(['usePerFormPermissions' => true])->singleLineTextField('privateMessage')->create();
    formie()->submission($form)->with(['message' => 'Allowed answer'])->save();
    formie()->submission($form)->with(['message' => 'Second answer'])->save();
    formie()->submission($denied)->with(['privateMessage' => 'Private answer'])->save();
    $permissions = Formie::$plugin->getPermissions();
    $grants = [
        Permissions::PERM_EXPORT_SUBMISSIONS_FROM_INDEX,
        'editSite:' . Craft::$app->getSites()->getPrimarySite()->uid,
        $permissions->scopedPermission(Permissions::PERM_VIEW_SUBMISSIONS, $permissions->formScope($form)),
    ];
    $user = submissionExportUser($grants, false);
    $other = submissionExportUser($grants, false);
    $settings = Formie::$plugin->getSettings();
    $threshold = $settings->reportAsyncExportRowThreshold;
    $settings->reportAsyncExportRowThreshold = $queued ? 1 : 1000;
    $files = Formie::$plugin->getReportExportFiles();
    $exportFile = null;

    try {
        asSubmissionExporter(function (User $identity, $request) use ($user, $other, $form, $denied, $queued, $files, &$exportFile): void {
            expect($identity->can(Permissions::PERM_EXPORT_SUBMISSIONS_FROM_INDEX))->toBeTrue()
                ->and($identity->can(Permissions::PERM_ACCESS_REPORTS))->toBeFalse()
                ->and(Formie::$plugin->getPermissions()->canExportSubmissions($identity))->toBeFalse();
            $controller = new ReportsController('reports', Formie::$plugin);
            $request->setBodyParams([
                $request->csrfParam => $request->getCsrfToken(),
                'formIds' => [$form->id, $denied->id],
                'format' => 'csv',
            ]);
            expect(fn() => $controller->runAction('index'))->toThrow(ForbiddenHttpException::class);
            expect(fn() => $controller->runAction('export', ['id' => 1]))->toThrow(ForbiddenHttpException::class);
            expect($controller->runAction('submission-export-count')->data['count'])->toBe(2);
            $options = $controller->runAction('submission-export-options')->data;
            expect(array_column($options['forms'], 'value'))->toContain((string)$form->id)->not->toContain((string)$denied->id);
            $columns = $controller->runAction('submission-export-columns')->data;
            expect(array_column($columns['columns'], 'handle'))->toContain('message')->not->toContain('privateMessage');

            ob_start();
            $response = $controller->runAction('submission-export');
            if ($queued) {
                expect($response->data['queued'])->toBeTrue();
                $exportFile = $files->getExportFileByUid($response->data['export']['uid']);
                $result = Formie::$plugin->getReportExport()->runQueuedExport($exportFile);
                $exportFile = $files->markReady($exportFile, $result['path'], $result['filename'], $result['mimeType']);
                expect($controller->runAction('export-status', ['uid' => $exportFile->uid])->data['status'])->toBe('ready');
                $response = $controller->runAction('download-queued-export', ['uid' => $exportFile->uid]);
            }
            rewind($response->stream[0]);
            $contents = stream_get_contents($response->stream[0]);
            expect($contents)->toContain('Allowed answer', 'Second answer')->not->toContain('Private answer', 'Private Message');
            fclose($response->stream[0]);
            $response->stream = null;
            $response->trigger(\craft\web\Response::EVENT_AFTER_SEND);
            $response->clear();

            if (!$queued) {
                return;
            }

            Craft::$app->getUser()->setIdentity(User::find()->id($other->id)->one());
            foreach (['export-status', 'download-queued-export'] as $action) {
                expect(fn() => $controller->runAction($action, ['uid' => $exportFile->uid]))->toThrow(\yii\web\NotFoundHttpException::class);
            }
            // A queued file must stop working when its owner's export grant is revoked.
            expect(Craft::$app->getUserPermissions()->saveUserPermissions($user->id, ['accessCp', 'accessPlugin-formie', Permissions::PERM_ACCESS_SUBMISSIONS]))->toBeTrue();
            Craft::$app->set('userPermissions', new \craft\services\UserPermissions());
            Craft::$app->getUser()->setIdentity(User::find()->id($user->id)->one());
            foreach (['export-status', 'download-queued-export'] as $action) {
                expect(fn() => $controller->runAction($action, ['uid' => $exportFile->uid]))->toThrow(ForbiddenHttpException::class);
            }
            expect(fn() => Formie::$plugin->getReportExport()->runQueuedExport($exportFile))->toThrow(RuntimeException::class, 'permission');
        }, $user);
    } finally {
        $settings->reportAsyncExportRowThreshold = $threshold;
        if ($exportFile) {
            $files->deleteExportFile($exportFile);
        }
    }
})->with(['immediate' => false, 'queued' => true]);
