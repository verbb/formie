# Quiz

Use the Quiz field to collect scored answers in questionnaires. Each Quiz field presents a question with radio buttons, checkboxes, or a dropdown, and supports optional weighted scoring and answer explanations.

## Key settings

- **Question** - The question text shown to respondents.
- **Field type** - Choose radio buttons, checkboxes, or a dropdown.
- **Static options** - Define answer choices, mark correct answers, and optionally assign point values when weighted scoring is enabled.
- **Weighted score** - When enabled, award different point values per option. Correct options should receive the highest values.
- **Enable answer explanation** - Provide rich text that explains the correct answer. Explanations are included in quiz results when a question is answered incorrectly.
- **Randomize options** - Shuffle option order on the front-end without changing stored values.

## Form scoring settings

When a form includes Quiz fields, configure scoring under **Behaviour → Quiz Scoring**:

- **Enable scoring** - Calculate scores when complete, non-spam submissions are saved.
- **Pass percentage** - Minimum percentage required to pass.
- **Allow retakes** - When disabled, the same logged-in user or IP address cannot submit the quiz again after a scored attempt.
- **Show score after submit** - Include a `quizResult` object in Ajax and client submit responses after a successful final-page submit.

## How scoring works

For each Quiz field on a scored form:

- **Radio / dropdown** - One point (or the selected option’s points when weighted scoring is enabled) is awarded when the selected option is marked correct.
- **Checkboxes** - Full credit requires selecting every correct option and no incorrect options. With weighted scoring enabled, partial credit is the sum of points for selected correct options, and the maximum is the sum of points on all correct options.

Scores are stored in `formie_submission_quiz_results` (one row per submission) with overall score, percentage, pass/fail status, and per-question JSON.

## Submit response

When **Show score after submit** is enabled, successful final-page submits include:

```json
{
  "quizResult": {
    "score": 4,
    "maxScore": 5,
    "percentage": 80,
    "passed": true,
    "passPercentage": 70,
    "questions": [
      {
        "handle": "questionHandle",
        "label": "Question label",
        "score": 1,
        "maxScore": 1,
        "isCorrect": true
      }
    ]
  }
}
```

Wrong answers may include an `answerExplanation` HTML string when enabled on the field.

## Results tab

Forms with Quiz fields and scoring enabled show a **Quiz summary** on the Results tab with attempt count, average percentage, pass rate, and pass threshold.

