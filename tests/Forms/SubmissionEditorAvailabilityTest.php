<?php

declare(strict_types=1);

use craft\elements\User;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\SubmissionsController;
use verbb\formie\elements\Form;
use verbb\formie\Formie;
use verbb\formie\services\Permissions;
use yii\web\ForbiddenHttpException;
use yii\web\HttpException;
use yii\web\Response;

function submissionAvailabilityContext(bool $canView, callable $callback): mixed
{
    $name = 'submissionAvailability' . bin2hex(random_bytes(5));
    $user = new User(['username' => $name, 'email' => $name . '@example.test']);
    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();
    Craft::$app->set('userPermissions', new \craft\services\UserPermissions());
    expect(Craft::$app->getUserPermissions()->saveUserPermissions($user->id, [
        'accessCp', 'accessPlugin-formie', Permissions::PERM_ACCESS_SUBMISSIONS,
        ...($canView ? [Permissions::PERM_VIEW_SUBMISSIONS] : []),
        ...array_map(fn($site) => 'editSite:' . $site->uid, Craft::$app->getSites()->getAllSites()),
    ]))->toBeTrue();

    return WebRequestTestHelper::withWebRequestContext(function () use ($user, $callback): mixed {
        Craft::$app->getRequest()->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(User::find()->id($user->id)->status(null)->one());
        // Capture the editor data after the real lookup, authorization and preparation.
        $controller = new class('submissions', Formie::$plugin) extends SubmissionsController {
            public function renderTemplate(string $template, array $variables = [], ?string $templateMode = null): Response
            {
                return new Response(['data' => $variables]);
            }
        };
        return $callback($controller);
    });
}

it('opens existing submissions independently of form availability and lifecycle labels', function (string $status, bool $enabled): void {
    $form = formie()->form([
        'formStatusId' => Formie::$plugin->getFormStatuses()->getStatusByHandle($status)->id,
    ])->singleLineTextField('message')->create();
    $submission = formie()->submission($form)->with(['message' => 'Existing response'])->save();
    Craft::$app->getDb()->createCommand()->update('{{%elements}}', ['enabled' => $enabled], ['id' => $form->id])->execute();
    Formie::$plugin->getForms()->invalidateFormCaches();

    submissionAvailabilityContext(true, function ($controller) use ($form, $submission, $status, $enabled): void {
        // Submission viewers need not also have permission to browse/edit forms.
        $response = $controller->actionEditSubmission($form->handle, (int)$submission->id);
        expect($response->data['submission']->id)->toBe($submission->id)
            ->and($response->data['submission']->getFieldValue('message'))->toBe('Existing response')
            ->and($response->data['form']->getStatus())->toBe($status)
            ->and($response->data['form']->enabled)->toBe($enabled);
    });

    // Opening an existing response must not enable a form for public queries.
    expect(Form::find()->id($form->id)->exists())->toBe($enabled);
})->with(['active', 'draft', 'archived'])->with([true, false]);

it('still checks submission permissions for disabled forms', function (): void {
    $form = formie()->form()->create();
    $submission = formie()->submission($form)->save();
    Craft::$app->getDb()->createCommand()->update('{{%elements}}', ['enabled' => false], ['id' => $form->id])->execute();

    submissionAvailabilityContext(false, function ($controller) use ($form, $submission): void {
        expect(fn() => $controller->actionEditSubmission($form->handle, (int)$submission->id))
            ->toThrow(ForbiddenHttpException::class);
    });
});

it('still rejects mismatched and deleted parent forms in submission editor routes', function (bool $deleted): void {
    $form = formie()->form()->create();
    $other = formie()->form()->create();
    $submission = formie()->submission($form)->save();
    if ($deleted) {
        expect(Craft::$app->getElements()->deleteElement($form))->toBeTrue();
    }

    submissionAvailabilityContext(true, function ($controller) use ($form, $other, $submission, $deleted): void {
        try {
            $controller->actionEditSubmission($deleted ? $form->handle : $other->handle, (int)$submission->id);
            test()->fail('Unavailable or mismatched forms must not open.');
        } catch (HttpException $e) {
            expect($e->statusCode)->toBe(404);
        }
    });
})->with([true, false]);
