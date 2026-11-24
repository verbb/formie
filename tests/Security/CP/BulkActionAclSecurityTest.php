<?php

declare(strict_types=1);

use craft\db\Query;
use craft\elements\User;
use craft\services\UserPermissions;
use verbb\formie\elements\actions\DuplicateForm;
use verbb\formie\elements\actions\MoveFormToGroup;
use verbb\formie\elements\actions\SetSubmissionSpam;
use verbb\formie\elements\actions\SetSubmissionStatus;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\Formie;
use verbb\formie\helpers\Table;
use verbb\formie\models\FormGroup;
use verbb\formie\models\SubmissionStatus;
use verbb\formie\services\Permissions;

function bulkActionAclFixture(callable $permissionsForForms): array
{
    $allowedForm = formie()->form(['title' => 'Allowed Bulk Action'])
        ->settings(['usePerFormPermissions' => true])
        ->singleLineTextField('fullName')
        ->create();
    $restrictedForm = formie()->form(['title' => 'Restricted Bulk Action'])
        ->settings(['usePerFormPermissions' => true])
        ->singleLineTextField('fullName')
        ->create();
    $allowedSubmission = formie()->submission($allowedForm)->with(['fullName' => 'Allowed'])->save();
    $restrictedSubmission = formie()->submission($restrictedForm)->with(['fullName' => 'Restricted'])->save();
    $name = 'bulkActionAcl' . bin2hex(random_bytes(6));
    $user = new User(['username' => $name, 'email' => $name . '@example.test']);

    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();

    Craft::$app->set('userPermissions', new \craft\services\UserPermissions());

    expect(Craft::$app->getUserPermissions()->saveUserPermissions($user->id, [
        'accessCp',
        'accessPlugin-formie',
        ...$permissionsForForms($allowedForm, $restrictedForm),
        ...array_map(fn($site) => 'editSite:' . $site->uid, Craft::$app->getSites()->getAllSites()),
    ]))->toBeTrue();

    return compact('allowedForm', 'restrictedForm', 'allowedSubmission', 'restrictedSubmission', 'user');
}

function bulkActionAclIdentity(User $user): User
{
    return User::find()->id($user->id)->status(null)->one();
}

it('checks submission save access before bulk status changes', function (): void {
    $permissionService = Formie::$plugin->getPermissions();
    $fixture = bulkActionAclFixture(fn($allowedForm) => [
        Permissions::PERM_ACCESS_SUBMISSIONS,
        $permissionService->scopedPermission(Permissions::PERM_VIEW_SUBMISSIONS, $permissionService->formScope($allowedForm)),
        $permissionService->scopedPermission(Permissions::PERM_SAVE_SUBMISSIONS, $permissionService->formScope($allowedForm)),
    ]);
    $status = new SubmissionStatus([
        'name' => 'Bulk Reviewed ' . uniqid(),
        'handle' => 'bulkReviewed' . uniqid(),
        'color' => 'blue',
    ]);

    expect(Formie::$plugin->getSubmissionStatuses()->saveStatus($status))->toBeTrue();

    $originalUser = Craft::$app->getUser()->getIdentity();
    Craft::$app->getUser()->setIdentity(bulkActionAclIdentity($fixture['user']));

    try {
        $action = new SetSubmissionStatus(['statusId' => $status->id, 'statuses' => [$status]]);
        $result = $action->performAction(Submission::find()
            ->id([$fixture['allowedSubmission']->id, $fixture['restrictedSubmission']->id])
            ->status(null));
    } finally {
        Craft::$app->getUser()->setIdentity($originalUser);
    }

    $allowed = Submission::find()->id($fixture['allowedSubmission']->id)->status(null)->one();
    $restricted = Submission::find()->id($fixture['restrictedSubmission']->id)->status(null)->one();

    expect($result)->toBeTrue()
        ->and((int)$allowed->statusId)->toBe((int)$status->id)
        ->and((int)$restricted->statusId)->not->toBe((int)$status->id);
})->group('security');

