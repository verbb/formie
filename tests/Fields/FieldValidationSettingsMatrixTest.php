<?php

declare(strict_types=1);

use Craft;
use DateTimeInterface;
use yii\validators\EmailValidator;
use Faker\Factory as FakerFactory;
use verbb\formie\base\Integration;
use verbb\formie\elements\Form;
use verbb\formie\helpers\References;
use verbb\formie\fields\Email;
use verbb\formie\fields\SingleLineText;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\Notification;

it('enforces single line text min and max constraints', function (): void {
    $form = formie()
        ->form(['title' => 'SingleLine MinMax'])
        ->singleLineTextField('username', [
            'limit' => true,
            'min' => 3,
            'max' => 5,
            'minType' => 'characters',
            'maxType' => 'characters',
        ])
        ->create();

    $tooShort = formie()->submission($form)->with(['username' => 'ab'])->allowValidationFailure()->save();
    $tooLong = formie()->submission($form)->with(['username' => 'abcdef'])->allowValidationFailure()->save();
    $valid = formie()->submission($form)->with(['username' => 'abcd'])->save();

    expect($tooShort)->toHaveFieldError('username')
        ->and($tooLong)->toHaveFieldError('username')
        ->and($valid->id)->not->toBeNull();
});

it('enforces multi line text min and max constraints and rich text mode', function (): void {
    $form = formie()
        ->form(['title' => 'MultiLine MinMax'])
        ->multiLineTextField('bio', [
            'limit' => true,
            'min' => 2,
            'max' => 3,
            'minType' => 'words',
            'maxType' => 'words',
            'useRichText' => true,
        ])
        ->create();

    $field = $form->getFieldByHandle('bio');
    $tooShort = formie()->submission($form)->with(['bio' => 'one'])->allowValidationFailure()->save();
    $tooLong = formie()->submission($form)->with(['bio' => 'one two three four'])->allowValidationFailure()->save();
    $valid = formie()->submission($form)->with(['bio' => 'one two'])->save();

    expect($field?->useRichText ?? false)->toBeTrue()
        ->and($tooShort)->toHaveFieldError('bio')
        ->and($tooLong)->toHaveFieldError('bio')
        ->and($valid->id)->not->toBeNull();
});

it('enforces match field contract', function (): void {
    $form = formie()
        ->form(['title' => 'Match Field'])
        ->singleLineTextField('password', ['required' => true])
        ->singleLineTextField('confirmPassword', ['required' => true])
        ->create();

    $passwordField = $form->getFieldByHandle('password');
    $confirmField = $form->getFieldByHandle('confirmPassword');
    expect($passwordField?->reference)->not->toBeNull();

    $confirmField->matchField = References::field((string)$passwordField->reference);
    expect(Craft::$app->elements->saveElement($form))->toBeTrue();

    $form = Form::find()->id($form->id)->one();
    expect($form)->not->toBeNull();

    $invalid = formie()
        ->submission($form)
        ->with([
            'password' => 'secret-one',
            'confirmPassword' => 'secret-two',
        ])
        ->allowValidationFailure()
        ->save();

    $valid = formie()
        ->submission($form)
        ->with([
            'password' => 'secret-one',
            'confirmPassword' => 'secret-one',
        ])
        ->save();

    expect($invalid)->toHaveFieldError('confirmPassword')
        ->and($valid->id)->not->toBeNull();
});

it('enforces unique value contract with second submission failure', function (): void {
    $form = formie()
        ->form(['title' => 'Unique Value'])
        ->singleLineTextField('username', ['uniqueValue' => true])
        ->create();

    $first = formie()->submission($form)->with(['username' => 'taken-name'])->save();
    $second = formie()->submission($form)->with(['username' => 'taken-name'])->allowValidationFailure()->save();

    expect($first->id)->not->toBeNull()
        ->and($second)->toHaveFieldError('username');
});

it('enforces email blocked domains and DNS validation settings contracts', function (): void {
    $form = formie()
        ->form(['title' => 'Email Domain Rules'])
        ->emailField('email', [
            'validateDomain' => true,
            'blockedDomains' => [
                ['label' => 'blocked.test'],
            ],
        ])
        ->create();

    /** @var Email|null $field */
    $field = $form->getFieldByHandle('email');
    $blocked = formie()->submission($form)->with(['email' => 'user@blocked.test'])->allowValidationFailure()->save();

    $rules = $field?->getElementValidationRules() ?? [];
    $hasDnsRule = false;
    foreach ($rules as $rule) {
        if (($rule[1] ?? null) === EmailValidator::class && ($rule['checkDNS'] ?? false) === true) {
            $hasDnsRule = true;
            break;
        }
    }

    expect($hasDnsRule)->toBeTrue()
        ->and($blocked)->toHaveFieldError('email');
})->group('slow');

