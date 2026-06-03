<?php

declare(strict_types=1);

use craft\errors\GqlException;
use craft\models\GqlSchema;
use GraphQL\Error\Error;
use GraphQL\Type\Definition\ResolveInfo;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\gql\mutations\SubmissionMutation;
use verbb\formie\gql\types\input\FileUploadInputType;
use verbb\formie\helpers\ValidationHelper;
use Tests\Support\UploadTestHelper;

const FILE_UPLOAD_REQUIRED_GQL_FIXTURE = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4////fwAJ+wP9KobjigAAAABJRU5ErkJggg==';

it('treats graphql file upload mutation data as non-empty for required validation', function (): void {
    UploadTestHelper::ensureUploadVolume();

    $form = formie()
        ->form(['title' => 'GraphQL Required File Upload'])
        ->fileUploadField('resume', [
            'required' => true,
            'restrictFiles' => false,
            'allowedKinds' => ['image'],
        ])
        ->create();

    $field = $form->getFieldByHandle('resume');
    $submission = new Submission();
    $submission->setForm($form);
    $submission->setScenario(craft\base\Element::SCENARIO_LIVE);

    $gqlValue = FileUploadInputType::normalizeValue([[
        'fileData' => FILE_UPLOAD_REQUIRED_GQL_FIXTURE,
        'filename' => 'testing.png',
    ]]);

    $submission->setFieldValue('resume', $gqlValue);
    $value = $submission->getFieldValue('resume');

    expect($field->isValueEmpty($value, $submission))->toBeFalse();

    ValidationHelper::validateField(
        $submission,
        $field,
        $value,
        ValidationHelper::fieldValidationAttribute($field),
        $field->errorMessage,
    );

    expect($submission->getErrors())->toBeEmpty();
})->group('graphql');

it('accepts required file upload submissions over the graphql mutation path', function (): void {
    UploadTestHelper::ensureUploadVolume();

    $form = formie()
        ->form([
            'title' => 'GraphQL Required File Upload Mutation ' . uniqid(),
            'handle' => fileUploadRequiredGraphqlHandle(),
        ])
        ->fileUploadField('resume', [
            'required' => true,
            'restrictFiles' => false,
            'allowedKinds' => ['image'],
        ])
        ->create();

    $initialSubmissionCount = (int)Submission::find()->formId($form->id)->status(null)->isSpam(null)->isIncomplete(null)->count();
    $mutation = SubmissionMutation::createSaveMutation($form);
    $resolve = $mutation['resolve'];
    $resolveInfo = $this->createMock(ResolveInfo::class);
    $arguments = [
        'resume' => [[
            'fileData' => FILE_UPLOAD_REQUIRED_GQL_FIXTURE,
            'filename' => 'testing.png',
        ]],
    ];

    $gqlService = Craft::$app->getGql();
    $activeSchema = null;

    try {
        $activeSchema = $gqlService->getActiveSchema();
    } catch (GqlException) {
        // No active schema is normal in test runtime.
    }

    $gqlService->setActiveSchema(new GqlSchema([
        'name' => 'Formie File Upload Required GraphQL Test Schema',
        'scope' => [
            'formieSubmissions.all:create',
            'formieSubmissions.all:save',
        ],
    ]));

    try {
        $result = call_user_func($resolve, null, $arguments, null, $resolveInfo);
    } finally {
        $gqlService->setActiveSchema($activeSchema);
    }

    $finalSubmissionCount = (int)Submission::find()->formId($form->id)->status(null)->isSpam(null)->isIncomplete(null)->count();

    expect($result)->toBeInstanceOf(Submission::class)
        ->and($result->id)->not->toBeNull()
        ->and($finalSubmissionCount)->toBe($initialSubmissionCount + 1)
        ->and($result->getFieldValue('resume')->exists())->toBeTrue();
})->group('graphql');

it('rejects missing required file upload submissions over the graphql mutation path', function (): void {
    UploadTestHelper::ensureUploadVolume();

    $form = formie()
        ->form([
            'title' => 'GraphQL Missing Required File Upload ' . uniqid(),
            'handle' => fileUploadRequiredGraphqlHandle(),
        ])
        ->fileUploadField('resume', [
            'required' => true,
            'restrictFiles' => false,
            'allowedKinds' => ['image'],
        ])
        ->create();

    $initialSubmissionCount = (int)Submission::find()->formId($form->id)->status(null)->isSpam(null)->isIncomplete(null)->count();
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
        'name' => 'Formie File Upload Required GraphQL Test Schema',
        'scope' => [
            'formieSubmissions.all:create',
            'formieSubmissions.all:save',
        ],
    ]));

    $message = '';

    try {
        call_user_func($resolve, null, [], null, $resolveInfo);
        $this->fail('Expected GraphQL mutation to throw validation error.');
    } catch (Error $error) {
        $message = $error->getMessage();
    } finally {
        $gqlService->setActiveSchema($activeSchema);
    }

    $finalSubmissionCount = (int)Submission::find()->formId($form->id)->status(null)->isSpam(null)->isIncomplete(null)->count();

    expect($message)->toContain('resume')
        ->and($finalSubmissionCount)->toBe($initialSubmissionCount);
})->group('graphql');

function fileUploadRequiredGraphqlHandle(): string
{
    static $counter = 700;
    $alphabet = 'abcdefghijklmnopqrstuvwxyz';

    do {
        $first = intdiv($counter, 26) % 26;
        $second = $counter % 26;
        $handle = $alphabet[$first] . $alphabet[$second];
        $counter++;
    } while (Form::find()->handle($handle)->status(null)->one() !== null);

    return $handle;
}
