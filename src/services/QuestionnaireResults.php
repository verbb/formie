<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\base\OptionsField;
use verbb\formie\base\QuestionnaireFieldInterface;
use verbb\formie\elements\Form;
use verbb\formie\fields\Quiz;
use verbb\formie\fields\Survey;
use verbb\formie\helpers\Table;

use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\helpers\Json;

class QuestionnaireResults extends Component
{
    // Public Methods
    // =========================================================================

    public function getResults(Form $form): ?array
    {
        $questions = $this->_getQuestionnaireFields($form);

        if ($questions === []) {
            return null;
        }

        $contentRows = $this->_getSubmissionContentRows($form->id);

        $aggregatedQuestions = array_map(function(QuestionnaireFieldInterface&OptionsField $field) use ($contentRows): array {
            return $this->_aggregateQuestion($field, $contentRows);
        }, $questions);

        $results = [
            'totalResponses' => $this->_countResponsesWithAnswers($questions, $contentRows),
            'questions' => $aggregatedQuestions,
        ];

        $quizSummary = Formie::$plugin->getQuestionnaireScoring()->getQuizSummary($form);

        if ($quizSummary !== null) {
            $results['quizSummary'] = $quizSummary;
        }

        return $results;
    }


    // Private Methods
    // =========================================================================

