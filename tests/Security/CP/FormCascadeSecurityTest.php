<?php

declare(strict_types=1);

use craft\controllers\ElementIndexesController;
use craft\db\Query;
use craft\elements\actions\{Delete, Restore};
use craft\elements\{Asset, User};
use Tests\Support\{UploadTestHelper, WebRequestTestHelper};
use verbb\formie\controllers\SubmissionsController;
use verbb\formie\elements\{Form, Submission};
use verbb\formie\Formie;
use verbb\formie\services\Permissions;
use yii\base\Event;

function cascadeUser(array $permissions = []): User
{
    $name = 'cascade' . bin2hex(random_bytes(5));
    $user = new User(['username' => $name, 'email' => $name . '@example.test']);
    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();
    Craft::$app->set('userPermissions', new \craft\services\UserPermissions());
    expect(Craft::$app->getUserPermissions()->saveUserPermissions($user->id, [
        'accessCp', 'accessPlugin-formie', 'formie-accessSentNotifications', Permissions::PERM_ACCESS_FORMS, Permissions::PERM_ACCESS_SUBMISSIONS,
        Permissions::PERM_MANAGE_FORMS, Permissions::PERM_DELETE_FORMS, ...$permissions,
        ...array_map(fn($site) => 'editSite:' . $site->uid, Craft::$app->getSites()->getAllSites()),
    ]))->toBeTrue();
    return $user;
}

function cascadeIndexAction(Form $form, string $action, bool $trashed = false): void
{
    $request = Craft::$app->getRequest();
    $request->setBodyParams([
        $request->csrfParam => $request->getCsrfToken(),
        'elementType' => Form::class, 'elementAction' => $action, 'elementIds' => [$form->id],
        'source' => '*', 'context' => 'index', 'criteria' => ['trashed' => $trashed, 'siteId' => $form->siteId],
        'viewState' => ['mode' => 'table', 'static' => false],
    ]);
    $controller = new ElementIndexesController('element-indexes', Craft::$app);
    Craft::$app->controller = $controller;
    $response = $controller->runAction('perform-action');
    expect($response->statusCode)->toBe(200)->and($response->data['error'] ?? null)->toBeNull();
}

function cascadeContext(User $user, callable $callback): void
{
    WebRequestTestHelper::withWebRequestContext(function ($request) use ($user, $callback): void {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(User::find()->id($user->id)->status(null)->one());
        $callback();
    }, ['method' => 'POST', 'headers' => ['Accept' => 'application/json']]);
}

it('cascades index delete and restore independently of response viewing permissions', function (bool $regional): void {
    if ($regional && !Craft::$app->getIsMultiSite()) {
        test()->markTestSkipped('Requires a regional site; the primary-site case runs in both suites.');
    }
    $siteId = $regional ? Craft::$app->getSites()->getAllSiteIds()[1] : Craft::$app->getSites()->getPrimarySite()->id;
    $form = formie()->form(['siteId' => $siteId, 'sourceSiteId' => $siteId])->singleLineTextField('answer')->create();
    $ids = [];
    foreach (['normal', 'draft', 'spam', 'spamDraft', 'previouslyTrashed'] as $state) {
        $submission = formie()->submission($form)->with(['answer' => $state])->save();
        $submission->isIncomplete = in_array($state, ['draft', 'spamDraft']);
        $submission->isSpam = in_array($state, ['spam', 'spamDraft']);
        expect(Craft::$app->getElements()->saveElement($submission))->toBeTrue();
        $ids[$state] = $submission->id;
        if ($state === 'previouslyTrashed') {
            expect(Craft::$app->getElements()->deleteElement($submission))->toBeTrue();
        }
    }
    cascadeContext(cascadeUser(), function () use ($form, $ids): void {
        cascadeIndexAction($form, Delete::class);
        expect(Submission::find()->formId($form->id)->status(null)->trashed(null)->ids())->toBe([]);
        foreach ($ids as $id) {
            expect((new Query())->select('dateDeleted')->from('{{%elements}}')->where(['id' => $id])->scalar())->not->toBeNull();
        }
        cascadeIndexAction($form, Restore::class, true);
        foreach ($ids as $state => $id) {
            $deleted = (new Query())->select('dateDeleted')->from('{{%elements}}')->where(['id' => $id])->scalar();
            expect($deleted === null)->toBe($state !== 'previouslyTrashed');
        }
        expect(Submission::find()->formId($form->id)->status(null)->ids())->toBe([]);
    });
})->with([false, true])->group('security');

it('rolls back form and sibling state when a child rejects a cascade', function (bool $restore): void {
    $form = formie()->form()->create();
    $first = formie()->submission($form)->save();
    $second = formie()->submission($form)->save();
    if ($restore) {
        expect(Craft::$app->getElements()->deleteElement($form))->toBeTrue();
    }
    $eventName = $restore ? Submission::EVENT_BEFORE_RESTORE : Submission::EVENT_BEFORE_DELETE;
    $handler = static function ($event) use ($first): void {
        if ((int)$event->sender->id === (int)$first->id) {
            $event->isValid = false;
        }
    };
    Event::on(Submission::class, $eventName, $handler);
    try {
        cascadeContext(cascadeUser(), function () use ($form, $restore): void {
            expect(fn() => cascadeIndexAction($form, $restore ? Restore::class : Delete::class, $restore))->toThrow(RuntimeException::class);
        });
    } finally {
        Event::off(Submission::class, $eventName, $handler);
    }
    foreach ([$form->id, $first->id, $second->id] as $id) {
        expect((new Query())->select('dateDeleted')->from('{{%elements}}')->where(['id' => $id])->scalar() !== null)->toBe($restore);
    }
})->with([false, true])->group('security');

