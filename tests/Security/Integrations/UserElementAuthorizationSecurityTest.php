<?php

declare(strict_types=1);

use Tests\Support\IntegrationTestHelper;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\enums\SubmissionAuthorityType;
use verbb\formie\Formie;
use verbb\formie\helpers\References;
use verbb\formie\integrations\elements\User as UserIntegration;
use verbb\formie\models\IntegrationExecutionContext;

use craft\elements\User;

use yii\base\Event;
use yii\db\Query;

function userAuthorizationUser(string $prefix, string $password = 'Original-password-123!', bool $pending = false): User
{
    $user = new User([
        'username' => $prefix . uniqid(),
        'email' => $prefix . uniqid() . '@example.test',
        'firstName' => 'Before',
        'newPassword' => $password,
        'active' => !$pending,
        'pending' => $pending,
    ]);

    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();

    return User::find()->id($user->id)->status(null)->one();
}

function userAuthorizationFixture(User $target, array $overrides = [], bool $mapPassword = false): array
{
    $form = formie()->form()
        ->singleLineTextField('matchEmail')
        ->singleLineTextField('username')
        ->singleLineTextField('firstName')
        ->emailField('email')
        ->passwordField('password')
        ->create();
    $values = array_merge([
        'matchEmail' => $target->email,
        'username' => $target->username,
        'firstName' => 'After',
        'email' => $target->email,
        'password' => '',
    ], $overrides);
    $saved = formie()->submission($form)->with($values)->save();
    $submission = Submission::find()->id($saved->id)->status(null)->one();
    IntegrationTestHelper::primeVariableCacheForSubmission($submission);

    $reference = static function(string $handle) use ($form): string {
        $field = $form->getFieldByHandle($handle);

        return References::field($field->reference ?? $field->handle);
    };
    $attributeMapping = [
        'username' => $reference('username'),
        'firstName' => $reference('firstName'),
        'email' => $reference('email'),
    ];

    if ($mapPassword) {
        $attributeMapping['newPassword'] = $reference('password');
    }

    $integration = new UserIntegration([
        'name' => 'User authorization test',
        'handle' => 'userAuthorization' . uniqid(),
        'enabled' => true,
        'updateElement' => true,
        'overwriteValues' => true,
        'sendActivationEmail' => false,
        'attributeMapping' => $attributeMapping,
        'updateElementMapping' => [
            'email' => $reference('matchEmail'),
        ],
    ]);

    return [$integration, $submission];
}

function runUserAuthorizationIntegration(UserIntegration $integration, Submission $submission, SubmissionAuthorityType $type, ?int $actorId): \verbb\formie\models\IntegrationResult
{
    return Formie::$plugin->getIntegrationRunner()->runIntegration($integration, $submission, 'user-auth-' . uniqid(), 'synchronous', [
        'triggerEvent' => 'submit',
        'authorityType' => $type->value,
        'authorityScope' => 'security-test',
        'actorId' => $actorId,
    ]);
}

function userAuthorizationPasswordHash(int $userId): string
{
    return (string)(new Query())
        ->select(['password'])
        ->from('{{%users}}')
        ->where(['id' => $userId])
        ->scalar();
}

it('denies anonymous and unrelated visitor updates to an existing user', function(): void {
    $target = userAuthorizationUser('target');
    $outsider = userAuthorizationUser('outsider');

    foreach ([null, $outsider->id] as $actorId) {
        [$integration, $submission] = userAuthorizationFixture($target);
        $result = runUserAuthorizationIntegration($integration, $submission, SubmissionAuthorityType::VISITOR, $actorId);
        $stored = User::find()->id($target->id)->status(null)->one();

        expect($result->code)->toBe('existing_user_unauthorized')
            ->and($stored->firstName)->toBe('Before');
    }
})->group('security');

it('allows a visitor to update their own non-credential profile values', function(): void {
    $target = userAuthorizationUser('self');
    [$integration, $submission] = userAuthorizationFixture($target);
    $result = runUserAuthorizationIntegration($integration, $submission, SubmissionAuthorityType::VISITOR, $target->id);
    $stored = User::find()->id($target->id)->status(null)->one();

    expect($result->isSuccessful())->toBeTrue()
        ->and($stored->firstName)->toBe('After');
})->group('security');