it('checks submission save access before bulk spam changes', function (): void {
    $permissionService = Formie::$plugin->getPermissions();
    $fixture = bulkActionAclFixture(fn($allowedForm) => [
        Permissions::PERM_ACCESS_SUBMISSIONS,
        $permissionService->scopedPermission(Permissions::PERM_VIEW_SUBMISSIONS, $permissionService->formScope($allowedForm)),
        $permissionService->scopedPermission(Permissions::PERM_SAVE_SUBMISSIONS, $permissionService->formScope($allowedForm)),
    ]);
    $originalUser = Craft::$app->getUser()->getIdentity();
    Craft::$app->getUser()->setIdentity(bulkActionAclIdentity($fixture['user']));

    try {
        $action = new SetSubmissionSpam(['spam' => 'markSpam']);
        $result = $action->performAction(Submission::find()
            ->id([$fixture['allowedSubmission']->id, $fixture['restrictedSubmission']->id])
            ->status(null));
    } finally {
        Craft::$app->getUser()->setIdentity($originalUser);
    }

    $allowed = Submission::find()->id($fixture['allowedSubmission']->id)->status(null)->one();
    $restricted = Submission::find()->id($fixture['restrictedSubmission']->id)->status(null)->one();

    expect($result)->toBeTrue()
        ->and((bool)$allowed->isSpam)->toBeTrue()
        ->and((bool)$restricted->isSpam)->toBeFalse();
})->group('security');

it('checks form duplication access for every selected form', function (): void {
    $permissionService = Formie::$plugin->getPermissions();
    $fixture = bulkActionAclFixture(fn($allowedForm) => [
        Permissions::PERM_ACCESS_FORMS,
        Permissions::PERM_CREATE_FORMS,
        $permissionService->scopedPermission(Permissions::PERM_MANAGE_FORMS, $permissionService->formScope($allowedForm)),
    ]);
    $originalUser = Craft::$app->getUser()->getIdentity();
    $beforeCount = Form::find()->withoutCpIndexScope()->status(null)->count();
    Craft::$app->getUser()->setIdentity(bulkActionAclIdentity($fixture['user']));

    try {
        $action = new DuplicateForm();
        $result = $action->performAction(Form::find()
            ->withoutCpIndexScope()
            ->id([$fixture['allowedForm']->id, $fixture['restrictedForm']->id])
            ->status(null));
    } finally {
        Craft::$app->getUser()->setIdentity($originalUser);
    }

    expect($result)->toBeTrue()
        ->and((int)Form::find()->withoutCpIndexScope()->status(null)->count())->toBe((int)$beforeCount + 1);
})->group('security');

/**
 * Creates form groups A, B and C, a form in A, a dedicated-permission form in A, and a user holding the given grants.
 */
function moveFormToGroupFixture(callable $grants, bool $admin = false): array
{
    $groups = [];

    foreach (['a', 'b', 'c'] as $key) {
        $groups[$key] = new FormGroup([
            'name' => 'Move ' . strtoupper($key) . ' ' . bin2hex(random_bytes(3)),
            'handle' => 'move' . strtoupper($key) . bin2hex(random_bytes(6)),
        ]);

        expect(Formie::$plugin->getFormGroups()->saveGroup($groups[$key]))->toBeTrue();
    }

    $form = formie()->form(['title' => 'Move Group Form', 'groupId' => $groups['a']->id])
        ->singleLineTextField('fullName')
        ->create();
    $dedicatedForm = formie()->form(['title' => 'Move Group Dedicated Form', 'groupId' => $groups['a']->id])
        ->settings(['usePerFormPermissions' => true])
        ->singleLineTextField('fullName')
        ->create();
    $name = 'moveFormGroup' . bin2hex(random_bytes(6));
    $user = new User(['username' => $name, 'email' => $name . '@example.test']);
    $user->admin = $admin;

    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();

    Craft::$app->set('userPermissions', new UserPermissions());

    expect(Craft::$app->getUserPermissions()->saveUserPermissions($user->id, [
        'accessCp',
        'accessPlugin-formie',
        Permissions::PERM_ACCESS_FORMS,
        ...$grants($groups, $dedicatedForm),
        // Group site availability follows the user's editable sites on multi-site installs, as in the form builder.
        ...array_map(fn($site) => 'editSite:' . $site->uid, Craft::$app->getSites()->getAllSites()),
    ]))->toBeTrue();

    return [
        'groups' => $groups,
        'form' => $form,
        'dedicatedForm' => $dedicatedForm,
        'user' => bulkActionAclIdentity($user),
    ];
}

