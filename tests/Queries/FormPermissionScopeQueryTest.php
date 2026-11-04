<?php

declare(strict_types=1);

use craft\controllers\ElementIndexesController;
use craft\elements\User;
use craft\services\UserPermissions;
use craft\web\TemplateResponseFormatter;
use Tests\Support\PermissionTestHelper;
use Tests\Support\WebRequestTestHelper;
use verbb\base\web\twig\Extension as BaseTwigExtension;
use verbb\formie\controllers\ImportExportController;
use verbb\formie\elements\db\SentNotificationQuery;
use verbb\formie\elements\Form;
use verbb\formie\elements\SentNotification;
use verbb\formie\elements\Submission;
use verbb\formie\Formie;
use verbb\formie\models\FormGroup;
use verbb\formie\models\FormStatus;
use verbb\formie\services\Permissions;

function formScopeUser(array $grants): User
{
    $name = 'formScope' . bin2hex(random_bytes(6));
    $user = new User(['username' => $name, 'email' => $name . '@example.test']);
    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();

    Craft::$app->set('userPermissions', new UserPermissions());
    $grants = array_merge(['accessCp', 'accessPlugin-formie'], $grants);

    foreach (Craft::$app->getSites()->getAllSites() as $site) {
        $grants[] = 'editSite:' . $site->uid;
    }

    expect(Craft::$app->getUserPermissions()->saveUserPermissions($user->id, PermissionTestHelper::withParents($grants)))->toBeTrue();

    return User::find()->id($user->id)->status(null)->one();
}

function formScopeImportPage(User $user): array
{
    return WebRequestTestHelper::withWebRequestContext(function ($request) use ($user): array {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(User::find()->id($user->id)->status(null)->one());
        Craft::$app->getView()->registerTwigExtension(new BaseTwigExtension());

        $response = (new ImportExportController('import-export', Formie::$plugin))->runAction('index');
        (new TemplateResponseFormatter())->format($response);

        return ['status' => $response->statusCode, 'html' => (string)$response->content];
    }, ['method' => 'GET', 'requestUri' => '/admin/formie/settings/import-export']);
}

it('finds no forms for a control panel user whose form scope is empty', function (): void {
    $form = formie()->form(['title' => 'Hidden Scope Form'])->singleLineTextField('message')->create();
    $user = formScopeUser(['formie-importForms']);

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($form, $user): void {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(User::find()->id($user->id)->status(null)->one());

        expect(Formie::$plugin->getPermissions()->getAccessibleFormIds(Craft::$app->getUser()->getIdentity()))->toBe([])
            ->and(Form::find()->handle($form->handle)->ids())->toBe([])
            ->and(Form::find()->handle($form->handle)->all())->toBe([])
            ->and(Form::find()->handle($form->handle)->one())->toBeNull()
            ->and((int)Form::find()->handle($form->handle)->count())->toBe(0)
            ->and(Form::find()->handle($form->handle)->exists())->toBeFalse()
            // Callers that authorize access themselves still reach the same record.
            ->and(array_map('intval', Form::find()->withoutCpIndexScope()->handle($form->handle)->ids()))->toBe([(int)$form->id]);
    });
});

it('limits a scoped control panel user to the forms they can view', function (): void {
    $allowedGroup = new FormGroup(['name' => 'Allowed Scope', 'handle' => 'allowedScope' . bin2hex(random_bytes(5))]);
    $otherGroup = new FormGroup(['name' => 'Other Scope', 'handle' => 'otherScope' . bin2hex(random_bytes(5))]);
    expect(Formie::$plugin->getFormGroups()->saveGroup($allowedGroup))->toBeTrue()
        ->and(Formie::$plugin->getFormGroups()->saveGroup($otherGroup))->toBeTrue();

    $allowed = formie()->form(['groupId' => $allowedGroup->id])->singleLineTextField('message')->create();
    $other = formie()->form(['groupId' => $otherGroup->id])->singleLineTextField('message')->create();
    $permissions = Formie::$plugin->getPermissions();
    $user = formScopeUser([
        Permissions::PERM_ACCESS_FORMS,
        $permissions->scopedPermission(Permissions::PERM_VIEW_FORMS, $permissions->groupScope($allowedGroup->handle)),
    ]);

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($allowed, $other, $user): void {
        $request->setIsCpRequest(true);
        $ids = [$allowed->id, $other->id];

        Craft::$app->getUser()->setIdentity(User::find()->id($user->id)->status(null)->one());
        expect(array_map('intval', Form::find()->id($ids)->ids()))->toBe([(int)$allowed->id]);

        Craft::$app->getUser()->setIdentity(User::find()->admin(true)->one());
        expect(array_map('intval', Form::find()->id($ids)->orderBy(['elements.id' => SORT_ASC])->ids()))->toBe([(int)$allowed->id, (int)$other->id]);
    });
});

