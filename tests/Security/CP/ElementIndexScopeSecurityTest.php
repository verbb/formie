<?php

declare(strict_types=1);

use craft\controllers\ElementIndexesController;
use craft\elements\User;
use craft\services\UserPermissions;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\elements\SentNotification;
use verbb\formie\elements\Submission;
use verbb\formie\Formie;
use verbb\formie\models\FormGroup;
use verbb\formie\services\Permissions;

function elementIndexScopeFixture(): array
{
    $allowedGroup = new FormGroup([
        'name' => 'Allowed Index Group',
        'handle' => 'allowedIndexGroup' . bin2hex(random_bytes(6)),
    ]);
    $deniedGroup = new FormGroup([
        'name' => 'Denied Index Group',
        'handle' => 'deniedIndexGroup' . bin2hex(random_bytes(6)),
    ]);

    expect(Formie::$plugin->getFormGroups()->saveGroup($allowedGroup))->toBeTrue()
        ->and(Formie::$plugin->getFormGroups()->saveGroup($deniedGroup))->toBeTrue();

    $allowedForm = formie()->form([
        'title' => 'Allowed Index Form',
        'groupId' => $allowedGroup->id,
    ])->singleLineTextField('answer')->create();
    $deniedForm = formie()->form([
        'title' => 'Denied Index Form',
        'groupId' => $deniedGroup->id,
    ])->singleLineTextField('answer')->create();

    $allowedSubmission = formie()->submission($allowedForm)->with(['answer' => 'Allowed'])->save();
    $deniedSubmission = formie()->submission($deniedForm)->with(['answer' => 'Denied'])->save();

    $allowedSentNotification = new SentNotification([
        'title' => 'Allowed notice',
        'formId' => (string)$allowedForm->id,
        'subject' => 'Allowed subject',
        'to' => 'allowed@example.test',
        'body' => 'Allowed body',
        'htmlBody' => '<p>Allowed body</p>',
        'success' => true,
    ]);
    $deniedSentNotification = new SentNotification([
        'title' => 'Denied notice',
        'formId' => (string)$deniedForm->id,
        'subject' => 'Denied subject',
        'to' => 'denied@example.test',
        'body' => 'Denied body',
        'htmlBody' => '<p>Denied body</p>',
        'success' => true,
    ]);

    expect(Craft::$app->getElements()->saveElement($allowedSentNotification))->toBeTrue()
        ->and(Craft::$app->getElements()->saveElement($deniedSentNotification))->toBeTrue();

    $username = 'elementIndexScope' . bin2hex(random_bytes(6));
    $user = new User(['username' => $username, 'email' => $username . '@example.test']);
    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();

    Craft::$app->set('userPermissions', new UserPermissions());
    $permissions = Formie::$plugin->getPermissions();
    $groupScope = $permissions->groupScope($allowedGroup->handle);

    expect(Craft::$app->getUserPermissions()->saveUserPermissions($user->id, [
        'accessCp',
        'accessPlugin-formie',
        Permissions::PERM_ACCESS_SUBMISSIONS,
        'formie-accessSentNotifications',
        $permissions->scopedPermission(Permissions::PERM_VIEW_SUBMISSIONS, $groupScope),
        $permissions->scopedPermission(Permissions::PERM_VIEW_SENT_NOTIFICATIONS, $groupScope),
    ]))->toBeTrue();

    return compact(
        'user',
        'allowedSubmission',
        'deniedSubmission',
        'allowedSentNotification',
        'deniedSentNotification',
    );
}

function submissionIdsForIndexScope(mixed $formId = null): array
{
    $query = Submission::find()
        ->status(null)
        ->isIncomplete(null)
        ->isSpam(null);

    if ($formId !== null) {
        $query->formId($formId);
    }

    return array_map('intval', $query->ids());
}

function sentNotificationIdsForIndexScope(mixed $formId = null): array
{
    $query = SentNotification::find()->status(null);

    if ($formId !== null) {
        $query->formId($formId);
    }

    return array_map('intval', $query->ids());
}

it('intersects forged element-index criteria with group-scoped submission permissions', function (): void {
    $fixture = elementIndexScopeFixture();

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($fixture): void {
        $request->setIsCpRequest(true);
        Craft::$app->controller = new ElementIndexesController('element-indexes', Craft::$app);
        Craft::$app->getUser()->setIdentity(User::find()->id($fixture['user']->id)->status(null)->one());

        $allowedFormId = (int)$fixture['allowedSubmission']->formId;
        $deniedFormId = (int)$fixture['deniedSubmission']->formId;

        expect(submissionIdsForIndexScope())->toBe([(int)$fixture['allowedSubmission']->id])
            ->and(submissionIdsForIndexScope($deniedFormId))->toBe([])
            ->and(submissionIdsForIndexScope([$allowedFormId, $deniedFormId]))->toBe([(int)$fixture['allowedSubmission']->id]);
    });
})->group('security');

