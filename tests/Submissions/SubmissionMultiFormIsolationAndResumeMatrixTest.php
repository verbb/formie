<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Name;
use verbb\formie\services\SubmissionDrafts;
use verbb\formie\state\DraftSubmissionState;

it('isolates draft progression content for same-form multiple instances and different forms', function (): void {
    $formA = formie()
        ->form([
            'title' => 'Isolation Form A',
            'handle' => isolationMatrixHandle(),
        ])
        ->singleLineTextField('headline')
        ->create();

    $formB = formie()
        ->form([
            'title' => 'Isolation Form B',
            'handle' => isolationMatrixHandle(),
        ])
        ->singleLineTextField('headline')
        ->create();

    $submissionDrafts = Formie::$plugin->getSubmissionDrafts();

    $sameFormInstanceA = $submissionDrafts->resolveFormInstanceKey($formA, null, [
        'scope' => 'submit',
        'instance' => 'render-a',
    ]);
    $sameFormInstanceB = $submissionDrafts->resolveFormInstanceKey($formA, null, [
        'scope' => 'submit',
        'instance' => 'render-b',
    ]);
    $differentFormInstance = $submissionDrafts->resolveFormInstanceKey($formB, null, [
        'scope' => 'submit',
        'instance' => 'render-a',
    ]);

    $submissionDrafts->saveDraftState(new DraftSubmissionState([
        'formInstanceKey' => $sameFormInstanceA,
        'content' => ['headline' => 'same-form-a'],
        'snapshot' => ['instance' => 'a'],
        'version' => 1,
    ]));
    $submissionDrafts->saveDraftState(new DraftSubmissionState([
        'formInstanceKey' => $sameFormInstanceB,
        'content' => ['headline' => 'same-form-b'],
        'snapshot' => ['instance' => 'b'],
        'version' => 1,
    ]));
    $submissionDrafts->saveDraftState(new DraftSubmissionState([
        'formInstanceKey' => $differentFormInstance,
        'content' => ['headline' => 'different-form'],
        'snapshot' => ['instance' => 'different'],
        'version' => 1,
    ]));

    $loadedA = $submissionDrafts->loadDraftState($sameFormInstanceA);
    $loadedB = $submissionDrafts->loadDraftState($sameFormInstanceB);
    $loadedDifferent = $submissionDrafts->loadDraftState($differentFormInstance);

    expect($loadedA?->content['headline'] ?? null)->toBe('same-form-a')
        ->and($loadedB?->content['headline'] ?? null)->toBe('same-form-b')
        ->and($loadedDifferent?->content['headline'] ?? null)->toBe('different-form');
});

it('issues resume tokens and rehydrates saved content in a new submission instance', function (): void {
    $form = formie()
        ->form([
            'title' => 'Resume Matrix',
            'handle' => isolationMatrixHandle(),
        ])
        ->singleLineTextField('fullName')
        ->emailField('email')
        ->create();

    $submission = formie()
        ->submission($form)
        ->with([
            'fullName' => 'Resume Value',
            'email' => 'resume@example.test',
        ])
        ->save();

    $submissionDrafts = new SubmissionDrafts();
    $key = $submissionDrafts->resolveFormInstanceKey($form, null, [
        'scope' => 'resume-matrix',
        'instance' => 'primary',
    ]);
    $state = new DraftSubmissionState([
        'formInstanceKey' => $key,
        'submissionId' => (int)$submission->id,
        'content' => $submission->serializeFieldValues(),
        'snapshot' => ['source' => 'resume-matrix'],
        'version' => 1,
    ]);

    $savedState = $submissionDrafts->saveDraftState($state);
    $resumeToken = $submissionDrafts->issueResumeToken($savedState);
    $verified = $submissionDrafts->verifyResumeToken($resumeToken->token, [SubmissionDrafts::RESUME_CAPABILITY_READ]);
    $loadedByToken = $verified ? $submissionDrafts->loadDraftState($verified) : null;

    $rehydrated = new Submission();
    $rehydrated->setForm($form);

    if (is_array($loadedByToken?->content)) {
        $rehydrated->getContentManager()->normalizeFromDb($rehydrated, $loadedByToken->content);
    }

    expect($resumeToken->token)->not->toBeEmpty()
        ->and($verified)->not->toBeNull()
        ->and($loadedByToken)->not->toBeNull()
        ->and($rehydrated->getFieldValue('fullName'))->toBe('Resume Value')
        ->and($rehydrated->getFieldValue('email'))->toBe('resume@example.test');
});

it('retains advanced values independently across different multipage forms', function (): void {
    $nameRows = (new Name(['useMultipleFields' => true]))->getSubFields();

    $formA = formie()
        ->form([
            'title' => 'Multi Form Advanced A',
            'handle' => isolationMatrixHandle(),
        ])
        ->multiPage(2)
        ->onPage(1)->nameField('profileName', ['useMultipleFields' => true, 'rows' => $nameRows])
        ->onPage(2)->emailField('contactEmail')
        ->create();

    $formB = formie()
        ->form([
            'title' => 'Multi Form Advanced B',
            'handle' => isolationMatrixHandle(),
        ])
        ->multiPage(2)
        ->onPage(1)->nameField('profileName', ['useMultipleFields' => true, 'rows' => $nameRows])
        ->onPage(2)->emailField('contactEmail')
        ->create();

    $submissionA = formie()
        ->submission($formA)
        ->with([
            'profileName' => ['firstName' => 'Ada', 'lastName' => 'Lovelace'],
            'contactEmail' => 'ada@example.test',
        ])
        ->save();

    $submissionB = formie()
        ->submission($formB)
        ->with([
            'profileName' => ['firstName' => 'Grace', 'lastName' => 'Hopper'],
            'contactEmail' => 'grace@example.test',
        ])
        ->save();

    expect($submissionA->getFieldValue('profileName.firstName'))->toBe('Ada')
        ->and($submissionA->getFieldValue('contactEmail'))->toBe('ada@example.test')
        ->and($submissionB->getFieldValue('profileName.firstName'))->toBe('Grace')
        ->and($submissionB->getFieldValue('contactEmail'))->toBe('grace@example.test');
});

function isolationMatrixHandle(): string
{
    static $counter = 2000;
    $alphabet = 'abcdefghijklmnopqrstuvwxyz';

    do {
        $first = intdiv($counter, 26) % 26;
        $second = $counter % 26;
        $handle = $alphabet[$first] . $alphabet[$second];
        $counter++;
    } while (Form::find()->handle($handle)->status(null)->one() !== null);

    return $handle;
}