function moveFormToGroupAs(User $user, callable $callback): mixed
{
    $originalUser = Craft::$app->getUser()->getIdentity();
    Craft::$app->getUser()->setIdentity($user);

    try {
        return $callback();
    } finally {
        Craft::$app->getUser()->setIdentity($originalUser);
    }
}

function moveFormToGroupStoredGroupId(Form $form): ?int
{
    $groupId = (new Query())->select('groupId')->from(Table::FORMIE_FORMS)->where(['id' => $form->id])->scalar();

    return $groupId === null ? null : (int)$groupId;
}

/**
 * Runs the Move form action as the fixture user, with an empty group ID meaning Ungrouped.
 */
function moveFormToGroupPerform(array $fixture, string $groupId, array $forms, ?int $siteId = null): array
{
    return moveFormToGroupAs($fixture['user'], function() use ($groupId, $forms, $siteId): array {
        $action = new MoveFormToGroup(['groupId' => $groupId]);
        $query = Form::find()
            ->withoutCpIndexScope()
            ->id(array_map(static fn(Form $form) => $form->id, $forms))
            ->status(null);

        if ($siteId !== null) {
            $query->siteId($siteId);
        }

        $result = $action->performAction($query);

        return [$result, $action->getMessage()];
    });
}

function moveFormToGroupManageGrant(FormGroup|string $group): string
{
    $permissions = Formie::$plugin->getPermissions();
    $handle = $group instanceof FormGroup ? $group->handle : $group;

    return $permissions->scopedPermission(Permissions::PERM_MANAGE_FORMS, $permissions->groupScope($handle));
}

it('refuses moving forms into a group the user cannot manage', function (): void {
    $fixture = moveFormToGroupFixture(fn(array $groups) => [
        moveFormToGroupManageGrant($groups['a']),
        moveFormToGroupManageGrant($groups['c']),
    ]);
    ['a' => $groupA, 'b' => $groupB, 'c' => $groupC] = $fixture['groups'];

    [$result, $message] = moveFormToGroupPerform($fixture, (string)$groupB->id, [$fixture['form']]);

    expect($result)->toBeFalse()
        ->and($message)->toBe('You don’t have permission to move forms to the selected group.')
        ->and(moveFormToGroupStoredGroupId($fixture['form']))->toBe((int)$groupA->id);

    [$result] = moveFormToGroupPerform($fixture, '', [$fixture['form']]);

    expect($result)->toBeFalse()
        ->and(moveFormToGroupStoredGroupId($fixture['form']))->toBe((int)$groupA->id);

    [$result, $message] = moveFormToGroupPerform($fixture, (string)$groupC->id, [$fixture['form']]);

    expect($result)->toBeTrue()
        ->and($message)->toBe('Form moved.')
        ->and(moveFormToGroupStoredGroupId($fixture['form']))->toBe((int)$groupC->id);

    // The menu only offers groups the user can manage.
    $options = moveFormToGroupAs($fixture['user'], static fn() => MoveFormToGroup::getTargetOptions());

    expect($options)->toHaveKeys([$groupA->id, $groupC->id])
        ->not->toHaveKey($groupB->id)
        ->not->toHaveKey('');
})->group('security');

it('offers and allows Ungrouped to users who can manage ungrouped forms', function (): void {
    $fixture = moveFormToGroupFixture(fn(array $groups) => [
        moveFormToGroupManageGrant($groups['a']),
        moveFormToGroupManageGrant(Permissions::GROUP_UNGROUPED),
    ]);
    $options = moveFormToGroupAs($fixture['user'], static fn() => MoveFormToGroup::getTargetOptions());

    expect($options)->toHaveKey('')
        ->not->toHaveKey($fixture['groups']['b']->id);

    [$result] = moveFormToGroupPerform($fixture, '', [$fixture['form']]);

    expect($result)->toBeTrue()
        ->and(moveFormToGroupStoredGroupId($fixture['form']))->toBeNull();
})->group('security');