it('rejects password and email changes for an authorized existing user', function(): void {
    $password = 'Original-password-123!';
    $target = userAuthorizationUser('credentials', $password);
    $originalHash = userAuthorizationPasswordHash((int)$target->id);
    [$passwordIntegration, $passwordSubmission] = userAuthorizationFixture($target, ['password' => 'Replacement-password-456!'], true);
    $passwordResult = runUserAuthorizationIntegration($passwordIntegration, $passwordSubmission, SubmissionAuthorityType::VISITOR, $target->id);

    [$emailIntegration, $emailSubmission] = userAuthorizationFixture($target, ['email' => 'replacement@example.test']);
    $emailResult = runUserAuthorizationIntegration($emailIntegration, $emailSubmission, SubmissionAuthorityType::VISITOR, $target->id);
    $stored = User::find()->id($target->id)->status(null)->one();

    expect($passwordResult->code)->toBe('existing_user_password_change_not_supported')
        ->and($emailResult->code)->toBe('existing_user_email_change_not_supported')
        ->and($stored->email)->toBe($target->email)
        ->and(userAuthorizationPasswordHash((int)$stored->id))->toBe($originalHash);
})->group('security');

it('authorizes the final user selected by extension events', function(): void {
    $actor = userAuthorizationUser('eventActor');
    $other = userAuthorizationUser('eventTarget');
    [$integration, $submission] = userAuthorizationFixture($actor);
    $handler = static function($event) use ($other): void {
        $event->payload = $other;
    };
    Event::on(Integration::class, Integration::EVENT_BEFORE_SEND_PAYLOAD, $handler);

    try {
        $result = runUserAuthorizationIntegration($integration, $submission, SubmissionAuthorityType::VISITOR, $actor->id);
        expect($result->code)->toBe('existing_user_unauthorized');
    } finally {
        Event::off(Integration::class, Integration::EVENT_BEFORE_SEND_PAYLOAD, $handler);
    }
})->group('security');

it('keeps password creation available for new users', function(): void {
    $missing = new User(['username' => 'new' . uniqid(), 'email' => 'new' . uniqid() . '@example.test']);
    $password = 'New-account-password-123!';
    [$integration, $submission] = userAuthorizationFixture($missing, ['password' => $password], true);
    $result = runUserAuthorizationIntegration($integration, $submission, SubmissionAuthorityType::VISITOR, null);
    $created = User::find()->email($missing->email)->status(null)->one();

    expect($result->isSuccessful())->toBeTrue()
        ->and($created)->not->toBeNull()
        ->and(Craft::$app->getSecurity()->validatePassword($password, userAuthorizationPasswordHash((int)$created->id)))->toBeTrue();
})->group('security');

it('does not activate an existing pending user', function(): void {
    $target = userAuthorizationUser('pending', pending: true);
    $admin = User::find()->admin(true)->status(null)->one();
    [$integration, $submission] = userAuthorizationFixture($target);
    $integration->activateUser = true;
    $result = runUserAuthorizationIntegration($integration, $submission, SubmissionAuthorityType::CONTROL_PANEL, $admin->id);
    $stored = User::find()->id($target->id)->status(null)->one();

    expect($result->isSuccessful())->toBeTrue()
        ->and($stored->active)->toBeFalse()
        ->and($stored->pending)->toBeTrue();
})->group('security');

it('preserves authority and created-user origin through durable delivery state', function(): void {
    $target = userAuthorizationUser('retry');
    [$integration, $submission] = userAuthorizationFixture($target);
    $context = new IntegrationExecutionContext(
        (int)$submission->id,
        (int)$submission->formId,
        (string)$integration->handle,
        'created-user-retry-' . uniqid(),
        'queued',
        authorityType: SubmissionAuthorityType::VISITOR->value,
        authorityScope: 'security-test-retry',
        actorId: null,
    );
    $attempts = Formie::$plugin->getDeliveryAttempts();
    $uid = $attempts->prepare($context, 'integration');
    $attempts->recordResource($uid, [
        'elementType' => User::class,
        'elementId' => $target->id,
        'url' => null,
        'formieUserOrigin' => 'created',
    ]);
    $integration->setDeliveryContext($context, $uid);
    $result = Formie::$plugin->getIntegrations()->sendIntegrationPayload($integration, $submission);
    $storedContext = $attempts->context($uid);

    expect($result->isSuccessful())->toBeTrue()
        ->and($storedContext->authorityType)->toBe(SubmissionAuthorityType::VISITOR->value)
        ->and($storedContext->authorityScope)->toBe('security-test-retry')
        ->and($storedContext->actorId)->toBeNull();
})->group('security');
