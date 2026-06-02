<?php

declare(strict_types=1);

use Tests\Support\MaliciousPayloads;
use verbb\formie\content\SubmissionContentNormalizer;
use verbb\formie\elements\Submission;

it('ignores unknown submission payload keys instead of persisting attacker-controlled extras', function (): void {
    $form = formie()
        ->form(['title' => 'Submission Payload Security'])
        ->singleLineTextField('fullName', ['required' => true])
        ->emailField('emailAddress', ['required' => true])
        ->create();

    $submission = new Submission();
    $submission->setForm($form);

    (new SubmissionContentNormalizer())->normalizeRequestPayload($submission, [
        'fullName' => 'Security Tester',
        'emailAddress' => 'security@example.test',
        MaliciousPayloads::unknownFieldHandle() => MaliciousPayloads::storedXssProbe(),
    ], '');

    $serialized = json_encode($submission->serializeFieldValues());

    expect($submission->getFieldValue('fullName'))->toBe('Security Tester')
        ->and($submission->getFieldValue('emailAddress'))->toBe('security@example.test')
        ->and($serialized)->not->toContain(MaliciousPayloads::unknownFieldHandle())
        ->and($serialized)->not->toContain(MaliciousPayloads::storedXssProbe());
})->group('security');
