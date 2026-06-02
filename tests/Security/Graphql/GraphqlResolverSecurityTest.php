<?php

declare(strict_types=1);

use craft\errors\GqlException;
use craft\models\GqlSchema;
use GraphQL\Error\Error;
use verbb\formie\gql\resolvers\HtmlFormResolver;
use verbb\formie\gql\resolvers\ClientFormResolver;
use yii\web\NotFoundHttpException;

it('returns a generic not-found message for unknown client form handles', function (): void {
    $missingHandle = 'security-missing-client-' . uniqid();

    try {
        ClientFormResolver::resolveForm(null, [
            'handle' => $missingHandle,
        ]);

        $this->fail('Expected a not-found exception for unknown handle.');
    } catch (NotFoundHttpException $exception) {
        expect($exception->getMessage())
            ->toBe('Form not found')
            ->not->toContain($missingHandle);
    }
})->group('security');

it('returns a generic not-found message for unknown html form handles', function (): void {
    $missingHandle = 'security-missing-html-' . uniqid();

    try {
        HtmlFormResolver::resolve(null, [
            'handle' => $missingHandle,
        ]);

        $this->fail('Expected a not-found exception for unknown handle.');
    } catch (NotFoundHttpException $exception) {
        expect($exception->getMessage())
            ->toBe('Form not found')
            ->not->toContain($missingHandle);
    }
})->group('security');

it('hides unreadable client form handles behind not-found responses', function (): void {
    $allowedForm = formie()
        ->form(['title' => 'Allowed Client GraphQL Form'])
        ->singleLineTextField('fullName')
        ->create();
    $blockedForm = formie()
        ->form(['title' => 'Blocked Client GraphQL Form'])
        ->singleLineTextField('fullName')
        ->create();

    withGraphqlSchemaScope([
        'formieForms.' . $allowedForm->uid . ':read',
    ], function () use ($blockedForm): void {
        expect(fn() => ClientFormResolver::resolveForm(null, [
            'handle' => (string)$blockedForm->handle,
        ]))->toThrow(NotFoundHttpException::class, 'Form not found');
    });
})->group('security');

it('hides unreadable html form handles behind not-found responses', function (): void {
    $allowedForm = formie()
        ->form(['title' => 'Allowed Html GraphQL Form'])
        ->singleLineTextField('fullName')
        ->create();
    $blockedForm = formie()
        ->form(['title' => 'Blocked Html GraphQL Form'])
        ->singleLineTextField('fullName')
        ->create();

    withGraphqlSchemaScope([
        'formieForms.' . $allowedForm->uid . ':read',
    ], function () use ($blockedForm): void {
        expect(fn() => HtmlFormResolver::resolve(null, [
            'handle' => (string)$blockedForm->handle,
        ]))->toThrow(NotFoundHttpException::class, 'Form not found');
    });
})->group('security');

it('requires submission mutation scopes for client graphql session mutations', function (): void {
    $form = formie()
        ->form(['title' => 'Client GraphQL Mutation Permissions'])
        ->singleLineTextField('fullName')
        ->create();

    withGraphqlSchemaScope([
        'formieForms.' . $form->uid . ':read',
    ], function () use ($form): void {
        expect(fn() => ClientFormResolver::refreshSession(null, [
            'input' => [
                'handle' => (string)$form->handle,
                'session' => [],
            ],
        ]))->toThrow(Error::class, 'Unable to perform the action.');
    });
})->group('security');

function withGraphqlSchemaScope(array $scope, callable $callback): void
{
    $gqlService = \Craft::$app->getGql();
    $activeSchema = null;

    try {
        $activeSchema = $gqlService->getActiveSchema();
    } catch (GqlException) {
        // No active schema is normal in test runtime.
    }

    $gqlService->setActiveSchema(new GqlSchema([
        'name' => 'Formie GraphQL Resolver Security Test Schema',
        'scope' => $scope,
    ]));

    try {
        $callback();
    } finally {
        $gqlService->setActiveSchema($activeSchema);
    }
}
