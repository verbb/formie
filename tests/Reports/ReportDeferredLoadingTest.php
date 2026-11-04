<?php

declare(strict_types=1);

use craft\elements\User;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\Formie;
use verbb\formie\controllers\ReportsController;
use verbb\formie\models\Report;
use verbb\formie\services\Permissions;
use verbb\formie\services\ReportEditor;
use verbb\formie\services\ReportViewer;
use yii\web\ForbiddenHttpException;

it('renders the dashboard shell without querying report data or discovering forms', function (bool $openCreate): void {
    $report = new Report(['name' => 'Deferred report', 'handle' => 'deferred' . uniqid()]);
    expect(Formie::$plugin->getReports()->saveReport($report))->toBeTrue();
    $editor = Formie::$plugin->getReportEditor();
    Formie::$plugin->set('reportEditor', new class extends ReportEditor {
        public function getFormOptions(?User $user = null): array
        {
            throw new RuntimeException('Form discovery must wait until the dialog is visible.');
        }
    });

    try {
        WebRequestTestHelper::withWebRequestContext(function () use ($report, $openCreate): void {
            $admin = User::find()->admin(true)->one();
            Craft::$app->getUser()->setIdentity($admin);
            $viewer = new class extends ReportViewer {
                public function getViewConfig(Report $report, ?User $user = null): array
                {
                    throw new RuntimeException('Report data must load after the page shell.');
                }
            };
            $config = $viewer->getDashboardConfig($admin, $report->handle, $openCreate);

            expect($config['selectedReportId'])->toBe((int)$report->id)
                ->and($config['viewConfig'])->toBeNull()
                ->and($config)->not->toHaveKey('formOptions')
                ->and($config['formOptionsUrl'])->toContain('formie/reports/form-options')
                ->and($config['openCreate'])->toBe($openCreate);
        });
    } finally {
        Formie::$plugin->set('reportEditor', $editor);
    }
})->with([false, true]);

it('scopes deferred form options to a report managers submission access', function (bool $manage): void {
    $allowed = formie()->form(['title' => 'Allowed deferred form'])->settings(['usePerFormPermissions' => true])->singleLineTextField('allowed')->create();
    $denied = formie()->form(['title' => 'Denied deferred form'])->settings(['usePerFormPermissions' => true])->singleLineTextField('denied')->create();
    $name = 'deferredManager' . bin2hex(random_bytes(6));
    $user = new User(['username' => $name, 'email' => $name . '@example.test']);
    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();
    Craft::$app->set('userPermissions', new \craft\services\UserPermissions());
    $permissions = Formie::$plugin->getPermissions();
    $grants = [
        'accessCp', 'accessPlugin-formie', Permissions::PERM_ACCESS_SUBMISSIONS, Permissions::PERM_ACCESS_REPORTS,
        $permissions->scopedPermission(Permissions::PERM_VIEW_SUBMISSIONS, $permissions->formScope($allowed)),
    ];
    if ($manage) {
        $grants[] = Permissions::PERM_MANAGE_REPORTS;
    }
    expect(Craft::$app->getUserPermissions()->saveUserPermissions($user->id, $grants))->toBeTrue();

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($user, $manage, $allowed, $denied): void {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(User::find()->id($user->id)->status(null)->one());
        $controller = new ReportsController('reports', Formie::$plugin);
        if (!$manage) {
            expect(fn() => $controller->runAction('form-options'))->toThrow(ForbiddenHttpException::class);
            return;
        }

        $options = $controller->runAction('form-options')->data['formOptions'];
        expect(array_map('intval', array_column($options, 'value')))->toBe([(int)$allowed->id])
            ->and(array_column($options, 'label'))->not->toContain($denied->title);
    }, ['method' => 'GET']);
})->with([true, false]);
