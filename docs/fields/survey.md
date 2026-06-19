# Survey

Use the Survey field to collect questionnaire responses with multiple presentation types: radio buttons, checkboxes, dropdown, Likert scale, star rating, and rank.

## Presentation types

| Presentation | Use when |
|---|---|
| Radio | Single choice from a short list |
| Checkboxes | Multiple choices |
| Dropdown | Single choice from a longer list |
| Likert | Agreement or satisfaction scale |
| Rating | Star rating |
| Rank | Drag-and-drop preference ranking |

Text-based presentations are not included in the questionnaire Results tab.

## Likert scoring

For Likert presentations with **static columns**, enable **Weighted score** on the Columns table to assign a numeric score to each column.

Scoring is only available with static options. Dynamic or integration-driven option sources disable scoring automatically.

When scoring is enabled:

- Each submission’s Likert score is the sum of points for the selected columns.
- Multi-row Likert fields sum points across all answered rows.
- The Results tab shows the average score and maximum possible score per question.

### Likert rows

Enable **Multiple rows** to collect one scale response per statement. Row labels are configured separately from column labels. When only one row is configured, the field renders as a single-row Likert scale.

## Rank results

Rank submissions are aggregated in the Results tab using weighted position scoring. Higher-ranked options receive more weight in the bar chart.

## Key settings

- **Question** - The question text shown to respondents.
- **Presentation** - The display type for this field.
- **Static options / columns** - Define choices or Likert columns.
- **Likert rows** - Optional statements for multi-row Likert fields.

## Results tab

Survey fields that use options (radio, checkboxes, dropdown, Likert, rating, rank) appear on the form **Results** tab with response counts and option breakdowns. Likert fields with scoring enabled also show average scores.