it('protects stored notification bodies when the source form is trashed', function (bool $global): void {
    $form = formie()->form()->create();
    $sent = new \verbb\formie\elements\SentNotification([
        'title' => 'Stored notice', 'formId' => (string)$form->id, 'subject' => 'Private subject',
        'to' => 'private@example.test', 'body' => 'Private body', 'htmlBody' => '<p>Private body</p>', 'success' => true,
    ]);
    expect(Craft::$app->getElements()->saveElement($sent))->toBeTrue();
    expect(Craft::$app->getElements()->deleteElement($form))->toBeTrue();
    cascadeContext(cascadeUser(['formie-accessSentNotifications', ...($global ? [Permissions::PERM_VIEW_SENT_NOTIFICATIONS] : [])]), function () use ($sent, $global): void {
        Craft::$app->getRequest()->setBodyParams(['id' => $sent->id]);
        $controller = new \verbb\formie\controllers\SentNotificationsController('sent-notifications', Formie::$plugin);
        if ($global) {
            expect($controller->actionGetResendModalData()->data['notification']['body'])->toBe('Private body');
        } else {
            expect(fn() => $controller->actionGetResendModalData())->toThrow(\yii\web\ForbiddenHttpException::class);
        }
    });
})->with([false, true])->group('security');

it('honors upload retention and shared references during permanent index deletion', function (string $policy, bool $shared): void {
    UploadTestHelper::ensureUploadVolume();
    $form = formie()->form(['fileUploadsAction' => $policy])->fileUploadField('documents', ['restrictFiles' => false])->create();
    $asset = UploadTestHelper::seedAsset('index-delete-' . uniqid() . '.txt', 'owned');
    $owner = formie()->submission($form)->with(['documents' => [$asset->id]])->save();
    $uploads = Formie::$plugin->getFileUploads();
    $uploads->trackSubmissionAsset($asset, (int)$form->id, (int)$owner->id, $form->getFieldByHandle('documents')->uid, $form, 'documents');
    if ($shared) {
        $otherForm = formie()->form()->fileUploadField('documents', ['restrictFiles' => false])->create();
        $other = formie()->submission($otherForm)->with(['documents' => [$asset->id]])->save();
        expect(Craft::$app->getElements()->deleteElement($other))->toBeTrue();
    }
    cascadeContext(cascadeUser(), function () use ($form): void {
        cascadeIndexAction($form, Delete::class);
        cascadeIndexAction($form, Delete::class, true);
    });
    expect((new Query())->from('{{%elements}}')->where(['id' => $owner->id])->exists())->toBeFalse()
        ->and(Asset::find()->id($asset->id)->status(null)->trashed(null)->exists())->toBe($policy !== 'delete' || $shared);
})->with(['delete', 'retain'])->with([false, true])->group('security');

it('requires explicit global read permission when the original form is unavailable', function (bool $global): void {
    $user = cascadeUser($global ? [Permissions::PERM_VIEW_SUBMISSIONS, Permissions::PERM_VIEW_SENT_NOTIFICATIONS] : []);
    cascadeContext($user, function () use ($global): void {
        $permissions = Formie::$plugin->getPermissions();
        $identity = Craft::$app->getUser()->getIdentity();
        expect($permissions->canViewSubmissions($identity, null))->toBe($global)
            ->and($permissions->canViewSentNotifications($identity, null))->toBe($global);
    });
})->with([false, true])->group('security');

it('rejects a submission editor URL using a different form before rendering', function (): void {
    $form = formie()->form()->create();
    $other = formie()->form()->create();
    $submission = formie()->submission($form)->save();
    cascadeContext(cascadeUser([Permissions::PERM_VIEW_SUBMISSIONS]), function () use ($other, $submission): void {
        $controller = new SubmissionsController('submissions', Formie::$plugin);
        try {
            $controller->actionEditSubmission($other->handle, (int)$submission->id);
            test()->fail('A mismatched form route must not render.');
        } catch (\yii\web\HttpException $e) {
            expect($e->statusCode)->toBe(404);
        }
    });
})->group('security');

it('preserves hidden JSON upload references during index cleanup', function (bool $trashed): void {
    UploadTestHelper::ensureUploadVolume();
    $form = formie()->form()->fileUploadField('documents', ['restrictFiles' => false])->create();
    $asset = UploadTestHelper::seedAsset('cascade-shared-' . uniqid() . '.txt', 'shared');
    $submission = formie()->submission($form)->with(['documents' => [$asset->id]])->save();
    if ($trashed) {
        expect(Craft::$app->getElements()->deleteElement($submission))->toBeTrue();
    }
    cascadeContext(cascadeUser(), function () use ($asset): void {
        Craft::$app->controller = new ElementIndexesController('element-indexes', Craft::$app);
        expect(Formie::$plugin->getFileUploads()->isReferenced((int)$asset->id))->toBeTrue();
    });
})->with([false, true])->group('security');
