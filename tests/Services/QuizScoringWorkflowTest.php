<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\fields\Quiz;
use verbb\formie\client\models\{LoadContext, SubmitRequest};
use Tests\Support\WebRequestTestHelper;

it('scores submitted answers through the save workflow and persists the exact pass result', function (bool $correct, array $multiple, float $score, bool $passed, bool $multipleCorrect): void {
    $form = formie()->form()->settings(['scoringEnabled' => true, 'quizPassPercentage' => 70, 'disableCaptchas' => true])
        ->addField(Quiz::class, 'single', ['weightedScoreEnabled' => true, 'options' => [
            ['label' => 'Correct', 'value' => 'yes', 'isCorrect' => true, 'points' => 3],
            ['label' => 'Incorrect', 'value' => 'no', 'isCorrect' => false, 'points' => 0],
        ]])->addField(Quiz::class, 'multiple', ['fieldType' => Quiz::FIELD_TYPE_CHECKBOXES, 'options' => [
            ['label' => 'A', 'value' => 'a', 'isCorrect' => true],
            ['label' => 'B', 'value' => 'b', 'isCorrect' => true],
            ['label' => 'C', 'value' => 'c', 'isCorrect' => false],
        ]])->create();
    WebRequestTestHelper::withWebRequestContext(function () use ($form, $correct, $multiple, $score, $passed, $multipleCorrect): void {
        $bootstrap = Formie::$plugin->getClientFormBootstrapBuilder()->build($form, new LoadContext(['handle' => $form->handle]));
        $execution = Formie::$plugin->getSubmissionProcessor()->execute(new SubmitRequest([
            'handle' => $form->handle, 'action' => 'submit', 'session' => $bootstrap->session->toArrayRecursive(),
            'values' => ['single' => $correct ? 'yes' : 'no', 'multiple' => $multiple],
        ]));
        expect($execution->success)->toBeTrue();
        $saved = \verbb\formie\elements\Submission::find()->formId($form->id)->status(null)->all();
        expect($saved)->toHaveCount(1);
        $submission = $saved[0];
        $result = Formie::$plugin->getQuestionnaireScoring()->getQuizResultForSubmission($submission->id);
        expect($result)->not->toBeNull();
        expect($result->score)->toBe($score)->and($result->maxScore)->toBe(4.0)
            ->and($result->percentage)->toBe($score / 4 * 100)->and($result->passed)->toBe($passed);
        expect(array_column($result->questionResults, 'isCorrect', 'handle'))->toBe(['single' => $correct, 'multiple' => $multipleCorrect]);
    });
})->with([
    'all answers correct' => [true, ['a', 'b'], 4.0, true, true],
    'weighted answer incorrect' => [false, ['a', 'b'], 1.0, false, true],
    'missing one required choice' => [true, ['a'], 3.0, true, false],
    'includes an incorrect choice' => [true, ['a', 'b', 'c'], 3.0, true, false],
]);
