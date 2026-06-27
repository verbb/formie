# Surveys, polls and quizzes end-to-end

Formie separates **questionnaire** use cases into two field types: **Survey** for collecting and aggregating responses, and **Quiz** for scored assessments with pass/fail logic. Simple **polls** — single-question votes — are built with Survey fields using Radio or Dropdown presentation.

This walkthrough covers all three patterns from form setup through results.

## Prerequisites

- [Survey field](/fields/survey)
- [Quiz field](/fields/quiz)

## Polls — one question, aggregate results

A poll is a single Survey field where respondents pick one option and you review vote counts on the form **Results** tab.

1. Create a form (for example `featurePoll`).
2. Add a **Survey** field.
3. Set **Presentation** to **Radio** (short lists) or **Dropdown** (longer lists).
4. Add **Static options** — one row per choice.
5. Publish and embed the form.

After submissions arrive, open the form → **Results**. The Survey field shows response counts and an option breakdown bar chart.

Polls do not use Quiz scoring. Keep options as static rows so results stay consistent in the Results tab.

## Surveys — multi-question feedback

Use Survey fields when you need varied question types in one form:

| Presentation | Use when |
| --- | --- |
| Radio | Single choice |
| Checkboxes | Multiple choices |
| Dropdown | Single choice from a longer list |
| Likert | Agreement or satisfaction scale |
| Rating | Star rating |
| Rank | Drag-and-drop preference ranking |

### Example: customer satisfaction form

1. Add Survey fields for each question:
   - **Overall rating** — Presentation: **Rating**
   - **Would you recommend us?** — Presentation: **Likert** with static columns (Strongly disagree → Strongly agree)
   - **Which features matter most?** — Presentation: **Checkboxes**
2. Save and publish.

Open **Results** to see per-question breakdowns. Likert and Rank fields use weighted scoring in the results aggregation (see below).

### Likert scoring

For Likert presentations with **static columns**:

1. Enable **Weighted score** on the Columns table.
2. Assign a numeric score to each column.

Scoring requires static options — dynamic or integration-driven sources disable it automatically.

When enabled, each submission's Likert score is the sum of points for selected columns. Multi-row Likert fields (enable **Multiple rows** for statement lists) sum across all answered rows. Results show average score and maximum possible score per question.

### Rank results

Rank submissions aggregate using weighted position scoring — higher-ranked options receive more weight in the bar chart on the Results tab.

## Quizzes — scored assessments

Use **Quiz** fields when respondents should receive a score and optionally pass or fail.

### Form setup

1. Create a form (for example `onboardingQuiz`).
2. Add one or more **Quiz** fields.
3. For each Quiz field:
   - Set **Question** text.
   - Choose **Field type** (radio, checkboxes, or dropdown).
   - Define **Static options** and mark correct answers.
   - Optionally enable **Weighted score** for partial credit on checkbox questions.
   - Optionally enable **Enable answer explanation** for rich-text feedback on wrong answers.
   - Optionally enable **Randomize options** to shuffle order on the front end.

4. Under **Behaviour → Quiz Scoring**:
   - Enable **Enable scoring**
   - Set **Pass percentage** (default mindset: 70%)
   - Choose **Allow retakes** — when disabled, the same logged-in user or IP cannot submit again after a scored attempt
   - Enable **Show score after submit** to include `quizResult` in Ajax/client responses

### How scoring works

| Field type | Scoring |
| --- | --- |
| Radio / dropdown | Full credit when the selected option is marked correct (option points when weighted scoring is on) |
| Checkboxes | Full credit requires every correct option and no incorrect ones; weighted mode allows partial credit |

Scores persist in `formie_submission_quiz_results` with overall score, percentage, pass/fail, and per-question JSON.

### Show results after submit

When **Show score after submit** is enabled, successful final-page submits return:

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

Wrong answers may include an `answerExplanation` HTML string when enabled on the field. Build your success page or client UI around this object.

### Quiz Results tab

Forms with Quiz fields and scoring enabled show a **Quiz summary** on **Results**: attempt count, average percentage, pass rate, and pass threshold.

## Combining Survey and Quiz on one form

You can mix field types on one form, but scoring only applies to Quiz fields when **Enable scoring** is on. Survey fields always feed the questionnaire Results tab; Quiz fields feed both per-question results and the Quiz summary when scoring is enabled.

A common pattern: Survey fields for demographic questions, Quiz fields for the scored section.

## Screening and incomplete submissions

Quiz scoring runs when complete, non-spam submissions are saved. Draft saves and spam submissions are excluded. Configure [submission screening](/forms/submission-screening) and [spam protection](/forms/spam-protection) so automated abuse does not pollute poll or quiz results.

## Related

- [Survey field](/fields/survey)
- [Quiz field](/fields/quiz)
- [Form builder](/forms/form-builder)
- [Submission screening rules in practice](/guides/submissions-workflows/submission-screening-rules-in-practice)
