<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\client\models\LoadContext;
use verbb\formie\client\models\SubmitRequest;
use Tests\Support\WebRequestTestHelper;

it('submits a client payload through the current bootstrap session contract', function(): void {
    $form = formie()
        ->form([
            'title' => 'Frontend Submit ' . uniqid(),
        ])
        ->singleLineTextField('fullName', ['required' => true])
        ->emailField('emailAddress', ['required' => true])
        ->create();

    WebRequestTestHelper::withWebRequestContext(function() use ($form) {
        $bootstrap = Formie::$plugin->getClientFormBootstrapBuilder()->build($form, new LoadContext([
            'handle' => $form->handle,
        ]));

        $result = Formie::$plugin->getSubmissionProcessor()->execute(new SubmitRequest([
            'handle' => $form->handle,
            'action' => 'submit',
            'session' => $bootstrap->session->toArrayRecursive(),
            'values' => [
                'fullName' => 'Peter Sherman',
                'emailAddress' => 'peter@example.test',
            ],
        ]))->toArrayRecursive();

        expect($result['success'])->toBeTrue()
            ->and($result['errors']['form'] ?? [])->toBe([])
            ->and($result['errors']['fields'] ?? [])->toBeArray();
    });
});