it('supports name field single and multi mode contracts', function (): void {
    $singleForm = formie()
        ->form(['title' => 'Name Single'])
        ->nameField('name', ['useMultipleFields' => false])
        ->create();

    $multiForm = formie()
        ->form(['title' => 'Name Multi'])
        ->nameField('name', ['useMultipleFields' => true])
        ->create();

    $single = formie()->submission($singleForm)->with(['name' => 'Single Name'])->save();
    $multi = formie()->submission($multiForm)->with(['name' => ['firstName' => 'Multi', 'lastName' => 'Name']])->save();

    expect($single->id)->not->toBeNull()
        ->and($multi->id)->not->toBeNull()
        ->and($singleForm->getFieldByHandle('name')?->useMultipleFields ?? true)->toBeFalse()
        ->and($multiForm->getFieldByHandle('name')?->useMultipleFields ?? false)->toBeTrue();
});

it('supports getValueAs* and getValueFor* contracts for representative fields', function (): void {
    $form = formie()
        ->form(['title' => 'Value Conversion Matrix'])
        ->singleLineTextField('fullName')
        ->emailField('email')
        ->create();

    $submission = formie()->submission($form)->with([
        'fullName' => 'Value Matrix',
        'email' => 'matrix@example.test',
    ])->save();

    $notification = new Notification(['name' => 'n', 'handle' => 'n' . uniqid()]);
    $integration = new class extends Integration {};
    $faker = FakerFactory::create();

    $integrationTypes = [
        IntegrationField::TYPE_STRING,
        IntegrationField::TYPE_NUMBER,
        IntegrationField::TYPE_FLOAT,
        IntegrationField::TYPE_BOOLEAN,
        IntegrationField::TYPE_DATE,
        IntegrationField::TYPE_DATETIME,
        IntegrationField::TYPE_DATECLASS,
        IntegrationField::TYPE_ARRAY,
        IntegrationField::TYPE_PHONE,
    ];

    foreach (['fullName', 'email'] as $handle) {
        $field = $form->getFieldByHandle($handle);
        $value = $submission->getFieldValue($handle);

        expect($field?->getValueAsString($value, $submission))->not->toBeNull();
        expect($field?->getValueAsArray($value, $submission))->not->toBeNull();
        expect($field?->getValueForExport($value, $submission))->not->toBeNull();
        expect($field?->getValueForSummary($value, $submission))->not->toBeNull();
        expect($field?->getValueForCondition($value, $submission))->not->toBeNull();
        expect($submission->getFieldValueForReference($handle, $notification))->not->toBeNull();
        expect($field?->getValueForReference($value, $submission))->not->toBeNull();
        expect($submission->getFieldValueForReferenceBlock($handle, $notification))->not->toBeNull();
        expect($field?->getValueForReferenceBlock($value, $notification, $submission))->not->toBeNull();
        expect($submission->getFieldValueForVariable($handle, $notification))->not->toBeNull();
        expect($field?->getValueForReferenceBlock($value, $notification, $submission))->not->toBeNull();
        expect($field?->getValueForEmailPreview($faker))->not->toBeNull();

        foreach ($integrationTypes as $integrationType) {
            $integrationField = new IntegrationField([
                'handle' => 'integrationTarget',
                'name' => 'Integration Target',
                'type' => $integrationType,
            ]);

            $resolved = $field?->getValueForIntegration($value, $integrationField, $integration, $submission, $handle);
            assertRepresentativeIntegrationContract($integrationType, $resolved);
        }
    }
});

it('bridges legacy email label and placeholder overrides through reference block helpers', function (): void {
    $field = new class extends SingleLineText {
        public function hasEmailLabel(): bool
        {
            return false;
        }

        public function hasEmailPlaceholder(): bool
        {
            return false;
        }
    };

    expect($field->hasReferenceBlockLabel())->toBeFalse()
        ->and($field->hasReferenceBlockPlaceholder())->toBeFalse();
});

function assertRepresentativeIntegrationContract(string $integrationType, mixed $value): void
{
    switch ($integrationType) {
        case IntegrationField::TYPE_STRING:
            expect($value)->toBeString();
            return;
        case IntegrationField::TYPE_NUMBER:
            expect($value === null || is_int($value))->toBeTrue();
            return;
        case IntegrationField::TYPE_FLOAT:
            expect($value === null || is_float($value) || is_int($value))->toBeTrue();
            return;
        case IntegrationField::TYPE_BOOLEAN:
            expect($value === null || is_bool($value))->toBeTrue();
            return;
        case IntegrationField::TYPE_DATE:
        case IntegrationField::TYPE_DATETIME:
            expect($value === null || is_string($value))->toBeTrue();
            return;
        case IntegrationField::TYPE_DATECLASS:
            expect($value === null || $value instanceof DateTimeInterface)->toBeTrue();
            return;
        case IntegrationField::TYPE_ARRAY:
            expect($value)->toBeArray();
            return;
        case IntegrationField::TYPE_PHONE:
            expect($value === null || is_string($value))->toBeTrue();
            return;
        default:
            throw new \RuntimeException("Unhandled IntegrationField type in assertRepresentativeIntegrationContract: {$integrationType}");
    }
}
