<?php

declare(strict_types=1);

use craft\errors\GqlException;
use craft\helpers\StringHelper;
use craft\models\GqlSchema;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\RecipientTokenHelper;

// The rendered form and client bootstrap only expose recipient tokens. A public schema is read by the browser, so its
// form and submission data must not hand out the addresses either. Private schemas are trusted server reads.

function recipientsGraphqlForm(): Form
{
    $form = formie()->form(['handle' => 'recipientsGql' . bin2hex(random_bytes(4))])
        ->settings(['disableCaptchas' => true])
        ->singleLineTextField('message')
        ->recipientsField('department', ['displayType' => 'dropdown', 'options' => [
            ['label' => 'Sales', 'value' => 'sales@example.test'],
            ['label' => 'Support', 'value' => 'support@example.test'],
        ]])
        ->recipientsField('teams', ['displayType' => 'checkboxes', 'options' => [
            ['label' => 'Sales', 'value' => 'sales@example.test'],
            ['label' => 'Support', 'value' => 'support@example.test'],
        ]])
        ->recipientsField('hiddenRecipient', ['displayType' => 'hidden', 'defaultValue' => 'secret-staff@example.test'])
        ->create();

    $form->setNotifications([]);

    return Form::find()->id($form->id)->one();
}

function recipientsGraphqlQuery(Form $form, bool $isPublic, string $query, array $variables = []): array
{
    $schema = new GqlSchema([
        'name' => $isPublic ? 'Public Schema' : 'Recipients private',
        'uid' => StringHelper::UUID(),
        'isPublic' => $isPublic,
        'scope' => ["formieForms.{$form->uid}:read", "formieSubmissions.{$form->uid}:create"],
    ]);

    return WebRequestTestHelper::withWebRequestContext(function() use ($schema, $query, $variables): array {
        $gql = Craft::$app->getGql();
        $previous = null;

        try {
            $previous = $gql->getActiveSchema();
        } catch (GqlException) {
        }

        $gql->flushCaches();
        $gql->setActiveSchema($schema);

        try {
            return json_decode(json_encode($gql->executeQuery($schema, $query, $variables)), true);
        } finally {
            $gql->flushCaches();
            $gql->setActiveSchema($previous);
        }
    }, ['method' => 'POST']);
}

function recipientsGraphqlFormFields(Form $form, bool $isPublic): array
{
    $result = recipientsGraphqlQuery($form, $isPublic, '{ formieForm(handle: "' . $form->handle . '") { formFields { handle ... on Field_Recipients { options { label value } defaultValue } } } }');

    expect($result['errors'] ?? [])->toBe([]);

    return array_column($result['data']['formieForm']['formFields'], null, 'handle');
}

function recipientsGraphqlSave(Form $form, bool $isPublic, array $variables): array
{
    $handle = $form->handle;
    $result = recipientsGraphqlQuery($form, $isPublic, "mutation Save(\$department: String, \$teams: [String]) {
        save_{$handle}_Submission(message: \"Hello\", department: \$department, teams: \$teams) {
            id
            ... on {$handle}_Submission { department teams hiddenRecipient }
        }
    }", $variables);

    expect($result['errors'] ?? [])->toBe([]);

    return $result['data']["save_{$handle}_Submission"];
}

function recipientsGraphqlOptionToken(Form $form, string $handle, string $address): string
{
    foreach ($form->getFieldByHandle($handle)->getFieldOptions() as $option) {
        if ($option['label'] === ($address === 'sales@example.test' ? 'Sales' : 'Support')) {
            return $option['value'];
        }
    }

    throw new RuntimeException("No option for $address");
}

it('returns recipient tokens instead of addresses for form fields through a public schema', function (): void {
    $form = recipientsGraphqlForm();
    $fields = recipientsGraphqlFormFields($form, true);

    expect(json_encode($fields))->not->toContain('@example.test')
        ->and(array_column($fields['department']['options'], 'value'))->toBe([
            recipientsGraphqlOptionToken($form, 'department', 'sales@example.test'),
            recipientsGraphqlOptionToken($form, 'department', 'support@example.test'),
        ])
        ->and(array_column($fields['department']['options'], 'label'))->toBe(['Sales', 'Support'])
        ->and($fields['hiddenRecipient']['defaultValue'])->toBe(RecipientTokenHelper::encodeHidden('secret-staff@example.test'));
})->group('security');

it('returns the same recipient tokens as the client bootstrap through a public schema', function (): void {
    $form = recipientsGraphqlForm();
    $result = recipientsGraphqlQuery($form, true, '{ formieClientForm(handle: "' . $form->handle . '") { definition } }');
    $fields = recipientsGraphqlFormFields($form, true);
    $definition = json_encode($result['data']['formieClientForm']['definition']);

    foreach ($fields['department']['options'] as $option) {
        expect($definition)->toContain($option['value']);
    }

    expect($definition)->toContain($fields['hiddenRecipient']['defaultValue']);
})->group('security');

it('keeps recipient addresses for form fields through a private schema', function (): void {
    $fields = recipientsGraphqlFormFields(recipientsGraphqlForm(), false);

    expect(array_column($fields['department']['options'], 'value'))->toBe(['sales@example.test', 'support@example.test'])
        ->and($fields['hiddenRecipient']['defaultValue'])->toBe('secret-staff@example.test');
})->group('security');

it('returns recipient tokens instead of addresses for a submission saved through a public schema', function (): void {
    $form = recipientsGraphqlForm();
    $saved = recipientsGraphqlSave($form, true, [
        'department' => recipientsGraphqlOptionToken($form, 'department', 'sales@example.test'),
        'teams' => [recipientsGraphqlOptionToken($form, 'teams', 'sales@example.test'), recipientsGraphqlOptionToken($form, 'teams', 'support@example.test')],
    ]);

    expect(json_encode($saved))->not->toContain('@example.test')
        ->and($saved['department'])->toBe(recipientsGraphqlOptionToken($form, 'department', 'sales@example.test'))
        ->and($saved['teams'])->toBe(recipientsGraphqlOptionToken($form, 'teams', 'sales@example.test') . ',' . recipientsGraphqlOptionToken($form, 'teams', 'support@example.test'))
        ->and($saved['hiddenRecipient'])->toBe(RecipientTokenHelper::encodeHidden('secret-staff@example.test'));

    // The tokens still route the submission to the configured addresses.
    $submission = Submission::find()->id($saved['id'])->status(null)->isSpam(null)->one();

    expect($submission->getFieldValue('department')->value)->toBe('sales@example.test')
        ->and($submission->getFieldValue('teams')->values())->toBe(['sales@example.test', 'support@example.test'])
        ->and($submission->getFieldValue('hiddenRecipient')->value)->toBe('secret-staff@example.test');
})->group('security');

it('keeps recipient addresses for a submission saved through a private schema', function (): void {
    $form = recipientsGraphqlForm();
    $saved = recipientsGraphqlSave($form, false, [
        'department' => recipientsGraphqlOptionToken($form, 'department', 'support@example.test'),
        'teams' => [recipientsGraphqlOptionToken($form, 'teams', 'sales@example.test')],
    ]);

    expect($saved['department'])->toBe('support@example.test')
        ->and($saved['teams'])->toBe('sales@example.test')
        ->and($saved['hiddenRecipient'])->toBe('secret-staff@example.test');
})->group('security');
