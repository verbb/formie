# Calculations field in detail

This guide walks through Calculations syntax, field references, built-in functions, and practical examples. Use it alongside the [Calculations field reference](/fields/calculations) when you are building pricing, scoring, or conditional output on a form.

## Prerequisites

- At least one [Calculations field](/fields/calculations) on a form
- Other fields whose values the formula will reference (Number fields work best for arithmetic)

## Field references

Always insert references with the variable picker in the formula editor. Formie stores stable tokens — not raw handles:

```text
{field:a1b2c3}
```

Do not type `{myFieldHandle}` manually. The token ID is generated when the field is created and stays stable even if you rename the handle.

### Sub-values and selectors

When a field exposes multiple referenceable values, add a selector:

```text
{field:a1b2c3:firstName}
{field:a1b2c3:lastName}
```

Concatenate name parts:

```text
{field:a1b2c3:firstName} ~ " " ~ {field:a1b2c3:lastName}
```

### Table columns and row scope

Table fields expose each column separately. Pick the column in the variable picker, then choose which rows to read:

```text
{field:a1b2c3:col3;scope=first}
{field:a1b2c3:col3;scope=all}
{field:a1b2c3:col3;scope=custom;rows=1,2,3}
```

To total a numeric column across all rows, use **All rows** scope in the picker — Formie sums numeric column values before they enter the formula.

## Operators

Calculations use [Symfony Expression Syntax](https://symfony.com/doc/current/reference/formats/expression_language.html). **Twig is not supported.**

### Arithmetic

| Operator | Meaning |
| --- | --- |
| `+` | Addition |
| `-` | Subtraction |
| `*` | Multiplication |
| `/` | Division |
| `%` | Modulus |
| `**` | Power |

```text
({field:a1b2c3} * 50) + ({field:d4e5f6} * 25)
```

### Comparison and logic

| Operator | Meaning |
| --- | --- |
| `==` / `!=` | Equal / not equal |
| `<` / `>` / `<=` / `>=` | Comparisons |
| `and` / `&&` | And |
| `or` / `\|\|` | Or |
| `not` / `!` | Not |

```text
{field:a1b2c3} > 10 and {field:d4e5f6} < 100
```

Also supported: strict comparisons (`===`, `!==`), regex `matches`, `in` / `not in`, ranges with `..`, and bitwise operators.

### Ternary and concatenation

```text
{field:a1b2c3} >= 10 ? {field:a1b2c3} * {field:d4e5f6} * 0.9 : {field:a1b2c3} * {field:d4e5f6}
```

Use `~` for string concatenation.

## Built-in functions

Formie registers these functions in addition to Symfony's defaults:

| Function | Example | Purpose |
| --- | --- | --- |
| `contains(subject, pattern)` | `contains({field:a1b2c3}, "vip")` | True if text contains pattern, or array contains value |
| `notContains(subject, pattern)` | `notContains({field:a1b2c3}, "test")` | Inverse of `contains` |
| `startsWith(subject, pattern)` | `startsWith({field:a1b2c3}, "http")` | True if text starts with pattern |
| `endsWith(subject, pattern)` | `endsWith({field:a1b2c3}, ".com")` | True if text ends with pattern |
| `empty(subject)` | `empty({field:a1b2c3})` | True for null, blank strings, empty arrays |
| `notEmpty(subject)` | `notEmpty({field:a1b2c3})` | Inverse of `empty` |
| `sum(value)` | `sum({field:a1b2c3})` | Sum of array values, or numeric passthrough |

Combine functions with ternary logic:

```text
notEmpty({field:a1b2c3}) ? {field:a1b2c3} * 1.1 : 0
```

## How values are coerced

On the front end, Formie casts numeric-looking strings to numbers before evaluation so `1 + 2` equals `3`, not `12`. Empty Number field values become `0`. Checkbox values arrive as arrays; when every item is numeric, Formie may sum them for use in arithmetic.

Text and Email fields can be referenced, but treat them as text unless you know the submitted value is always numeric.

## Formatting the result

Use the Calculations field settings before reaching for custom JavaScript:

- **Prefix / suffix** — currency symbols, units (`$`, ` kg`)
- **Number formatting** — decimal places, rounding
- **Visibility** — hide the calculated value from the user while still storing it

When display formatting needs locale-specific currency or custom rounding, use the [before/after evaluate events](/guides/fields/write-your-own-parsing-logic-for-the-calculations-field).

## Worked examples

### Simple line total

Number field `quantity`, Number field `unitPrice`, Calculations field `lineTotal`:

```text
{field:qty} * {field:price}
```

Set number formatting to 2 decimal places and prefix `$`.

### Volume discount

```text
{field:qty} >= 10 ? {field:qty} * {field:price} * 0.9 : {field:qty} * {field:price}
```

### Conditional label

```text
{field:score} >= 70 ? "Pass" : "Retake required"
```

### Shipping tier

Dropdown field `region` with values `local`, `national`, `international`; Number field `weight`:

```text
{field:region} == "local" ? 5 : ({field:region} == "national" ? 12 : 25 + {field:weight} * 2)
```

## Submitted value and integrations

Calculations stores the computed result at submit time. Treat it as derived output in exports, notifications, and CRM mappings — not as user-entered input.

If a formula depends on external APIs, complex branching, or data outside the form, custom code or an integration is usually easier to maintain than an enormous expression.

## Related

- [Calculations field](/fields/calculations)
- [Write your own parsing logic for the Calculations field](/guides/fields/write-your-own-parsing-logic-for-the-calculations-field)
- [Number field](/fields/number)
