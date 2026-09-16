<?php

declare(strict_types=1);

use craft\elements\User;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\Formie;
use verbb\formie\models\{FormGroup, Report, ReportSettings, ReportExportFile};
use verbb\formie\services\{Permissions, ReportColumns};

function regionalReportFixture(): array
{
    $sites = Craft::$app->getSites()->getAllSiteIds();
    $group = new FormGroup(['name' => 'Regional report', 'handle' => 'regionalReport' . bin2hex(random_bytes(5)),
        'settings' => ['sitePolicy' => ['enabledSiteIds' => [$sites[1]]]]]);
    expect(Formie::$plugin->getFormGroups()->saveGroup($group))->toBeTrue();
    $form = formie()->form(['groupId' => $group->id, 'siteId' => $sites[1], 'sourceSiteId' => $sites[1]])
        ->singleLineTextField('message')->create();
    $submission = new \verbb\formie\elements\Submission(['siteId' => $form->siteId, 'title' => 'Regional submission']);
    $submission->setForm($form);
    $submission->setFieldValue('message', 'Regional value');
    expect(Craft::$app->getElements()->saveElement($submission))->toBeTrue();
    $report = new Report(['name' => 'Regional report', 'handle' => 'regional' . bin2hex(random_bytes(5))]);
    $report->setSettingsModel(ReportSettings::fromArray(['filters' => ['formIds' => [$form->id]],
        'display' => ['fieldColumnsMode' => ReportColumns::FIELD_COLUMNS_MODE_SELECTED],
        'columns' => [['type' => 'field', 'handle' => 'message', 'label' => 'Message', 'enabled' => true]]]));
    expect(Formie::$plugin->getReports()->saveReport($report))->toBeTrue();
    return [$form, $report];
}

it('includes regional forms in scheduled report queries summaries and export bytes', function (): void {
    if (!Craft::$app->getIsMultiSite()) { $this->markTestSkipped('Multi-site contract.'); }
    [$form, $report] = regionalReportFixture();
    expect(Craft::$app->getSites()->getCurrentSite()->id)->toBe(Craft::$app->getSites()->getPrimarySite()->id);
    $service = Formie::$plugin->getReportQuery();
    expect($service->resolveFormIds([$form->id], null, false))->toBe([(int)$form->id]);
    expect(array_map(fn($row) => $row->getFieldValue('message'), $service->buildSubmissionQuery($report, checkPermissions: false)->all()))->toBe(['Regional value']);
    expect($service->getSummaryCounts($report, checkPermissions: false)['total'])->toBe(1);
    $export = Formie::$plugin->getReportExport()->runQueuedExport(new ReportExportFile([
        'reportId' => $report->id, 'format' => 'csv', 'source' => ReportExportFile::SOURCE_SCHEDULED,
        'context' => Formie::$plugin->getReportExport()->buildExportContext([], null),
    ]));
    try {
        expect(array_map('str_getcsv', file($export['path'], FILE_IGNORE_NEW_LINES)))->toBe([["\xEF\xBB\xBFMessage"], ['Regional value']]);
    } finally { unlink($export['path']); }
});

it('offers regional report forms and fields only to permitted submission viewers', function (bool $allowed): void {
    if (!Craft::$app->getIsMultiSite()) { $this->markTestSkipped('Multi-site contract.'); }
    [$form, $report] = regionalReportFixture();
    $name = 'reportViewer' . bin2hex(random_bytes(6));
    $user = new User(['username' => $name, 'email' => $name . '@example.test']);
    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();
    $permissions = Formie::$plugin->getPermissions();
    Craft::$app->set('userPermissions', new \craft\services\UserPermissions());
    expect(Craft::$app->getUserPermissions()->saveUserPermissions($user->id, $allowed
        ? ['accessCp', 'accessPlugin-formie', Permissions::PERM_ACCESS_SUBMISSIONS, $permissions->scopedPermission(Permissions::PERM_VIEW_SUBMISSIONS, $permissions->groupScope($permissions->getFormGroupHandle($form)))] : []))->toBeTrue();
    WebRequestTestHelper::withWebRequestContext(function ($request) use ($user, $form, $report, $allowed): void {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(User::find()->id($user->id)->status(null)->one());
        $viewer = Craft::$app->getUser()->getIdentity();
        $expected = $allowed ? [(int)$form->id] : [];
        expect(Formie::$plugin->getReportQuery()->resolveFormIds([$form->id], $viewer))->toBe($expected);
        expect(Formie::$plugin->getReportQuery()->getSummaryCounts($report, $viewer)['total'])->toBe($allowed ? 1 : 0);
        $options = Formie::$plugin->getReportEditor()->getFormOptions($viewer);
        expect(array_map('intval', array_column($options, 'value')))->toBe($expected);
        $groups = Formie::$plugin->getReportColumns()->getFieldColumnGroupsForFormIds([$form->id], $viewer);
        expect(array_column($groups, 'formId'))->toBe($expected);
        if ($allowed) { expect(array_column($groups[0]['columns'], 'handle'))->toContain('message'); }
    });
})->with(['allowed' => true, 'denied' => false]);

it('keeps an explicitly loaded regional form usable in the primary control panel', function (string $scenario): void {
    if (!Craft::$app->getIsMultiSite()) { $this->markTestSkipped('Multi-site contract.'); }
    [$form, $report] = regionalReportFixture();
    $admin = User::find()->admin(true)->one();
    WebRequestTestHelper::withWebRequestContext(function ($request) use ($admin, $form, $report, $scenario): void {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity($admin);
        // Each simulated request must resolve its own editable default site.
        $requestedSite = new ReflectionProperty(\craft\helpers\Cp::class, '_requestedSite');
        $before = $requestedSite->getValue();
        $requestedSite->setValue(null, null);
        Formie::$plugin->getForms()->invalidateFormCaches();
        try {
            expect(\craft\helpers\Cp::requestedSite()->id)->toBe(Craft::$app->getSites()->getPrimarySite()->id);
            expect(\verbb\formie\elements\Form::find()->id($form->id)->one())->toBeNull();
            if ($scenario === 'query') {
                expect(\verbb\formie\elements\Form::find()->id($form->id)->siteId($form->siteId)->one()?->id)->toBe($form->id);
            } elseif ($scenario === 'report') {
                $data = Formie::$plugin->getReportQuery()->getTableData($report, user: $admin);
                expect($data['pagination']['total'])->toBe(1);
                expect(array_values($data['rows'][0]['cells']))->toBe([['type' => 'text', 'value' => 'Regional value']]);
            } else {
                parse_str(parse_url($form->getCpEditUrl(), PHP_URL_QUERY), $params);
                expect($params['site'] ?? null)->toBe(Craft::$app->getSites()->getSiteById($form->siteId)->handle);
            }
        } finally {
            $requestedSite->setValue(null, $before);
            Formie::$plugin->getForms()->invalidateFormCaches();
        }
    });
})->with(['query', 'report', 'edit link']);
