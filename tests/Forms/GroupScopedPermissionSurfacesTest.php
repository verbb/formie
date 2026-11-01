<?php

declare(strict_types=1);

use craft\base\Element;
use craft\elements\actions\Delete;
use craft\elements\User;
use craft\events\TemplateEvent;
use craft\services\UserPermissions;
use craft\web\View;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\SubmissionsController;
use verbb\formie\elements\actions\ResendNotifications;
use verbb\formie\elements\SentNotification;
use verbb\formie\elements\Submission;
use verbb\formie\Formie;
use verbb\formie\models\FormGroup;
use verbb\formie\services\Notifications;
use verbb\formie\services\Permissions;
use verbb\formie\widgets\RecentSubmissions;
use yii\base\Event;
use yii\web\Response;

const GROUP_SCOPED_SURFACE_PERMISSIONS = [
    Permissions::PERM_MANAGE_FORMS,
    'formie-showNotifications',
    'formie-showNotificationsAdvanced',
    'formie-showNotificationsTemplates',
    Permissions::PERM_VIEW_SUBMISSIONS,
    Permissions::PERM_CREATE_SUBMISSIONS,
    Permissions::PERM_VIEW_SENT_NOTIFICATIONS,
    Permissions::PERM_RESEND_SENT_NOTIFICATIONS,
    Permissions::PERM_DELETE_SENT_NOTIFICATIONS,
];

/**
 * Creates an allowed and a denied grouped form, plus a real user whose grants use the given scope.
 */
function groupScopedSurfaceFixture(string $scope): array
{
    $groups = [];

    foreach (['allowed', 'denied'] as $key) {
        $groups[$key] = new FormGroup([
            'name' => ucfirst($key) . ' Surface Group',
            'handle' => $key . 'SurfaceGroup' . bin2hex(random_bytes(6)),
        ]);

        expect(Formie::$plugin->getFormGroups()->saveGroup($groups[$key]))->toBeTrue();
    }

    $forms = [];
    $sentNotifications = [];
    $submissions = [];

    foreach ($groups as $key => $group) {
        $forms[$key] = formie()->form([
            'title' => ucfirst($key) . ' Surface Form ' . bin2hex(random_bytes(3)),
            'groupId' => $group->id,
        ])->settings(['usePerFormPermissions' => $key === 'allowed'])->singleLineTextField('answer')->create();

        $submissions[$key] = formie()->submission($forms[$key])->with(['answer' => ucfirst($key)])->save();

        $sentNotifications[$key] = new SentNotification([
            'title' => ucfirst($key) . ' notice',
            'formId' => (string)$forms[$key]->id,
            'subject' => ucfirst($key) . ' subject',
            'to' => $key . '@example.test',
            'body' => ucfirst($key) . ' body',
            'htmlBody' => '<p>' . ucfirst($key) . ' body</p>',
            'success' => true,
        ]);

        expect(Craft::$app->getElements()->saveElement($sentNotifications[$key]))->toBeTrue();
    }

    $username = 'groupScopedSurface' . bin2hex(random_bytes(6));
    $user = new User(['username' => $username, 'email' => $username . '@example.test']);
    $user->admin = $scope === 'admin';
    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();

    $permissions = Formie::$plugin->getPermissions();
    $suffix = match ($scope) {
        'group' => ':' . $permissions->groupScope($groups['allowed']->handle),
        'form' => ':' . $permissions->formScope($forms['allowed']),
        default => '',
    };

    $grants = [
        'accessCp',
        'accessPlugin-formie',
        Permissions::PERM_ACCESS_FORMS,
        Permissions::PERM_ACCESS_SUBMISSIONS,
        'formie-accessSentNotifications',
    ];

    if ($scope !== 'admin') {
        foreach (GROUP_SCOPED_SURFACE_PERMISSIONS as $permission) {
            $grants[] = $permission . $suffix;
        }
    }

    Craft::$app->set('userPermissions', new UserPermissions());
    expect(Craft::$app->getUserPermissions()->saveUserPermissions($user->id, $grants))->toBeTrue();

    return [
        'user' => User::find()->id($user->id)->status(null)->one(),
        'forms' => $forms,
        'submissions' => $submissions,
        'sentNotifications' => $sentNotifications,
    ];
}

