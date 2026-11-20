<?php

declare(strict_types=1);

use craft\models\GqlSchema;
use Tests\Support\WebRequestTestHelper;

// Client-rendered Summary fields fetch the saved answers with the token an accepted save issues, as server-rendered
// responses do, and nothing before that save.
it('returns Summary access tokens from an accepted client page save', function(): void {
    $form = formie()->form()
        ->settings(['disableCaptchas' => true])
        ->multiPage(2)->onPage(1)
        ->singleLineTextField('visitorName', ['label' => 'Visitor name'])
        ->onPage(2)
        ->summaryField('review', ['label' => 'Review'])
        ->create();
    $summary = $form->getFieldByHandle('review');

    try {
        WebRequestTestHelper::withWebRequestContext(function() use ($form, $summary): void {
            $gql = Craft::$app->getGql();
            $schema = new GqlSchema(['name' => 'Summary client result', 'scope' => [
                'formieForms.' . $form->uid . ':read',
                'formieSubmissions.' . $form->uid . ':create',
            ]]);
            $loaded = $gql->executeQuery($schema, 'query($handle: String!) {
                formieClientForm(handle: $handle) { session { id version currentPageId tokens continuation } }
            }', ['handle' => $form->handle]);

            expect($loaded)->not->toHaveKey('errors');

            $submitted = $gql->executeQuery($schema, 'mutation($input: FormieClientSubmitInput!) {
                submitFormieClientForm(input: $input) { outcome success summaryTokens }
            }', ['input' => [
                'handle' => $form->handle,
                'session' => $loaded['data']['formieClientForm']['session'],
                'values' => ['visitorName' => 'Ada'],
            ]]);

            expect($submitted)->not->toHaveKey('errors');
            $result = $submitted['data']['submitFormieClientForm'];
            expect($result['outcome'])->toBe('pageChanged')
                ->and($result['summaryTokens'])->toHaveKey($summary->uid)
                ->and($result['summaryTokens'][$summary->uid])->toBeString()->not->toBe('');
        }, ['method' => 'POST']);
    } finally {
        Craft::$app->getElements()->deleteElement($form, true);
    }
});
