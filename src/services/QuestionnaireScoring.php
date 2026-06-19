<?php
namespace verbb\formie\services;

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Quiz;
use verbb\formie\fields\Survey;
use verbb\formie\helpers\Table;
use verbb\formie\models\RichText;
use verbb\formie\models\SubmissionQuizResult;
use verbb\formie\records\SubmissionQuizResult as SubmissionQuizResultRecord;

use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\helpers\Db;
use craft\helpers\Json;

class QuestionnaireScoring extends Component
{
    // Public Methods
    // =========================================================================

    public function formHasQuizFields(Form $form): bool
    {
        foreach ($form->getFormLayout()->getFields() as $field) {
            if ($field instanceof Quiz) {
                return true;
            }
        }

        return false;
    }

    public function shouldScoreSubmission(Form $form, Submission $submission): bool
    {
        if (!$form->settings->scoringEnabled || !$this->formHasQuizFields($form)) {
            return false;
        }

        if ($submission->isIncomplete || $submission->isSpam) {
            return false;
        }

        return true;
    }

    public function getRetakeError(Form $form, Submission $submission): ?string
    {
        if ($form->settings->quizAllowRetakes || !$form->settings->scoringEnabled || !$this->formHasQuizFields($form)) {
            return null;
        }

        if ($this->hasExistingAttempt($form, $submission)) {
            return Craft::t('formie', 'You have already completed this quiz.');
        }

        return null;
    }

    public function hasExistingAttempt(Form $form, Submission $submission): bool
    {
        $query = (new Query())
            ->from(['s' => Table::FORMIE_SUBMISSIONS])
            ->innerJoin(['e' => '{{%elements}}'], '[[e.id]] = [[s.id]]')
            ->innerJoin(['r' => Table::FORMIE_SUBMISSION_QUIZ_RESULTS], '[[r.submissionId]] = [[s.id]]')
            ->where([
                's.formId' => $form->id,
                's.isIncomplete' => false,
                's.isSpam' => false,
                'e.dateDeleted' => null,
            ]);

        if ($submission->id) {
            $query->andWhere(['not', ['s.id' => $submission->id]]);
        }

        $userId = $submission->userId ?: Craft::$app->getUser()->getId();

        if ($userId) {
            $query->andWhere(['s.userId' => $userId]);
        } else {
            $ipAddress = $submission->ipAddress ?: Craft::$app->getRequest()->getUserIP();

            if (!$ipAddress) {
                return false;
            }

            $query->andWhere(['s.ipAddress' => $ipAddress]);
        }

        return $query->exists();
    }

    public function scoreSubmission(Submission $submission): ?SubmissionQuizResult
    {
        $form = $submission->getForm();

        if (!$form || !$this->shouldScoreSubmission($form, $submission)) {
            if ($submission->id) {
                $this->deleteQuizResult($submission);
            }

            return null;
        }

        $questionResults = [];
        $totalScore = 0.0;
        $totalMaxScore = 0.0;

        foreach ($form->getFormLayout()->getFields() as $field) {
            if (!$field instanceof Quiz) {
                continue;
            }

            $questionResult = $this->_scoreQuizField($field, $submission);

            if ($questionResult === null) {
                continue;
            }

            $questionResults[] = $questionResult;
            $totalScore += (float)$questionResult['score'];
            $totalMaxScore += (float)$questionResult['maxScore'];
        }

        if ($questionResults === []) {
            $this->deleteQuizResult($submission);

            return null;
        }

        $percentage = $totalMaxScore > 0
            ? round(($totalScore / $totalMaxScore) * 100, 2)
            : 0.0;
        $passPercentage = (float)($form->settings->quizPassPercentage ?? 70);
        $passed = $percentage >= $passPercentage;

        $model = new SubmissionQuizResult([
            'submissionId' => (int)$submission->id,
            'score' => $totalScore,
            'maxScore' => $totalMaxScore,
            'percentage' => $percentage,
            'passed' => $passed,
            'questionResults' => $questionResults,
        ]);

        $this->saveQuizResult($model);

        return $model;
    }