    /**
     * @return array<int, QuestionnaireFieldInterface&OptionsField>
     */
    private function _getQuestionnaireFields(Form $form): array
    {
        $fields = [];

        foreach ($form->getFormLayout()->getFields() as $field) {
            if ($field instanceof QuestionnaireFieldInterface && $field instanceof OptionsField && $field->supportsQuestionnaireResults()) {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    private function _getSubmissionContentRows(int $formId): array
    {
        return (new Query())
            ->select(['submissions.content'])
            ->from(['submissions' => Table::FORMIE_SUBMISSIONS])
            ->innerJoin(['elements' => '{{%elements}}'], '[[elements.id]] = [[submissions.id]]')
            ->where([
                'submissions.formId' => $formId,
                'submissions.isIncomplete' => false,
                'submissions.isSpam' => false,
                'elements.dateDeleted' => null,
            ])
            ->all();
    }

    /**
     * @param array<int, QuestionnaireFieldInterface&OptionsField> $questions
     */
    private function _countResponsesWithAnswers(array $questions, array $contentRows): int
    {
        if ($questions === []) {
            return 0;
        }

        $count = 0;

        foreach ($contentRows as $row) {
            $content = Json::decodeIfJson($row['content'] ?? null);

            if (!is_array($content)) {
                continue;
            }

            foreach ($questions as $question) {
                if (!array_key_exists($question->uid, $content)) {
                    continue;
                }

                if ($this->_fieldHasAnswer($question, $content[$question->uid])) {
                    $count++;
                    break;
                }
            }
        }

        return $count;
    }

    private function _aggregateQuestion(QuestionnaireFieldInterface&OptionsField $questionField, array $contentRows): array
    {
        if ($questionField instanceof Survey && $questionField->displayType === Survey::DISPLAY_RANK) {
            return $this->_aggregateRankQuestion($questionField, $contentRows);
        }

        $optionRows = $this->_buildOptionRows($questionField);
        $fieldUid = $questionField->uid;
        $totalResponses = 0;
        $totalVotes = 0;

        foreach ($contentRows as $row) {
            $content = Json::decodeIfJson($row['content'] ?? null);

            if (!is_array($content) || !array_key_exists($fieldUid, $content)) {
                continue;
            }

            $selectedValues = $this->_extractSelectedValues($questionField, $content[$fieldUid]);

            if ($selectedValues === []) {
                continue;
            }

            $totalResponses++;

            foreach ($selectedValues as $selectedValue) {
                if (!isset($optionRows[$selectedValue])) {
                    $optionRows[$selectedValue] = [
                        'label' => $selectedValue,
                        'value' => $selectedValue,
                        'count' => 0,
                    ];
                }

                $optionRows[$selectedValue]['count']++;
                $totalVotes++;
            }
        }

        $result = $this->_formatQuestionResult($questionField, $optionRows, $totalResponses, $totalVotes);

        return $this->_appendLikertScoringSummary($questionField, $contentRows, $result);
    }

    private function _aggregateRankQuestion(Survey $questionField, array $contentRows): array
    {
        $optionRows = $this->_buildOptionRows($questionField);
        $fieldUid = $questionField->uid;
        $totalResponses = 0;
        $totalVotes = 0;

        foreach ($contentRows as $row) {
            $content = Json::decodeIfJson($row['content'] ?? null);

            if (!is_array($content) || !array_key_exists($fieldUid, $content)) {
                continue;
            }

            $rankedValues = $this->_extractOrderedOptionValues($content[$fieldUid]);

            if ($rankedValues === []) {
                continue;
            }

            $totalResponses++;
            $rankCount = count($rankedValues);

            foreach ($rankedValues as $position => $selectedValue) {
                // Higher-ranked items receive more weight so the bar chart reflects preference.
                $weight = $rankCount - $position;

                if (!isset($optionRows[$selectedValue])) {
                    $optionRows[$selectedValue] = [
                        'label' => $selectedValue,
                        'value' => $selectedValue,
                        'count' => 0,
                    ];
                }

                $optionRows[$selectedValue]['count'] += $weight;
                $totalVotes += $weight;
            }
        }

        return $this->_appendLikertScoringSummary($questionField, $contentRows, $this->_formatQuestionResult(
            $questionField,
            $optionRows,
            $totalResponses,
            $totalVotes,
        ));
    }

    private function _appendLikertScoringSummary(
        QuestionnaireFieldInterface&OptionsField $questionField,
        array $contentRows,
        array $result,
    ): array {
        if (
            !($questionField instanceof Survey)
            || $questionField->displayType !== Survey::DISPLAY_LIKERT
            || !$questionField->scoringEnabled
        ) {
            return $result;
        }

        $scoring = Formie::$plugin->getQuestionnaireScoring();
        $columnPoints = $scoring->getLikertColumnPoints($questionField);

        if ($columnPoints === []) {
            return $result;
        }

        $fieldUid = $questionField->uid;
        $scores = [];

        foreach ($contentRows as $row) {
            $content = Json::decodeIfJson($row['content'] ?? null);

            if (!is_array($content) || !array_key_exists($fieldUid, $content)) {
                continue;
            }

            $score = $scoring->scoreLikertSubmission($questionField, $content[$fieldUid], $columnPoints);

            if ($score !== null) {
                $scores[] = $score;
            }
        }

        if ($scores === []) {
            return $result;
        }

        $result['scoring'] = [
            'enabled' => true,
            'averageScore' => round(array_sum($scores) / count($scores), 2),
            'maxScore' => $scoring->getLikertMaxScore($questionField, $columnPoints),
            'responseCount' => count($scores),
        ];

        return $result;
    }

    /**
     * @param array<string, array{label: string, value: string, count: int}> $optionRows
     */
    private function _formatQuestionResult(
        QuestionnaireFieldInterface&OptionsField $questionField,
        array $optionRows,
        int $totalResponses,
        int $totalVotes,
    ): array {
        $options = array_values(array_map(function(array $option) use ($totalVotes): array {
            $count = (int)$option['count'];
            $percentage = $totalVotes > 0 ? round(($count / $totalVotes) * 100, 1) : 0.0;

            return [
                'label' => $option['label'],
                'value' => $option['value'],
                'count' => $count,
                'percentage' => $percentage,
            ];
        }, $optionRows));

        return [
            'question' => [
                'label' => $questionField->getQuestionPlainText() ?: $questionField->label,
                'handle' => $questionField->handle,
            ],
            'options' => $options,
            'totalResponses' => $totalResponses,
            'totalVotes' => $totalVotes,
        ];
    }

    private function _fieldHasAnswer(QuestionnaireFieldInterface&OptionsField $field, mixed $stored): bool
    {
        if ($field instanceof Survey && $field->displayType === Survey::DISPLAY_RANK) {
            return $this->_extractOrderedOptionValues($stored) !== [];
        }

        return $this->_extractSelectedValues($field, $stored) !== [];
    }

    /**
     * @return string[]
     */
    private function _extractSelectedValues(QuestionnaireFieldInterface&OptionsField $field, mixed $stored): array
    {
        if ($stored === null || $stored === '') {
            return [];
        }

        if ($field instanceof Survey) {
            if ($field->displayType === Survey::DISPLAY_LIKERT && $field->usesLikertMultipleRows()) {
                return $this->_extractLikertMultipleRowsColumnValues($stored);
            }

            if ($field->displayType === Survey::DISPLAY_CHECKBOXES) {
                return $this->_extractMultiOptionValues($stored);
            }

            return $this->_extractSingleOptionValue($stored);
        }

        if ($field instanceof Quiz) {
            if ($field->fieldType === Quiz::FIELD_TYPE_CHECKBOXES) {
                return $this->_extractMultiOptionValues($stored);
            }

            return $this->_extractSingleOptionValue($stored);
        }

        return [];
    }

    private function _buildOptionRows(OptionsField $questionField): array
    {
        $optionRows = [];

        foreach ($questionField->getFieldOptions() as $option) {
            if (isset($option['optgroup'])) {
                continue;
            }

            $value = (string)($option['value'] ?? $option['label'] ?? '');

            if ($value === '') {
                continue;
            }

            $optionRows[$value] = [
                'label' => (string)($option['label'] ?? $value),
                'value' => $value,
                'count' => 0,
            ];
        }

        return $optionRows;
    }

    /**
     * @return string[]
     */
    private function _extractLikertMultipleRowsColumnValues(mixed $stored): array
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
    private function _extractOrderedOptionValues(mixed $stored): array
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
