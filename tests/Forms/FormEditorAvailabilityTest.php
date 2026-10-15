<?php

declare(strict_types=1);

use craft\elements\User;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\FormsController;
use verbb\formie\elements\Form;
use verbb\formie\Formie;
use verbb\formie\services\Permissions;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

function formEditorAvailabilityContext(bool $canManage, callable $callback, array $options = []): mixed
{
    $name = 'formAvailability' . bin2hex(random_bytes(5));
    $user = new User(['username' => $name, 'email' => $name . '@example.test']);
    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();
    Craft::$app->set('userPermissions', new \craft\services\UserPermissions());
    expect(Craft::$app->getUserPermissions()->saveUserPermissions($user->id, [
        'accessCp', 'accessPlugin-formie', Permissions::PERM_ACCESS_FORMS, Permissions::PERM_VIEW_FORMS,
        ...($canManage ? [Permissions::PERM_MANAGE_FORMS] : []),
        ...array_map(fn($site) => 'editSite:' . $site->uid, Craft::$app->getSites()->getAllSites()),
    ]))->toBeTrue();

    return WebRequestTestHelper::withWebRequestContext(function () use ($user, $callback): mixed {
        Craft::$app->getRequest()->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(User::find()->id($user->id)->status(null)->one());
        $controller = new class('forms', Formie::$plugin) extends FormsController {
            public function renderTemplate(string $template, array $variables = [], ?string $templateMode = null): Response
            {
                return new Response(['data' => $variables]);
            }
        };
        return $callback($controller);
    }, $options);
}

it('opens the form builder independently of public availability and status labels', function (string $status, bool $enabled): void {
    $form = formie()->form([
        'formStatusId' => Formie::$plugin->getFormStatuses()->getStatusByHandle($status)->id,
    ])->singleLineTextField('message')->create();
    Craft::$app->getDb()->createCommand()->update('{{%elements}}', ['enabled' => $enabled], ['id' => $form->id])->execute();
    Formie::$plugin->getForms()->invalidateFormCaches();

    formEditorAvailabilityContext(true, function ($controller) use ($form, $status, $enabled): void {
        $response = $controller->actionEdit((string)$form->id);
        expect($response->data['form']->id)->toBe($form->id)
            ->and($response->data['form']->getStatus())->toBe($status)
            ->and($response->data['form']->enabled)->toBe($enabled);
    });
    expect(Form::find()->id($form->id)->exists())->toBe($enabled);
})->with(['active', 'draft', 'archived'])->with([true, false]);

it('saves disabled forms without changing their public availability', function (): void {
    $form = formie()->form()->singleLineTextField('message')->create();
    Craft::$app->getDb()->createCommand()->update('{{%elements}}', ['enabled' => false], ['id' => $form->id])->execute();

    formEditorAvailabilityContext(true, function ($controller): void {
        $response = $controller->actionSave();
        expect($response->data)->not->toHaveKey('errors');
    }, [
        'method' => 'POST',
        'headers' => ['Accept' => 'application/json'],
        'bodyParams' => ['id' => $form->id, 'siteId' => $form->siteId, 'title' => 'Edited disabled form'],
    ]);

    $saved = Form::find()->id($form->id)->status(null)->one();
    expect($saved->title)->toBe('Edited disabled form')
        ->and($saved->enabled)->toBeFalse()
        ->and(Form::find()->id($form->id)->exists())->toBeFalse();
});

it('requires manage permission to open or save disabled forms', function (): void {
    $form = formie()->form()->create();
    Craft::$app->getDb()->createCommand()->update('{{%elements}}', ['enabled' => false], ['id' => $form->id])->execute();

    formEditorAvailabilityContext(false, function ($controller) use ($form): void {
        expect(fn() => $controller->actionEdit((string)$form->id))->toThrow(ForbiddenHttpException::class);
        expect(fn() => $controller->actionSave())->toThrow(ForbiddenHttpException::class);
    }, ['method' => 'POST', 'bodyParams' => ['id' => $form->id, 'siteId' => $form->siteId]]);
});

it('does not open deleted forms in the builder', function (): void {
    $form = formie()->form()->create();
    expect(Craft::$app->getElements()->deleteElement($form))->toBeTrue();
    formEditorAvailabilityContext(true, function ($controller) use ($form): void {
        expect(fn() => $controller->actionEdit((string)$form->id))->toThrow(NotFoundHttpException::class);
    });
});