it('checks each selected form in its new group like the form builder', function (): void {
    $fixture = moveFormToGroupFixture(fn(array $groups, Form $dedicatedForm) => [
        moveFormToGroupManageGrant($groups['a']),
        Formie::$plugin->getPermissions()->scopedPermission(Permissions::PERM_MANAGE_FORMS, Formie::$plugin->getPermissions()->formScope($dedicatedForm)),
    ]);
    $groupB = $fixture['groups']['b'];

    [$result, $message] = moveFormToGroupPerform($fixture, (string)$groupB->id, [$fixture['form'], $fixture['dedicatedForm']]);

    // The dedicated grant still covers the form in group B, so the builder allows that move too.
    expect($result)->toBeTrue()
        ->and($message)->toBe('Some forms could not be moved.')
        ->and(moveFormToGroupStoredGroupId($fixture['form']))->toBe((int)$fixture['groups']['a']->id)
        ->and(moveFormToGroupStoredGroupId($fixture['dedicatedForm']))->toBe((int)$groupB->id);
})->group('security');

it('keeps moving forms into any group for admins and Manage all forms', function (bool $admin): void {
    $fixture = moveFormToGroupFixture(fn() => $admin ? [] : [Permissions::PERM_MANAGE_FORMS], $admin);
    ['a' => $groupA, 'b' => $groupB, 'c' => $groupC] = $fixture['groups'];
    $options = moveFormToGroupAs($fixture['user'], static fn() => MoveFormToGroup::getTargetOptions());

    expect($options)->toHaveKeys([$groupA->id, $groupB->id, $groupC->id, '']);

    [$result] = moveFormToGroupPerform($fixture, (string)$groupB->id, [$fixture['form']]);

    expect($result)->toBeTrue()
        ->and(moveFormToGroupStoredGroupId($fixture['form']))->toBe((int)$groupB->id);
})->with(['admin' => true, 'manage all forms' => false])->group('security');

it('only registers the Move form action when the user can manage a destination', function (): void {
    $viewer = moveFormToGroupFixture(fn(array $groups) => [
        Formie::$plugin->getPermissions()->scopedPermission(Permissions::PERM_VIEW_FORMS, Formie::$plugin->getPermissions()->groupScope($groups['a']->handle)),
    ]);
    $manager = moveFormToGroupFixture(fn(array $groups) => [moveFormToGroupManageGrant($groups['a'])]);
    $actionTypes = static fn(array $fixture): array => moveFormToGroupAs($fixture['user'], static fn() => array_map(
        static fn($action) => is_object($action) ? get_class($action) : ($action['type'] ?? $action),
        Form::actions('group:' . $fixture['groups']['a']->uid),
    ));

    expect($actionTypes($viewer))->not->toContain(MoveFormToGroup::class)
        ->and($actionTypes($manager))->toContain(MoveFormToGroup::class);
})->group('security');

/**
 * Creates an "open" group and a group limited to the given sites with created-site-only propagation, plus an admin.
 */
function moveFormToGroupSitePolicyFixture(array $policySiteIds): array
{
    $groups = [];

    foreach (['open' => [], 'limited' => ['sitePolicy' => ['enabledSiteIds' => $policySiteIds, 'propagation' => 'createdSiteOnly']]] as $key => $settings) {
        $groups[$key] = new FormGroup([
            'name' => 'Move site ' . $key . ' ' . bin2hex(random_bytes(3)),
            'handle' => 'moveSite' . ucfirst($key) . bin2hex(random_bytes(6)),
            'settings' => $settings,
        ]);

        expect(Formie::$plugin->getFormGroups()->saveGroup($groups[$key]))->toBeTrue();
    }

    $name = 'moveFormSite' . bin2hex(random_bytes(6));
    $user = new User(['username' => $name, 'email' => $name . '@example.test']);
    $user->admin = true;

    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();

    return ['groups' => $groups, 'user' => bulkActionAclIdentity($user)];
}