it('renders the import page for a user who can only import forms', function (): void {
    $form = formie()->form(['title' => 'Hidden Export Choice'])->singleLineTextField('message')->create();
    $page = formScopeImportPage(formScopeUser(['formie-importForms']));

    expect($page['status'])->toBe(200)
        ->and($page['html'])->toContain('Review Import')
        // The export selector must not disclose forms outside the user's scope.
        ->and($page['html'])->not->toContain('Hidden Export Choice');
});

it('offers forms in the export selector once the user can view them', function (): void {
    formie()->form(['title' => 'Visible Export Choice'])->singleLineTextField('message')->create();
    $page = formScopeImportPage(formScopeUser(['formie-importForms', 'formie-exportForms', Permissions::PERM_ACCESS_FORMS, Permissions::PERM_VIEW_FORMS]));

    expect($page['status'])->toBe(200)
        ->and($page['html'])->toContain('Review Import')
        ->and($page['html'])->toContain('Visible Export Choice');
});

it('finds no forms for a form status that does not exist', function (mixed $criterion): void {
    $status = new FormStatus(['name' => 'Known status', 'handle' => 'knownStatus' . bin2hex(random_bytes(5)), 'color' => 'blue']);
    expect(Formie::$plugin->getFormStatuses()->saveStatus($status))->toBeTrue();
    $form = formie()->form(['formStatusId' => $status->id])->singleLineTextField('message')->create();

    expect(Form::find()->id($form->id)->formStatusId($criterion)->status(null)->ids())->toBe([])
        ->and(array_map('intval', Form::find()->id($form->id)->formStatusId($status->id)->status(null)->ids()))->toBe([(int)$form->id])
        ->and(array_map('intval', Form::find()->id($form->id)->formStatusId($status->handle)->status(null)->ids()))->toBe([(int)$form->id]);
})->with([
    'unknown id' => 999999,
    'unknown handle' => 'statusThatDoesNotExist',
    'unknown handles' => [['statusThatDoesNotExist', 'anotherMissingStatus']],
]);

it('finds no element index rows for a user with empty submission and notification scopes', function (): void {
    $form = formie()->form()->singleLineTextField('message')->create();
    $submission = formie()->submission($form)->with(['message' => 'Scoped out'])->save();
    $notification = new SentNotification(['title' => 'Scoped out', 'formId' => (string)$form->id, 'subject' => 'Subject', 'to' => 'scope@example.test', 'body' => 'Body', 'htmlBody' => '<p>Body</p>', 'success' => true]);
    expect(Craft::$app->getElements()->saveElement($notification))->toBeTrue();
    $user = formScopeUser(['formie-importForms']);

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($submission, $notification, $user): void {
        $request->setIsCpRequest(true);
        Craft::$app->controller = new ElementIndexesController('element-indexes', Craft::$app);

        Craft::$app->getUser()->setIdentity(User::find()->id($user->id)->status(null)->one());
        expect(Submission::find()->id($submission->id)->ids())->toBe([]);
        expect(SentNotification::find()->id($notification->id)->ids())->toBe([]);

        Craft::$app->getUser()->setIdentity(User::find()->admin(true)->one());
        expect(array_map('intval', Submission::find()->id($submission->id)->ids()))->toBe([(int)$submission->id]);
        expect(array_map('intval', SentNotification::find()->id($notification->id)->ids()))->toBe([(int)$notification->id]);
    });
});