it('intersects forged element-index criteria with group-scoped sent-notification permissions', function (): void {
    $fixture = elementIndexScopeFixture();

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($fixture): void {
        $request->setIsCpRequest(true);
        Craft::$app->controller = new ElementIndexesController('element-indexes', Craft::$app);
        $identity = User::find()->id($fixture['user']->id)->status(null)->one();
        Craft::$app->getUser()->setIdentity($identity);

        $allowedFormId = (int)$fixture['allowedSentNotification']->formId;
        $deniedFormId = (int)$fixture['deniedSentNotification']->formId;

        expect(Formie::$plugin->getPermissions()->getViewableSentNotificationFormIds($identity))
            ->toContain($allowedFormId)
            ->not->toContain($deniedFormId);

        expect(sentNotificationIdsForIndexScope())->toBe([(int)$fixture['allowedSentNotification']->id])
            ->and(sentNotificationIdsForIndexScope($deniedFormId))->toBe([])
            ->and(sentNotificationIdsForIndexScope([$allowedFormId, $deniedFormId]))->toBe([(int)$fixture['allowedSentNotification']->id]);
    });
})->group('security');

it('fails closed for element-index queries without an authenticated user', function (): void {
    $fixture = elementIndexScopeFixture();

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($fixture): void {
        $request->setIsCpRequest(true);
        Craft::$app->controller = new ElementIndexesController('element-indexes', Craft::$app);
        Craft::$app->getUser()->setIdentity(null);

        expect(submissionIdsForIndexScope([$fixture['allowedSubmission']->formId, $fixture['deniedSubmission']->formId]))->toBe([])
            ->and(sentNotificationIdsForIndexScope([$fixture['allowedSentNotification']->formId, $fixture['deniedSentNotification']->formId]))->toBe([]);
    });
})->group('security');

it('leaves elevated element-index queries unrestricted', function (): void {
    $fixture = elementIndexScopeFixture();

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($fixture): void {
        $request->setIsCpRequest(true);
        Craft::$app->controller = new ElementIndexesController('element-indexes', Craft::$app);
        Craft::$app->getUser()->setIdentity(User::find()->admin(true)->one());

        expect(submissionIdsForIndexScope([$fixture['allowedSubmission']->formId, $fixture['deniedSubmission']->formId]))
            ->toContain((int)$fixture['allowedSubmission']->id, (int)$fixture['deniedSubmission']->id)
            ->and(sentNotificationIdsForIndexScope([$fixture['allowedSentNotification']->formId, $fixture['deniedSentNotification']->formId]))
            ->toContain((int)$fixture['allowedSentNotification']->id, (int)$fixture['deniedSentNotification']->id);
    });
})->group('security');

it('does not scope ordinary web or console queries', function (): void {
    $fixture = elementIndexScopeFixture();
    $submissionFormIds = [$fixture['allowedSubmission']->formId, $fixture['deniedSubmission']->formId];
    $sentNotificationFormIds = [$fixture['allowedSentNotification']->formId, $fixture['deniedSentNotification']->formId];

    WebRequestTestHelper::withWebRequestContext(function () use ($fixture, $submissionFormIds, $sentNotificationFormIds): void {
        Craft::$app->getUser()->setIdentity(User::find()->id($fixture['user']->id)->status(null)->one());

        expect(submissionIdsForIndexScope($submissionFormIds))
            ->toContain((int)$fixture['allowedSubmission']->id, (int)$fixture['deniedSubmission']->id)
            ->and(sentNotificationIdsForIndexScope($sentNotificationFormIds))
            ->toContain((int)$fixture['allowedSentNotification']->id, (int)$fixture['deniedSentNotification']->id);
    });

    expect(submissionIdsForIndexScope($submissionFormIds))
        ->toContain((int)$fixture['allowedSubmission']->id, (int)$fixture['deniedSubmission']->id)
        ->and(sentNotificationIdsForIndexScope($sentNotificationFormIds))
        ->toContain((int)$fixture['allowedSentNotification']->id, (int)$fixture['deniedSentNotification']->id);
})->group('security');
