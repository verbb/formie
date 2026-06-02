<?php

declare(strict_types=1);

use Craft;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Name;

it('supports submission content serialization and projection helper contracts', function (): void {
    $form = formie()
        ->form(['title' => 'Submission Content'])
        ->singleLineTextField('fullName')
        ->emailField('email')
        ->create();

    $submission = formie()
        ->submission($form)
        ->with([
            'fullName' => 'Serialize Me',
            'email' => 'serialize@example.test',
        ])
        ->save();

    $serialized = $submission->serializeFieldValues();

    expect($serialized)->toBeArray()
        ->and($submission->getValuesAsString())->toBeArray()
        ->and($submission->getValuesAsArray())->toBeArray()
        ->and($submission->getValuesForExport())->toBeArray()
        ->and($submission->getValuesForSummary())->toBeArray();
});

it('supports captcha payload getters and setters', function (): void {
    $form = formie()
        ->form(['title' => 'Captcha Data'])
        ->singleLineTextField('fullName')
        ->create();

    $submission = formie()->submission($form)->with(['fullName' => 'Captcha'])->save();
    $submission->setCaptchaData('token', 'abc');

    expect($submission->getCaptchaData('token'))->toBe('abc');
});

it('round-trips orphaned submission content when the current field layout cannot resolve it', function (): void {
    $form = formie()
        ->form(['title' => 'Submission Orphaned Content'])
        ->singleLineTextField('fullName')
        ->create();

    $field = $form->getFieldByHandle('fullName');
    $submission = new Submission();
    $submission->setForm($form);

    $submission->getContentManager()->normalizeFromDb($submission, [
        $field->uid => 'Current Value',
        'legacy-field-uid' => [
            'legacyChildUid' => 'Legacy Value',
        ],
    ]);

    $serialized = $submission->serializeFieldValues();

    expect($submission->getFieldValue('fullName'))->toBe('Current Value')
        ->and($submission->getContentManager()->hasOrphanedValues($submission))->toBeTrue()
        ->and($serialized[$field->uid] ?? null)->toBe('Current Value')
        ->and($serialized['legacy-field-uid']['legacyChildUid'] ?? null)->toBe('Legacy Value');
});

it('persists multi-name subfields when values are normalized from request payload shape', function (): void {
    $form = formie()
        ->form(['title' => 'Multi Name Persistence'])
        ->nameField('multiName', [
            'useMultipleFields' => true,
            'rows' => (new Name(['useMultipleFields' => true]))->getSubFields(),
        ])
        ->create();

    $submission = new Submission();
    $submission->setForm($form);
    $submission->title = 'Test Submission ' . uniqid();

    // Match CP/front-end payload shape: fields[multiName][firstName|lastName]
    $submission->setFieldValueFromRequest('multiName', [
        'firstName' => 'Jane',
        'lastName' => 'Doe',
    ]);

    expect($submission->getFieldValue('multiName.firstName'))->toBe('Jane')
        ->and($submission->getFieldValue('multiName.lastName'))->toBe('Doe');

    $saved = Craft::$app->elements->saveElement($submission);
    expect($saved)->toBeTrue();

    $reloaded = Submission::find()
        ->id($submission->id)
        ->one();

    expect($reloaded)->not->toBeNull()
        ->and($reloaded?->getFieldValue('multiName.firstName'))->toBe('Jane')
        ->and($reloaded?->getFieldValue('multiName.lastName'))->toBe('Doe');
});