function withGroupScopedSurfaceUser(array $fixture, callable $callback): mixed
{
    return WebRequestTestHelper::withWebRequestContext(function ($request) use ($fixture, $callback): mixed {
        $request->setIsCpRequest(true);
        Craft::$app->getView()->setTemplateMode(View::TEMPLATE_MODE_CP);
        Craft::$app->getUser()->setIdentity($fixture['user']);

        return $callback($fixture['user']);
    });
}

/**
 * Captures the variables passed to a template without rendering it.
 */
function captureGroupScopedSurfaceTemplate(string $template, callable $callback): array
{
    $captured = [];
    $handler = static function (TemplateEvent $event) use ($template, &$captured): void {
        if ($event->template === $template) {
            $captured = $event->variables;
            $event->isValid = false;
        }
    };

    Event::on(View::class, View::EVENT_BEFORE_RENDER_TEMPLATE, $handler);

    try {
        $callback();
    } finally {
        Event::off(View::class, View::EVENT_BEFORE_RENDER_TEMPLATE, $handler);
    }

    return $captured;
}

dataset('groupScopedSurfaceScopes', ['group', 'form', 'global', 'admin']);

it('shows notification advanced and templates tabs for the scoped form', function (string $scope): void {
    $fixture = groupScopedSurfaceFixture($scope);

    $tabsFor = function ($form) use ($fixture): array {
        return withGroupScopedSurfaceUser($fixture, function () use ($form): array {
            $tabs = [];
            $handler = static function ($event) use (&$tabs): void {
                $tabs = array_column($event->tabs, 'handle');
            };

            Event::on(Notifications::class, Notifications::EVENT_MODIFY_NOTIFICATION_SCHEMA, $handler);

            try {
                $form->defineNotificationsSchema();
            } finally {
                Event::off(Notifications::class, Notifications::EVENT_MODIFY_NOTIFICATION_SCHEMA, $handler);
            }

            return $tabs;
        });
    };

    $permissions = Formie::$plugin->getPermissions();
    $user = $fixture['user'];
    $allowed = $fixture['forms']['allowed'];
    $denied = $fixture['forms']['denied'];

    expect($permissions->canShowFormBuilderTab($user, $allowed, 'formie-showNotificationsAdvanced'))->toBeTrue()
        ->and($permissions->canShowFormBuilderTab($user, $allowed, 'formie-showNotificationsTemplates'))->toBeTrue()
        ->and($tabsFor($allowed))->toContain('content', 'advanced', 'templates');

    $deniedTabs = $tabsFor($denied);

    if (in_array($scope, ['group', 'form'], true)) {
        expect($deniedTabs)->toContain('content')->not->toContain('advanced')->not->toContain('templates');
    } else {
        expect($deniedTabs)->toContain('content', 'advanced', 'templates');
    }
})->with('groupScopedSurfaceScopes');

it('lists the scoped form under New Submission on the submissions index', function (string $scope): void {
    $fixture = groupScopedSurfaceFixture($scope);
    $allowed = $fixture['forms']['allowed'];
    $denied = $fixture['forms']['denied'];

    $newSubmission = new Submission();
    $newSubmission->setForm($allowed);
    expect($newSubmission->canSave($fixture['user']))->toBeTrue();

    $editableUids = withGroupScopedSurfaceUser($fixture, function (): array {
        $controller = new class('submissions', Formie::$plugin) extends SubmissionsController {
            public function renderTemplate(string $template, array $variables = [], ?string $templateMode = null): Response
            {
                return new Response(['data' => $variables]);
            }
        };

        return array_column($controller->actionIndex()->data['editableForms'], 'uid');
    });

    expect($editableUids)->toContain($allowed->uid);

    if (in_array($scope, ['group', 'form'], true)) {
        expect($editableUids)->not->toContain($denied->uid);
    } else {
        expect($editableUids)->toContain($denied->uid);
    }
})->with('groupScopedSurfaceScopes');

