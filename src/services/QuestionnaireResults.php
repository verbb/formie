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

        $scoring = Formie::$plugin->getQuestionnaireScoring();
        $aggregates = [];

        foreach ($questions as $field) {
            $aggregates[] = [
                'options' => $this->_buildOptionRows($field),
                'responses' => 0,
                'votes' => 0,
                'scoreSum' => 0,
                'scoreCount' => 0,
                'columnPoints' => $field instanceof Survey && $field->displayType === Survey::DISPLAY_LIKERT && $field->scoringEnabled
                    ? $scoring->getLikertColumnPoints($field) : [],
            ];
        }

        $totalResponses = 0;

        // Read bounded batches and decode each response once, regardless of how
        // many questions the form contains. Retain only aggregate counters.
        foreach ($this->_getSubmissionContentRows($form->id) as $row) {
            $content = Json::decodeIfJson($row['content'] ?? null);

            if (!is_array($content)) {
                continue;
            }

            $answered = false;

            foreach ($questions as $index => $field) {
                if (!array_key_exists($field->uid, $content)) {
                    continue;
                }

                $stored = $content[$field->uid];
                $ranked = $field instanceof Survey && $field->displayType === Survey::DISPLAY_RANK;
                $values = $ranked ? $this->_extractOrderedOptionValues($stored) : $this->_extractSelectedValues($field, $stored);
                $aggregate = &$aggregates[$index];

                if ($values !== []) {
                    $answered = true;
                    $aggregate['responses']++;

                    foreach ($values as $position => $value) {
                        $weight = $ranked ? count($values) - $position : 1;
                        $aggregate['options'][$value] ??= ['label' => $value, 'value' => $value, 'count' => 0];
                        $aggregate['options'][$value]['count'] += $weight;
                        $aggregate['votes'] += $weight;
                    }
                }

                if ($aggregate['columnPoints'] !== []) {
                    $score = $scoring->scoreLikertSubmission($field, $stored, $aggregate['columnPoints']);

                    if ($score !== null) {
                        $aggregate['scoreSum'] += $score;
                        $aggregate['scoreCount']++;
                    }
                }

                unset($aggregate);
            }

            $totalResponses += (int)$answered;
        }

        $aggregatedQuestions = [];

        foreach ($questions as $index => $field) {
            $aggregate = $aggregates[$index];
            $result = $this->_formatQuestionResult($field, $aggregate['options'], $aggregate['responses'], $aggregate['votes']);

            if ($aggregate['scoreCount'] > 0) {
                $result['scoring'] = [
                    'enabled' => true,
                    'averageScore' => round($aggregate['scoreSum'] / $aggregate['scoreCount'], 2),
                    'maxScore' => $scoring->getLikertMaxScore($field, $aggregate['columnPoints']),
                    'responseCount' => $aggregate['scoreCount'],
                ];
            }

            $aggregatedQuestions[] = $result;
        }

        $results = ['totalResponses' => $totalResponses, 'questions' => $aggregatedQuestions];

        $quizSummary = Formie::$plugin->getQuestionnaireScoring()->getQuizSummary($form);

        if ($quizSummary !== null) {
            $results['quizSummary'] = $quizSummary;
        }

        return $results;
    }


    // Private Methods
    // =========================================================================

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

    private function _getSubmissionContentRows(int $formId): iterable
    {
        $query = (new Query())
            ->select(['submissions.id', 'submissions.content'])
            ->from(['submissions' => Table::FORMIE_SUBMISSIONS])
            ->innerJoin(['elements' => '{{%elements}}'], '[[elements.id]] = [[submissions.id]]')
            ->where([
                'submissions.formId' => $formId,
                'submissions.isIncomplete' => false,
                'submissions.isSpam' => false,
                'elements.dateDeleted' => null,
            ])
            ->orderBy(['submissions.id' => SORT_ASC])
            ->limit(200);
        $lastId = 0;

        do {
            $rows = (clone $query)->andWhere(['>', 'submissions.id', $lastId])->all();

            foreach ($rows as $row) {
                $lastId = (int)$row['id'];
                yield $row;
            }
        } while (count($rows) === 200);
    }

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