    public function getQuizResultForSubmission(int $submissionId): ?SubmissionQuizResult
    {
        $row = (new Query())
            ->select(['*'])
            ->from(Table::FORMIE_SUBMISSION_QUIZ_RESULTS)
            ->where(['submissionId' => $submissionId])
            ->one();

        if (!$row) {
            return null;
        }

        return new SubmissionQuizResult([
            'id' => (int)$row['id'],
            'submissionId' => (int)$row['submissionId'],
            'score' => (float)$row['score'],
            'maxScore' => (float)$row['maxScore'],
            'percentage' => (float)$row['percentage'],
            'passed' => (bool)$row['passed'],
            'questionResults' => Json::decodeIfJson($row['questionResults'] ?? null) ?: [],
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getQuizResultPayload(SubmissionQuizResult $result, Form $form, bool $includeExplanations = true): ?array
    {
        $questions = [];

        foreach ($result->questionResults as $questionResult) {
            $item = [
                'handle' => $questionResult['handle'] ?? null,
                'label' => $questionResult['label'] ?? null,
                'score' => (float)($questionResult['score'] ?? 0),
                'maxScore' => (float)($questionResult['maxScore'] ?? 0),
                'isCorrect' => (bool)($questionResult['isCorrect'] ?? false),
            ];

            if ($includeExplanations && !empty($questionResult['answerExplanation'])) {
                $item['answerExplanation'] = $questionResult['answerExplanation'];
            }

            $questions[] = $item;
        }

        return [
            'score' => $result->score,
            'maxScore' => $result->maxScore,
            'percentage' => $result->percentage,
            'passed' => $result->passed,
            'passPercentage' => (float)($form->settings->quizPassPercentage ?? 70),
            'questions' => $questions,
        ];
    }

    public function getQuizSummary(Form $form): ?array
    {
        if (!$form->settings->scoringEnabled || !$this->formHasQuizFields($form)) {
            return null;
        }

        $rows = (new Query())
            ->select([
                'results.score',
                'results.maxScore',
                'results.percentage',
                'results.passed',
            ])
            ->from(['results' => Table::FORMIE_SUBMISSION_QUIZ_RESULTS])
            ->innerJoin(['submissions' => Table::FORMIE_SUBMISSIONS], '[[submissions.id]] = [[results.submissionId]]')
            ->innerJoin(['elements' => '{{%elements}}'], '[[elements.id]] = [[submissions.id]]')
            ->where([
                'submissions.formId' => $form->id,
                'submissions.isIncomplete' => false,
                'submissions.isSpam' => false,
                'elements.dateDeleted' => null,
            ])
            ->all();

        if ($rows === []) {
            return null;
        }

        $attemptCount = count($rows);
        $totalScore = 0.0;
        $totalPercentage = 0.0;
        $passCount = 0;

        foreach ($rows as $row) {
            $totalScore += (float)$row['score'];
            $totalPercentage += (float)$row['percentage'];

            if ((bool)$row['passed']) {
                $passCount++;
            }
        }

        return [
            'attemptCount' => $attemptCount,
            'averageScore' => round($totalScore / $attemptCount, 2),
            'averagePercentage' => round($totalPercentage / $attemptCount, 1),
            'passCount' => $passCount,
            'passRate' => round(($passCount / $attemptCount) * 100, 1),
            'passPercentage' => (float)($form->settings->quizPassPercentage ?? 70),
        ];
    }

    public function saveQuizResult(SubmissionQuizResult $model): bool
    {
        $record = SubmissionQuizResultRecord::findOne(['submissionId' => $model->submissionId])
            ?? new SubmissionQuizResultRecord();

        $record->submissionId = $model->submissionId;
        $record->score = $model->score;
        $record->maxScore = $model->maxScore;
        $record->percentage = $model->percentage;
        $record->passed = $model->passed;
        $record->questionResults = $model->questionResults;

        if (!$record->save()) {
            return false;
        }

        $model->id = (int)$record->id;

        return true;
    }

    public function deleteQuizResult(Submission $submission): void
    {
        if (!$submission->id) {
            return;
        }

        Db::delete(Table::FORMIE_SUBMISSION_QUIZ_RESULTS, [
            'submissionId' => $submission->id,
        ]);
    }

    /**
     * @return array<string, float>
     */
    public function getLikertColumnPoints(Survey $field): array
    {
        if (
            $field->displayType !== Survey::DISPLAY_LIKERT
            || !$field->scoringEnabled
        ) {
            return [];
        }

        $points = [];

        foreach ($field->getFieldOptions() as $option) {
            if (isset($option['optgroup'])) {
                continue;
            }

            $value = (string)($option['value'] ?? '');

            if ($value === '') {
                continue;
            }

            if (!isset($option['points']) || $option['points'] === '' || $option['points'] === null) {
                continue;
            }

            $points[$value] = (float)$option['points'];
        }

        return $points;
    }

    public function scoreLikertSubmission(Survey $field, mixed $stored, array $columnPoints): ?float
    {
        if ($columnPoints === []) {
            return null;
        }

        if ($field->usesLikertMultipleRows()) {
            if (!is_array($stored)) {
                return null;
            }

            $score = 0.0;
            $answered = false;

            foreach ($stored as $rowKey => $columnValue) {
                if (!is_string($columnValue) || $columnValue === '') {
                    continue;
                }

                if (!array_key_exists($columnValue, $columnPoints)) {
                    continue;
                }

                $score += $columnPoints[$columnValue];
                $answered = true;
            }

            return $answered ? $score : null;
        }

        $selected = $this->_extractSingleOptionValue($stored);

        if ($selected === []) {
            return null;
        }

        $columnValue = $selected[0];

        return array_key_exists($columnValue, $columnPoints)
            ? $columnPoints[$columnValue]
            : null;
    }

    public function getLikertMaxScore(Survey $field, array $columnPoints): float
    {
        if ($columnPoints === []) {
            return 0.0;
        }

        $maxColumnPoints = max($columnPoints);

        if ($field->usesLikertMultipleRows()) {
            return count($field->getEffectiveLikertRows()) * $maxColumnPoints;
        }

        return $maxColumnPoints;
    }


    // Private Methods
    // =========================================================================

    /**
     * @return array<string, array{label: string, isCorrect: bool, points: ?float}>|null
     */
    private function _getQuizOptionDefinitions(Quiz $field): ?array
    {
        $definitions = [];

        foreach ($field->options() as $option) {
            if (isset($option['optgroup'])) {
                continue;
            }

            $value = (string)($option['value'] ?? '');

            if ($value === '') {
                continue;
            }

            $definitions[$value] = [
                'label' => (string)($option['label'] ?? $value),
                'isCorrect' => !empty($option['isCorrect']),
                'points' => isset($option['points']) && $option['points'] !== '' && $option['points'] !== null
                    ? (float)$option['points']
                    : null,
            ];
        }

        return $definitions === [] ? null : $definitions;
    }

    /**
     * @param array<string, array{label: string, isCorrect: bool, points: ?float}> $definitions
     * @param string[] $selectedValues
     * @return array<string, mixed>|null
     */
    private function _scoreQuizField(Quiz $field, Submission $submission): ?array
    {
        $definitions = $this->_getQuizOptionDefinitions($field);

        if ($definitions === null) {
            return null;
        }

        $selectedValues = $this->_extractSelectedValues($field, $submission->getFieldValue($field->uid));
        $isMulti = $field->fieldType === Quiz::FIELD_TYPE_CHECKBOXES;
        $weighted = $field->weightedScoreEnabled;

        if ($isMulti) {
            $scoreData = $this->_scoreMultiSelectQuiz($definitions, $selectedValues, $weighted);
        } else {
            $scoreData = $this->_scoreSingleSelectQuiz($definitions, $selectedValues, $weighted);
        }

        $answerExplanation = null;

        if ($field->enableAnswerExplanation && !$scoreData['isCorrect']) {
            $answerExplanation = RichText::from($field->answerExplanation)->toHtml($submission, false);
        }

        return [
            'fieldUid' => $field->uid,
            'handle' => $field->handle,
            'label' => $field->getQuestionPlainText() ?: $field->label,
            'type' => 'quiz',
            'score' => $scoreData['score'],
            'maxScore' => $scoreData['maxScore'],
            'isCorrect' => $scoreData['isCorrect'],
            'answerExplanation' => $answerExplanation,
        ];
    }

    /**
     * @param array<string, array{label: string, isCorrect: bool, points: ?float}> $definitions
     * @param string[] $selectedValues
     * @return array{score: float, maxScore: float, isCorrect: bool}
     */
    private function _scoreSingleSelectQuiz(array $definitions, array $selectedValues, bool $weighted): array
    {
        $correctValues = array_keys(array_filter(
            $definitions,
            static fn(array $definition): bool => $definition['isCorrect'],
        ));

        if ($weighted) {
            $maxScore = 0.0;

            foreach ($correctValues as $correctValue) {
                $points = $definitions[$correctValue]['points'] ?? 1.0;
                $maxScore = max($maxScore, $points);
            }

            if ($maxScore <= 0) {
                $maxScore = 1.0;
            }
        } else {
            $maxScore = 1.0;
        }

        $selectedValue = $selectedValues[0] ?? null;
        $isCorrect = $selectedValue !== null
            && in_array($selectedValue, $correctValues, true);

        if (!$isCorrect) {
            return [
                'score' => 0.0,
                'maxScore' => $maxScore,
                'isCorrect' => false,
            ];
        }

        if ($weighted) {
            $points = $definitions[$selectedValue]['points'] ?? 1.0;

            return [
                'score' => $points,
                'maxScore' => $maxScore,
                'isCorrect' => true,
            ];
        }

        return [
            'score' => 1.0,
            'maxScore' => $maxScore,
            'isCorrect' => true,
        ];
    }

    /**
     * @param array<string, array{label: string, isCorrect: bool, points: ?float}> $definitions
     * @param string[] $selectedValues
     * @return array{score: float, maxScore: float, isCorrect: bool}
     */
    private function _scoreMultiSelectQuiz(array $definitions, array $selectedValues, bool $weighted): array
    {
        $correctValues = array_keys(array_filter(
            $definitions,
            static fn(array $definition): bool => $definition['isCorrect'],
        ));
        $selectedValues = array_values(array_unique($selectedValues));
        sort($selectedValues);
        $expectedValues = $correctValues;
        sort($expectedValues);

        if ($weighted) {
            $maxScore = 0.0;

            foreach ($correctValues as $correctValue) {
                $maxScore += $definitions[$correctValue]['points'] ?? 1.0;
            }

            $score = 0.0;

            foreach ($selectedValues as $selectedValue) {
                if (!isset($definitions[$selectedValue]) || !$definitions[$selectedValue]['isCorrect']) {
                    continue;
                }

                $score += $definitions[$selectedValue]['points'] ?? 1.0;
            }

            $isCorrect = $selectedValues === $expectedValues;

            return [
                'score' => $score,
                'maxScore' => $maxScore > 0 ? $maxScore : 1.0,
                'isCorrect' => $isCorrect,
            ];
        }

        $isCorrect = $selectedValues === $expectedValues;

        return [
            'score' => $isCorrect ? 1.0 : 0.0,
            'maxScore' => 1.0,
            'isCorrect' => $isCorrect,
        ];
    }

    /**
     * @return string[]
     */
    private function _extractSelectedValues(Quiz $field, mixed $stored): array
    {
        if ($stored === null || $stored === '') {
            return [];
        }

        if ($field->fieldType === Quiz::FIELD_TYPE_CHECKBOXES) {
            return $this->_extractMultiOptionValues($stored);
        }

        return $this->_extractSingleOptionValue($stored);
    }

    /**
     * @return string[]
     */
    private function _extractMultiOptionValues(mixed $stored): array
    {
        if (!is_array($stored)) {
            return [];
        }

        $values = [];

        foreach ($stored as $item) {
            if (is_string($item) && $item !== '') {
                $values[] = $item;
                continue;
            }

            if (is_array($item) && isset($item['value']) && $item['value'] !== '') {
                $values[] = (string)$item['value'];
            }
        }

        return $values;
    }

    /**
     * @return string[]
     */
    private function _extractSingleOptionValue(mixed $stored): array
    {
        if (is_string($stored) && $stored !== '') {
            return [$stored];
        }

        if (is_array($stored) && isset($stored['value']) && $stored['value'] !== '') {
            return [(string)$stored['value']];
        }

        return [];
    }
}
