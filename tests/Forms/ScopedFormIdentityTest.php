<?php

declare(strict_types=1);

use craft\elements\User;
use craft\errors\GqlException;
use craft\helpers\StringHelper;
use craft\models\GqlSchema;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\elements\{Form, Submission};
use verbb\formie\Formie;
use verbb\formie\helpers\Gql;
use verbb\formie\services\Permissions;

it('lists the forms and submission sources assigned to a persisted role', function (): void {
    $allowed = formie()->form()->settings(['usePerFormPermissions' => true])->singleLineTextField('message')->create();
    $restricted = formie()->form()->settings(['usePerFormPermissions' => true])->singleLineTextField('message')->create();
    $name = 'scopedRole' . bin2hex(random_bytes(6));
    $user = new User(['username' => $name, 'email' => $name . '@example.test']);
    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();
    // Register permissions after this test's forms have been created.
    Craft::$app->set('userPermissions', new \craft\services\UserPermissions());
    expect(Craft::$app->getUserPermissions()->saveUserPermissions($user->id, [
        'accessCp', 'accessPlugin-formie', Permissions::PERM_ACCESS_FORMS, Permissions::PERM_ACCESS_SUBMISSIONS,
        Permissions::PERM_MANAGE_FORMS . ':' . $allowed->uid,
        Permissions::PERM_VIEW_SUBMISSIONS . ':' . $allowed->uid,
        ...array_map(fn($site) => 'editSite:' . $site->uid, Craft::$app->getSites()->getAllSites()),
    ]))->toBeTrue();

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($allowed, $restricted, $user): void {
        $request->setIsCpRequest(true);
        $identity = User::find()->id($user->id)->status(null)->one();
        Craft::$app->getUser()->setIdentity($identity);
        Craft::$app->set('sites', new \craft\services\Sites());
        expect($allowed->canView($identity))->toBeTrue()->and($restricted->canView($identity))->toBeFalse();
        expect(Form::find()->ids())->toContain($allowed->id)->not->toContain($restricted->id);
        $sources = (new ReflectionMethod(Submission::class, 'defineSources'))->invoke(null, 'index');
        expect(array_column($sources, 'key'))->toContain('form:' . $allowed->id)->not->toContain('form:' . $restricted->id);
    });
});

it('uses the registered form identity for scoped graphql operations', function (string $operation): void {
    $allowed = formie()->form()->singleLineTextField('message')->create();
    $restricted = formie()->form()->singleLineTextField('message')->create();
    $submission = formie()->submission($allowed)->with(['message' => 'Allowed'])->save();
    $other = formie()->submission($restricted)->with(['message' => 'Restricted'])->save();
    $service = Craft::$app->getGql();
    $previous = null;
    try { $previous = $service->getActiveSchema(); } catch (GqlException) {}
    $schema = new GqlSchema(['name' => 'Scoped form identity', 'uid' => StringHelper::UUID(), 'scope' => [
        'formieForms.' . $allowed->uid . ':read',
        'formieSubmissions.' . $allowed->uid . ':read',
        'formieSubmissions.' . $allowed->uid . ':delete',
    ]]);
    $service->setActiveSchema($schema);

    try {
        if ($operation === 'bootstrap') {
            expect(Gql::canReadForm($allowed))->toBeTrue()->and(Gql::canReadForm($restricted))->toBeFalse();
        } elseif ($operation === 'query') {
            $result = $service->executeQuery($schema, '{ formieForms { id } formieSubmissions { id } }');
            expect($result['errors'] ?? [])->toBe([]);
            expect(array_map('intval', array_column($result['data']['formieForms'], 'id')))->toBe([$allowed->id]);
            expect(array_map('intval', array_column($result['data']['formieSubmissions'], 'id')))->toBe([$submission->id]);
        } else {
            $mutation = 'mutation($id: Int!, $siteId: Int!) { deleteSubmission(id: $id, siteId: $siteId) }';
            $denied = $service->executeQuery($schema, $mutation, ['id' => $other->id, 'siteId' => $other->siteId]);
            expect($denied['errors'] ?? [])->not->toBeEmpty();
            expect(Submission::find()->id($other->id)->one())->not->toBeNull();
            $result = $service->executeQuery($schema, $mutation, ['id' => $submission->id, 'siteId' => $submission->siteId]);
            expect($result['errors'] ?? [])->toBe([])->and($result['data']['deleteSubmission'])->toBeTrue();
            expect(Submission::find()->id($submission->id)->one())->toBeNull();
        }
    } finally {
        $service->setActiveSchema($previous);
    }
})->with(['bootstrap', 'query', 'delete']);
