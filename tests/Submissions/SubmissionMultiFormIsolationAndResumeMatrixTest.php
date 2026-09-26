<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Name;

it('isolates progress for separate form contexts and forms', function (): void {
    $formA = formie()->form()->singleLineTextField('headline')->create();
    $formB = formie()->form()->singleLineTextField('headline')->create();
    $service = Formie::$plugin->getSubmissionProgress();
    $formA->setDraftContext('render-a');
    $a = $service->upsertPageState($formA);
    $a->content = ['headline' => 'same-form-a'];
    $service->saveProgress($a);
    $formA->setDraftContext('render-b');
    $b = $service->upsertPageState($formA);
    $b->content = ['headline' => 'same-form-b'];
    $service->saveProgress($b);
    $other = $service->upsertPageState($formB);
    expect($a->id)->not->toBe($b->id)->not->toBe($other->id)
        ->and($service->getProgressState($formA)->content['headline'])->toBe('same-form-b');
    $formA->setDraftContext('render-a');
    expect($service->getProgressState($formA)->content['headline'])->toBe('same-form-a');
});

it('resolves saved content from the durable submission through a continue grant', function (): void {
    [$form, $submission] = continuitySubmission();
    $progress = Formie::$plugin->getSubmissionProgress()->upsertProgressState($form, $submission);
    $grants = Formie::$plugin->getSubmissionGrants();
    $grant = $grants->issue($submission, \verbb\formie\services\SubmissionGrants::CONTINUE, $progress->id);
    $verified = $grants->verify($grant->token, \verbb\formie\services\SubmissionGrants::CONTINUE, $form);
    $loaded = Submission::find()->id($verified->submissionId)->isIncomplete(true)->status(null)->one();
    expect($loaded->getFieldValue('message'))->toBe('Canonical content')
        ->and(Formie::$plugin->getSubmissionProgress()->loadProgress($verified->progressId)->content)->toBe([]);
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

    do {
        $handle = 'isolationForm' . $counter++;
    } while (Form::find()->handle($handle)->status(null)->one() !== null);

    return $handle;
}
