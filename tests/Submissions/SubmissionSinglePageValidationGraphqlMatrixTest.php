<?php

declare(strict_types=1);

use craft\errors\GqlException;
use craft\models\GqlSchema;
use GraphQL\Error\Error;
use GraphQL\Type\Definition\ResolveInfo;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Address;
use verbb\formie\fields\Name;
use verbb\formie\fields\SingleLineText;
use verbb\formie\gql\mutations\SubmissionMutation;

dataset('single_page_graphql_validation_cases', [
    'required text' => ['required text', ['requiredText']],
    'required email missing' => ['required email missing', ['requiredEmail']],
    'invalid email format' => ['invalid email format', ['formatEmail']],
    'single name required' => ['single name required', ['singleName']],
    'multi name first required' => ['multi name first required', ['multiName', 'firstName']],
    'address child required' => ['address child required', ['shippingAddress', 'address1']],
    'group child required' => ['group child required', ['groupBlock', 'groupRequiredText']],
    'repeater child required' => ['repeater child required', ['repeatBlock', 'repeatRequiredText']],
]);

it('blocks invalid single-page submissions over graphql mutation path', function (
    string $case,
    array $expectedErrorFragments
): void {
    $form = createGraphqlSinglePageValidationForm();
    $initialSubmissionCount = (int)Submission::find()->formId($form->id)->status(null)->isSpam(null)->isIncomplete(null)->count();
    $arguments = applyGraphqlValidationCase(buildValidGraphqlSinglePagePayload(), $case);
    $mutation = SubmissionMutation::createSaveMutation($form);
    $resolve = $mutation['resolve'];
    $resolveInfo = $this->createMock(ResolveInfo::class);

    $gqlService = Craft::$app->getGql();
    $activeSchema = null;

    try {
        $activeSchema = $gqlService->getActiveSchema();
    } catch (GqlException) {
        // No active schema is normal in test runtime.
    }

    $gqlService->setActiveSchema(new GqlSchema([
        'name' => 'Formie Submission Validation Test Schema',
        'scope' => [
            'formieSubmissions.all:create',
            'formieSubmissions.all:save',
        ],
    ]));

    $message = '';
    $extensions = [];

    try {
        call_user_func($resolve, null, $arguments, null, $resolveInfo);

        $this->fail('Expected GraphQL mutation to throw validation error.');
    } catch (Error $error) {
        $message = $error->getMessage();
        $extensions = $error->getExtensions();
    } finally {
        $gqlService->setActiveSchema($activeSchema);
    }

    $finalSubmissionCount = (int)Submission::find()->formId($form->id)->status(null)->isSpam(null)->isIncomplete(null)->count();

    expect($message)->not->toBeEmpty()
        ->and($extensions['category'] ?? null)->toBe('validation')
        ->and($extensions['errors'] ?? null)->toBeArray()
        ->and($finalSubmissionCount)->toBe($initialSubmissionCount);

    foreach ($expectedErrorFragments as $fragment) {
        expect($message)->toContain($fragment);
    }
})->with('single_page_graphql_validation_cases');

function applyGraphqlValidationCase(array $values, string $case): array
{
    return match ($case) {
        'required text' => array_replace($values, ['requiredText' => '']),
        'required email missing' => array_replace($values, ['requiredEmail' => '']),
        'invalid email format' => array_replace($values, ['formatEmail' => 'not-an-email']),
        'single name required' => array_replace($values, ['singleName' => '']),
        'multi name first required' => array_replace($values, ['multiName' => array_replace($values['multiName'], ['firstName' => ''])]),
        'address child required' => array_replace($values, ['shippingAddress' => array_replace($values['shippingAddress'], ['address1' => ''])]),
        'group child required' => array_replace($values, ['groupBlock' => array_replace($values['groupBlock'], ['groupRequiredText' => ''])]),
        'repeater child required' => array_replace($values, ['repeatBlock' => [[
            'repeatRequiredText' => '',
        ]]]),
        default => $values,
    };
}

function createGraphqlSinglePageValidationForm(): mixed
{
    $nameRows = markGraphqlNestedSubFieldRequired((new Name(['useMultipleFields' => true]))->getSubFields(), 'firstName');
    $addressRows = markGraphqlNestedSubFieldRequired((new Address())->getSubFields(), 'address1');

    return formie()
        ->form([
            'title' => 'Single Page GraphQL Validation Matrix ' . uniqid(),
            'handle' => graphqlMatrixHandle(),
        ])
        ->singleLineTextField('requiredText', ['required' => true])
        ->emailField('requiredEmail', ['required' => true])
        ->emailField('formatEmail', ['required' => true])
        ->nameField('singleName', ['required' => true, 'useMultipleFields' => false])
        ->nameField('multiName', ['useMultipleFields' => true, 'rows' => $nameRows])
        ->addressField('shippingAddress', ['rows' => $addressRows])
        ->groupField('groupBlock', [
            'rows' => [[
                'fields' => [[
                    'type' => SingleLineText::class,
                    'handle' => 'groupRequiredText',
                    'label' => 'Group Required',
                    'required' => true,
                ]],
            ]],
        ])
        ->repeaterField('repeatBlock', [
            'rows' => [[
                'fields' => [[
                    'type' => SingleLineText::class,
                    'handle' => 'repeatRequiredText',
                    'label' => 'Repeater Required',
                    'required' => true,
                ]],
            ]],
        ])
        ->create();
}

function buildValidGraphqlSinglePagePayload(): array
{
    return [
        'requiredText' => 'Required text value',
        'requiredEmail' => 'required@example.test',
        'formatEmail' => 'valid@example.test',
        'singleName' => 'Single Name',
        'multiName' => [
            'firstName' => 'Jane',
            'lastName' => 'Doe',
        ],
        'shippingAddress' => [
            'address1' => '1 Main Street',
            'city' => 'Nashville',
            'state' => 'TN',
            'zip' => '37011',
            'country' => 'US',
        ],
        'groupBlock' => [
            'groupRequiredText' => 'Group value',
        ],
        'repeatBlock' => [[
            'repeatRequiredText' => 'Repeater value',
        ]],
    ];
}

function markGraphqlNestedSubFieldRequired(array $rows, string $handle): array
{
    foreach ($rows as $rowIndex => $rowConfig) {
        foreach (($rowConfig['fields'] ?? []) as $fieldIndex => $fieldConfig) {
            if (($fieldConfig['handle'] ?? null) === $handle) {
                $rows[$rowIndex]['fields'][$fieldIndex]['required'] = true;
            }
        }
    }

    return $rows;
}

function graphqlMatrixHandle(): string
{
    static $counter = 500;
    $alphabet = 'abcdefghijklmnopqrstuvwxyz';

    do {
        $first = intdiv($counter, 26) % 26;
        $second = $counter % 26;
        $handle = $alphabet[$first] . $alphabet[$second];
        $counter++;
    } while (Form::find()->handle($handle)->status(null)->one() !== null);

    return $handle;
}