it('refuses moving forms into a group whose site policy excludes them, like the form builder', function (): void {
    if (!Craft::$app->getIsMultiSite()) {
        $this->markTestSkipped('Multi-site contract.');
    }

    $sites = Craft::$app->getSites()->getAllSiteIds();
    $fixture = moveFormToGroupSitePolicyFixture([$sites[1]]);
    ['open' => $open, 'limited' => $limited] = $fixture['groups'];
    $primaryForm = formie()->form(['title' => 'Move Site Primary', 'groupId' => $open->id, 'siteId' => $sites[0], 'sourceSiteId' => $sites[0]])
        ->singleLineTextField('fullName')
        ->create();
    $secondaryForm = formie()->form(['title' => 'Move Site Secondary', 'groupId' => $open->id, 'siteId' => $sites[1], 'sourceSiteId' => $sites[1]])
        ->singleLineTextField('fullName')
        ->create();

    [$result, $message] = moveFormToGroupPerform($fixture, (string)$limited->id, [$primaryForm], (int)$sites[1]);

    expect($result)->toBeFalse()
        ->and($message)->toBe('This form could not be enabled on any sites for the selected group propagation policy.')
        ->and(moveFormToGroupStoredGroupId($primaryForm))->toBe((int)$open->id);

    [$result, $message] = moveFormToGroupPerform($fixture, (string)$limited->id, [$primaryForm, $secondaryForm], (int)$sites[1]);

    expect($result)->toBeTrue()
        ->and($message)->toBe('Some forms could not be moved.')
        ->and(moveFormToGroupStoredGroupId($primaryForm))->toBe((int)$open->id)
        ->and(moveFormToGroupStoredGroupId($secondaryForm))->toBe((int)$limited->id);
})->group('security');

it('reports a form that its new group leaves out of the current site as not moved', function (): void {
    if (!Craft::$app->getIsMultiSite()) {
        $this->markTestSkipped('Multi-site contract.');
    }

    $sites = Craft::$app->getSites()->getAllSiteIds();
    $fixture = moveFormToGroupSitePolicyFixture([$sites[0], $sites[1]]);
    ['open' => $open, 'limited' => $limited] = $fixture['groups'];
    $primaryForm = formie()->form(['title' => 'Move Site Created Elsewhere', 'groupId' => $open->id, 'siteId' => $sites[0], 'sourceSiteId' => $sites[0]])
        ->singleLineTextField('fullName')
        ->create();

    // The group is available on the second site, but created-site-only keeps this form on the first site.
    [$result, $message] = moveFormToGroupPerform($fixture, (string)$limited->id, [$primaryForm], (int)$sites[1]);

    expect($result)->toBeFalse()
        ->and($message)->toBe('This form can’t be enabled on the selected site under the group’s propagation policy.')
        ->and(moveFormToGroupStoredGroupId($primaryForm))->toBe((int)$open->id);
})->group('security');

it('leaves a form without a stored source site unchanged when moving it from another site', function (): void {
    if (!Craft::$app->getIsMultiSite()) {
        $this->markTestSkipped('Multi-site contract.');
    }

    $sites = Craft::$app->getSites()->getAllSiteIds();
    $fixture = moveFormToGroupSitePolicyFixture([$sites[1]]);
    $open = $fixture['groups']['open'];
    $target = new FormGroup(['name' => 'Move site target ' . bin2hex(random_bytes(3)), 'handle' => 'moveSiteTarget' . bin2hex(random_bytes(6))]);

    expect(Formie::$plugin->getFormGroups()->saveGroup($target))->toBeTrue();

    $form = formie()->form(['title' => 'Move Site Legacy Source', 'groupId' => $open->id, 'siteId' => $sites[0], 'sourceSiteId' => $sites[0]])
        ->singleLineTextField('fullName')
        ->create();

    // Forms saved before the install became multi-site have no stored source site.
    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_FORMS, ['sourceSiteId' => null], ['id' => $form->id])->execute();

    [$result] = moveFormToGroupPerform($fixture, (string)$target->id, [$form], (int)$sites[1]);
    $storedSourceSiteId = (new Query())->select('sourceSiteId')->from(Table::FORMIE_FORMS)->where(['id' => $form->id])->scalar();

    expect($result)->toBeTrue()
        ->and(moveFormToGroupStoredGroupId($form))->toBe((int)$target->id)
        ->and($storedSourceSiteId)->toBeNull();
})->group('security');
