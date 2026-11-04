<?php

declare(strict_types=1);

use craft\elements\User;
use craft\helpers\Json;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\enums\SubmissionAuthorityType;
use verbb\formie\fields\Email;
use verbb\formie\models\HtmlSubmissionRequest;

function blockedDomainsForm(array $rows, ?string $message = 'Blocked {domain}'): Form
{
    $form = formie()->form(['title' => 'Blocked Domains ' . uniqid()])
        ->settings(['disableCaptchas' => true])
        ->emailField('email', ['validateDomain' => false])
        ->create();

    // Rows arrive through the same builder payload and save path as the form builder.
    $pages = Json::decode(Json::encode($form->getFormLayout()->getFormBuilderConfig()));
    $pages[0]['rows'][0]['fields'][0]['blockedDomains'] = $rows;

    if ($message !== null) {
        $pages[0]['rows'][0]['fields'][0]['validationMessages']['blockedDomain'] = $message;
    }

    WebRequestTestHelper::withWebRequestContext(function($request) use ($form, $pages): void {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(User::find()->admin(true)->one());
        $request->setBodyParams(['id' => $form->id, 'siteId' => $form->siteId, 'pages' => $pages]);

        expect(Craft::$app->getElements()->saveElement(Formie::$plugin->getForms()->buildFormFromPost()))->toBeTrue();
    }, ['method' => 'POST']);

    Formie::$plugin->getFields()->resetFieldRegistryCache();

    return Form::find()->id($form->id)->one();
}

function submitBlockedDomainsEmail(Form $form, string $address): array
{
    return WebRequestTestHelper::withWebRequestContext(function($request) use ($form, $address): array {
        Craft::$app->getUser()->setIdentity(null);
        $session = Formie::$plugin->getFormSessions()->issueInitialSession($form)->toArrayRecursive();
        $request->setBodyParams([
            'handle' => $form->handle,
            'requestToken' => $session['tokens']['request'],
            'expectedVersion' => 0,
            'formieHoneypot' => '',
            'formStartedAt' => (string)((int)(microtime(true) * 1000) - 60000),
            'fields' => ['email' => $address],
        ]);

        $execution = Formie::$plugin->getSubmissionRequests()->executeHtml(new HtmlSubmissionRequest([
            'handle' => $form->handle,
            'requestToken' => $session['tokens']['request'],
            'expectedVersion' => 0,
        ]), SubmissionAuthorityType::VISITOR);

        $submission = $execution->command?->submission;
        $persisted = $submission?->id ? Submission::find()->id($submission->id)->status(null)->one() : null;

        return [
            'success' => $execution->response?->success,
            'errors' => $submission?->getErrors()['email'] ?? [],
            'persisted' => $persisted?->getFieldValue('email'),
            'isSpam' => $persisted?->isSpam,
        ];
    }, ['method' => 'POST']);
}

function expectBlockedDomainRejected(array $result, string $message): void
{
    expect($result['success'])->toBeFalse()
        ->and($result['errors'])->toBe([$message])
        ->and($result['persisted'])->toBeNull();
}

function expectBlockedDomainAccepted(array $result, string $address): void
{
    expect($result['success'])->toBeTrue()
        ->and($result['errors'])->toBe([])
        ->and($result['persisted'])->toBe($address)
        ->and($result['isSpam'])->toBeFalse();
}

it('blocks a domain added as a new row in the form builder', function (): void {
    $form = blockedDomainsForm([['label' => 'blocked.example']]);

    expect($form->getFieldByHandle('email')->blockedDomains)->toBe([['label' => 'blocked.example']]);
    expectBlockedDomainRejected(submitBlockedDomainsEmail($form, 'person@blocked.example'), 'Blocked blocked.example');
    expectBlockedDomainAccepted(submitBlockedDomainsEmail($form, 'person@allowed.example'), 'person@allowed.example');
});

it('uses the default blocked domain message', function (): void {
    $form = blockedDomainsForm([['label' => 'blocked.example']], null);

    expectBlockedDomainRejected(submitBlockedDomainsEmail($form, 'person@blocked.example'), '“blocked.example” is not allowed.');
});

it('blocks the visible domain after an existing row is edited', function (): void {
    $form = blockedDomainsForm([['label' => 'new.example', 'value' => 'old.example', 'domain' => 'old.example']]);

    expectBlockedDomainRejected(submitBlockedDomainsEmail($form, 'person@new.example'), 'Blocked new.example');
    expectBlockedDomainAccepted(submitBlockedDomainsEmail($form, 'person@old.example'), 'person@old.example');
});

it('keeps blocking existing rows', function (array $row): void {
    $form = blockedDomainsForm([$row]);

    expectBlockedDomainRejected(submitBlockedDomainsEmail($form, 'person@blocked.example'), 'Blocked blocked.example');
    expectBlockedDomainAccepted(submitBlockedDomainsEmail($form, 'person@allowed.example'), 'person@allowed.example');
})->with([
    'upgraded row' => [['label' => 'blocked.example', 'value' => 'blocked.example', 'domain' => 'blocked.example']],
    'value only' => [['value' => 'blocked.example']],
    'blank label' => [['label' => '', 'value' => 'blocked.example']],
]);

it('matches blocked domains regardless of case or surrounding space', function (): void {
    $form = blockedDomainsForm([['label' => ' Blocked.Example ']]);

    expectBlockedDomainRejected(submitBlockedDomainsEmail($form, 'Person@BLOCKED.example'), 'Blocked blocked.example');
});

it('checks every row in the list', function (): void {
    $form = blockedDomainsForm([
        ['label' => 'first.example'],
        ['label' => 'second.example', 'value' => 'second.example', 'domain' => 'second.example'],
        ['value' => 'third.example'],
    ]);

    foreach (['first', 'second', 'third'] as $name) {
        expectBlockedDomainRejected(submitBlockedDomainsEmail($form, "person@{$name}.example"), "Blocked {$name}.example");
    }

    expectBlockedDomainAccepted(submitBlockedDomainsEmail($form, 'person@fourth.example'), 'person@fourth.example');
});

it('leaves submissions alone when no domains are blocked', function (): void {
    $form = blockedDomainsForm([]);

    expectBlockedDomainAccepted(submitBlockedDomainsEmail($form, 'person@blocked.example'), 'person@blocked.example');
});

it('reports the visible domains through GraphQL', function (): void {
    $field = new Email(['blockedDomains' => [
        ['label' => 'new.example'],
        ['label' => 'edited.example', 'value' => 'old.example', 'domain' => 'old.example'],
        ['value' => 'legacy.example'],
    ]]);

    expect($field->getSettingGqlTypes()['blockedDomains']['resolve']($field))->toBe(['new.example', 'edited.example', 'legacy.example']);
});
