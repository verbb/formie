<?php

declare(strict_types=1);

use verbb\formie\fields\Survey;
use verbb\formie\Formie;
use verbb\formie\helpers\Table;

it('aggregates ranks and scores across submission batches while excluding ineligible responses', function (): void {
    $options = [
        ['label' => 'A', 'value' => 'a', 'points' => 1],
        ['label' => 'B', 'value' => 'b', 'points' => 3],
    ];
    $form = formie()->form()
        ->addField(Survey::class, 'ranking', ['displayType' => Survey::DISPLAY_RANK, 'options' => $options])
        ->addField(Survey::class, 'score', ['displayType' => Survey::DISPLAY_LIKERT, 'options' => $options, 'scoringEnabled' => true])
        ->create();
    $fields = $form->getFormLayout()->getFields();
    expect($fields[1]->scoringEnabled)->toBeTrue();
    $content = [
        $fields[0]->uid => ['b', 'a'],
        $fields[1]->uid => $fields[1]->getFieldOptions()[1]['value'],
    ];
    $db = Craft::$app->getDb();

    for ($i = 0; $i < 204; $i++) {
        $submission = formie()->submission($form)->save();
        $db->createCommand()->update(Table::FORMIE_SUBMISSIONS, [
            'content' => $i === 201 ? [] : $content,
            'isIncomplete' => $i === 202,
            'isSpam' => $i === 203,
        ], ['id' => $submission->id])->execute();
    }

    $results = Formie::$plugin->getQuestionnaireResults()->getResults($form);
    expect($results['totalResponses'])->toBe(201);
    expect($results['questions'][0]['totalVotes'])->toBe(603);
    expect(array_column($results['questions'][0]['options'], 'count', 'value'))->toBe(['a' => 201, 'b' => 402]);
    expect($results['questions'][1]['scoring'])->toBe([
        'enabled' => true,
        'averageScore' => 3.0,
        'maxScore' => 3.0,
        'responseCount' => 201,
    ]);
});


it('aggregates quiz summary totals in the database', function (): void {
    $form = formie()->form()->settings(['scoringEnabled' => true, 'quizPassPercentage' => 70])
        ->addField(\verbb\formie\fields\Quiz::class, 'quiz')->create();
    $scoring = Formie::$plugin->getQuestionnaireScoring();
    expect($scoring->getQuizSummary($form))->toBeNull();

    foreach ([[8, 80, true], [4, 40, false]] as [$score, $percentage, $passed]) {
        $submission = formie()->submission($form)->save();
        expect((new \verbb\formie\records\SubmissionQuizResult([
            'submissionId' => $submission->id,
            'score' => $score, 'maxScore' => 10, 'percentage' => $percentage, 'passed' => $passed,
        ]))->save())->toBeTrue();
    }

    expect($scoring->getQuizSummary($form))->toBe([
        'attemptCount' => 2, 'averageScore' => 6.0, 'averagePercentage' => 60.0,
        'passCount' => 1, 'passRate' => 50.0, 'passPercentage' => 70.0,
    ]);
});
