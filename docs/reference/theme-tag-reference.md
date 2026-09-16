# Theme Tag Reference

Theme tags identify the parts of Formie’s rendered markup that you can configure. Use [Theme Config](/theming/theme-config) for a worked example, then look up the tags below when targeting a particular wrapper, field or control.

## Theme Tags
As you can see above, you pass a Twig object with keys for different parts of the rendered HTML. Each key can define attributes, a different tag, or even remove that element entirely by returning `false` or `null`.

Formie exposes several of these theme tags. Some apply to the overall form, some apply to all fields, and some are specific to particular field types. Every tag definition can use the following properties:

::: reference
### `reset`

**Type:** `Boolean`

Whether to retain or remove Formie's default `formie-*` classes for the element.
:::

::: reference
### `tag`

**Type:** `String`

The HTML tag to use for that element.
:::

::: reference
### `attributes`

**Type:** `Object`

A collection of HTML attributes for the element. Values such as `class`, `data`, `style`, and `aria` can be arrays or nested objects. This works much like Craft's [`attr`](https://craftcms.com/docs/4.x/functions.html#attr) helper.
:::

::: reference
### `cssVars`

**Type:** `Object`

CSS custom properties to merge into the element's inline style.
:::

::: reference
### `prepend` / `append`

**Type:** `Array|Object`

Extra content nodes to inject before or after the element content.
:::


```twig
{{ craft.formie.renderForm('contactForm', {
    themeConfig: {
        pages: {
            tag: 'fieldset',
            reset: true,
            attributes: {
                class: ['one', 'two'],
                disabled: true,
                readonly: false,
                style: {
                    'background-color': 'red',
                    'font-size': '20px',
                },
            },
        },
    },
}) }}
```

The available tags are grouped below.

### Form Tags
- `form`
- `formHeader`
- `formMessagesTop`
- `formNavigation`
- `formBody`
- `formFooter`
- `formMessagesBottom`
- `pages`
- `messageError`
- `messageSuccess`
- `formTitle`
- `pageTabs`
- `pageTab`
- `pageTabLink`
- `page`
- `pageContainer`
- `pageHeader`
- `pageBody`
- `pageFooter`
- `pageCaptchas`
- `pageButtons`
- `pageTitle`
- `rows`
- `row`
- `captchaContainer`
- `buttonContainer`
- `submitButton`
- `saveButton`
- `backButton`
- `progressWrapper`
- `progress`
- `progressContainer`
- `progressValue`
- `errors`
- `error`

### Shared Field Tags
- `field`
- `fieldLayout`
- `fieldLabel`
- `fieldRequired`
- `fieldOptional`
- `fieldInstructions`
- `fieldContent`
- `fieldControl`
- `fieldErrors`
- `fieldError`
- `subFieldRows`
- `subFieldRow`
- `nestedFieldRows`
- `nestedFieldRow`

### Address Field
- `subFieldRows`
- `subFieldRow`
- `fieldInput`

### Agree Field
- `fieldOptions`
- `fieldInput`
- `fieldOption`
- `fieldOptionLabel`

### Checkboxes Field
- `fieldInput`
- `fieldOptions`
- `fieldOption`
- `fieldOptionLabel`

### Date/Time Field
- `subFieldRows`
- `subFieldRow`
- `fieldInput`

### File Upload Field
- `fieldInput`
- `fieldSummary`
- `fieldSummaryContainer`
- `fieldSummaryItem`

### Group Field
- `nestedFieldRows`
- `nestedFieldRow`
- `nestedFieldContainer`

### Heading Field
- `fieldHeading`

### Multi-Line Text Field
- `fieldInput`
- `fieldLimit`
- `fieldRichText`

### Name Field
- `subFieldRows`
- `subFieldRow`
- `fieldInput`

### Phone Field
- `fieldInput`
- `fieldCountryInput`

### Radio Field
- `fieldInput`
- `fieldOptions`
- `fieldOption`
- `fieldOptionLabel`
- `fieldOtherOption`
- `fieldOtherOptionInput`
- `fieldOtherOptionLabel`
- `fieldOtherOptionText`

### Survey Likert Field
- `likertFieldLayout`
- `fieldColumnLabels`
- `fieldColumnLabelsRow`
- `fieldColumnLabel`
- `fieldInputs`
- `fieldInputsRow`
- `fieldOption`
- `fieldOptionLabel`
- `fieldInput`

Likert presentation styling is controlled through CSS custom properties on `likertFieldLayout`:

- `--formie-survey-likert-label-color`
- `--formie-survey-likert-option-background`

Likert radios reuse the standard radio field styles and behaviour.

```twig
{{ craft.formie.renderForm('surveyForm', {
    themeConfig: {
        surveyLikert: {
            likertFieldLayout: {
                cssVars: {
                    '--formie-survey-likert-option-background': 'var(--formie-color-surface)',
                },
            },
        },
    },
}) }}
```

### Survey Rank Field
- `rankFieldLayout`
- `fieldOptions`
- `fieldOption`
- `fieldRankHandle`
- `fieldOptionLabel`
- `fieldInput`

Rank presentation styling is controlled through CSS custom properties on `rankFieldLayout`:

- `--formie-survey-rank-list-gap`
- `--formie-survey-rank-item-background`
- `--formie-survey-rank-item-border-color`
- `--formie-survey-rank-handle-color`

### Survey Rating Field
- `ratingFieldLayout`
- `fieldOptions`
- `fieldOption`
- `fieldInput`
- `fieldOptionLabel`

Rating presentation styling is controlled through CSS custom properties on `fieldOptions`:

- `--formie-survey-rating-star-size`
- `--formie-survey-rating-star-spacing`
- `--formie-survey-rating-star-outline`
- `--formie-survey-rating-star-filled`

```twig
{{ craft.formie.renderForm('surveyForm', {
    themeConfig: {
        surveyRating: {
            fieldOptions: {
                cssVars: {
                    '--formie-survey-rating-star-size': '32px',
                },
            },
        },
    },
}) }}
```

### Repeater Field
- `nestedField`
- `nestedFieldWrapper`
- `nestedFieldRows`
- `nestedFieldRow`
- `nestedFieldContainer`
- `fieldAddButton`
- `fieldRemoveButton`

### Section Field
- `fieldSection`

### Signature Field
- `fieldCanvas`
- `fieldInput`
- `fieldRemoveButton`

### Single-Line Text Field
- `fieldInput`
- `fieldLimit`

### Summary Field
- `fieldSummary`
- `fieldSummaryBlocks`
- `fieldSummaryBlock`
- `fieldSummaryContainer`
- `fieldSummaryHeading`
- `fieldSummaryItem`
- `fieldSummaryLabel`
- `fieldSummaryValue`

### Table Field
- `fieldTableWrapper`
- `fieldTable`
- `fieldTableHeader`
- `fieldTableHeaderRow`
- `fieldTableHeaderColumn`
- `fieldTableBody`
- `fieldTableBodyRow`
- `fieldTableBodyColumn`
- `fieldAddButton`
- `fieldRemoveButton`
- `fieldTableRemoveColumn`
- `tableCheckboxInput`
- `tableColorInput`
- `tableDateInput`
- `tableEmailInput`
- `tableHeadingInput`
- `tableMultilineInput`
- `tableNumberInput`
- `tableSelectInput`
- `tableSinglelineInput`
- `tableTimeInput`
- `tableUrlInput`

### Captcha and Integration-Specific Tags

- `captcha`
- `stripePlaceholder`