it('includes the scoped form in the recent submissions widget', function (string $scope): void {
    $fixture = groupScopedSurfaceFixture($scope);
    $allowed = $fixture['forms']['allowed'];
    $denied = $fixture['forms']['denied'];
    $formIds = [$allowed->id, $denied->id];
    $visibleTitles = in_array($scope, ['group', 'form'], true) ? [$allowed->title] : [$allowed->title, $denied->title];

    expect(Formie::$plugin->getPermissions()->canViewSubmissions($fixture['user'], $allowed))->toBeTrue();

    withGroupScopedSurfaceUser($fixture, function () use ($fixture, $formIds, $visibleTitles, $allowed): void {
        $render = static fn(string $displayType, string $method = 'getBodyHtml'): array => captureGroupScopedSurfaceTemplate(
            $method === 'getBodyHtml' ? 'formie/widgets/submissions/body' : 'formie/widgets/submissions/settings',
            static fn() => (new RecentSubmissions(['formIds' => $formIds, 'displayType' => $displayType]))->$method(),
        );

        $list = $render('list');
        $listFormIds = array_values(array_unique(array_map(static fn($submission) => (int)$submission->formId, $list['submissions'])));
        $visibleFormIds = count($visibleTitles) === 1 ? [(int)$allowed->id] : array_map('intval', $formIds);
        sort($listFormIds);
        sort($visibleFormIds);

        $pieLabels = $render('pie')['labels'];
        $lineLabels = array_column($render('line')['datasets'], 'label');
        sort($pieLabels);
        sort($lineLabels);
        sort($visibleTitles);

        expect($listFormIds)->toBe($visibleFormIds)
            ->and($pieLabels)->toBe($visibleTitles)
            ->and($lineLabels)->toBe($visibleTitles);

        $formOptions = $render('list', 'getSettingsHtml')['formOptions'];

        expect($formOptions)->toHaveKey($allowed->id);

        if (count($visibleTitles) === 1) {
            expect($formOptions)->not->toHaveKey($fixture['forms']['denied']->id);
        }
    });
})->with('groupScopedSurfaceScopes');

it('exposes the scoped form source, actions and resend for sent notifications', function (string $scope): void {
    $fixture = groupScopedSurfaceFixture($scope);
    $user = $fixture['user'];
    $allowed = $fixture['forms']['allowed'];
    $denied = $fixture['forms']['denied'];
    $allowedNotification = $fixture['sentNotifications']['allowed'];
    $deniedNotification = $fixture['sentNotifications']['denied'];
    $scoped = in_array($scope, ['group', 'form'], true);

    expect(Formie::$plugin->getPermissions()->canViewSentNotifications($user, $allowed))->toBeTrue()
        ->and($allowedNotification->canView($user))->toBeTrue()
        ->and($allowedNotification->canDelete($user))->toBeTrue()
        ->and($allowedNotification->canResend($user))->toBeTrue()
        ->and($deniedNotification->canResend($user))->toBe(!$scoped);

    withGroupScopedSurfaceUser($fixture, function () use ($allowed, $denied, $scoped): void {
        // Craft memoizes sources per class for the request; each case needs a fresh read.
        $memoizedSources = new ReflectionProperty(Element::class, 'sources');
        $memoizedSources->setValue(null, []);

        try {
            $sourceKeys = array_column(SentNotification::sources('index'), 'key');
        } finally {
            $memoizedSources->setValue(null, []);
        }

        // Inspect Formie's own actions; Craft appends a generic Delete and filters it per element later.
        $defineActions = new ReflectionMethod(SentNotification::class, 'defineActions');
        $actionTypes = static fn(string $source): array => array_map(
            static fn($action) => is_object($action) ? get_class($action) : ($action['type'] ?? $action),
            $defineActions->invoke(null, $source),
        );

        expect($sourceKeys)->toContain("form:{$allowed->id}")
            ->and($actionTypes("form:{$allowed->id}"))->toContain(ResendNotifications::class, Delete::class);

        if ($scoped) {
            expect($sourceKeys)->not->toContain('*')->not->toContain("form:{$denied->id}")
                ->and($actionTypes("form:{$denied->id}"))->not->toContain(ResendNotifications::class)->not->toContain(Delete::class);
        } else {
            expect($sourceKeys)->toContain('*', "form:{$denied->id}");
        }
    });
})->with('groupScopedSurfaceScopes');
