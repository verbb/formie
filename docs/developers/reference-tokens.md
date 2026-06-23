# Reference tokens

Reference tokens are Formie's variable syntax for inserting dynamic submission, form, site, and user values into settings and content. They resolve **when Formie processes a submission** — not when your Twig template renders.

Use the variable picker in the control panel wherever it is available, or build the same tokens from Twig or PHP when you override settings in templates.

## Where tokens are used

Reference tokens appear anywhere Formie parses variable-aware content, including:

- Submit action messages, error messages, and other form rich-text settings
- [Email notifications](/forms/email-notifications) — subject, body, recipients
- [PDF templates](/templates/pdf-templates)
- Integration mapping fields (CRM, payments, messaging, and so on)
- Hidden field default values, calculations formulas, client event payloads, and spam keyword lines

::: tip
Calculations use the same field-reference tokens, but the formula editor is **not** Twig. See [Calculations](/fields/calculations) for expression syntax.
:::

## How tokens work

A token is a braced string Formie stores in settings, then resolves against the current submission:

```text
{target:identifier}
```

Examples:

| Token | Resolves to |
| --- | --- |
| `{submission:uid}` | The submission UID |
| `{form:name}` | The form title |
| `{field:a1b2c3}` | A field value (stable field reference, not the handle) |
| `{allFields}` | HTML summary of all fields |

Formie resolves tokens in stored content at submit time (or when previewing with sample submission data in the control panel). **Twig is not evaluated** inside submit action messages, notification bodies, or similar settings.

That means these do **not** work in those settings:

- Twig output syntax such as `{{ submission.uid }}`
- Legacy flat tokens such as `{submissionUid}` or `{field:myHandle}`

Use reference tokens instead — the same strings the variable picker inserts.

### Inline defaults

Add a fallback when the resolved value is empty:

```text
{submission:uid|pending}
```

### Transforms and metadata

Some contexts support transforms and extra metadata on the token body:

```text
{timestamp;transform=format;preset=isoDate}
{field:a1b2c3:email;scope=all}
```

The variable picker configures these for you. When building tokens manually, match the picker output. See [Calculations](/fields/calculations) for transform examples on field references.

## Built-in tokens

These tokens are available on every form. In the control panel, open the variable picker to insert them. From Twig, use `craft.formie.ref()` with the target and identifier shown in the **Twig** column.

### Summary selectors

| Label | Token | Twig |
| --- | --- | --- |
| All form fields | `{allFields}` | `craft.formie.ref('allFields')` |
| All non-empty fields | `{allContentFields}` | `craft.formie.ref('allContentFields')` |
| All visible fields | `{allVisibleFields}` | `craft.formie.ref('allVisibleFields')` |

### Form

| Label | Token | Twig |
| --- | --- | --- |
| Form name | `{form:name}` | `craft.formie.ref('form', 'name')` |
| Form handle | `{form:handle}` | `craft.formie.ref('form', 'handle')` |

### Submission

| Label | Token | Twig |
| --- | --- | --- |
| Submission title | `{submission:title}` | `craft.formie.ref('submission', 'title')` |
| Submission ID | `{submission:id}` | `craft.formie.ref('submission', 'id')` |
| Submission UID | `{submission:uid}` | `craft.formie.ref('submission', 'uid')` |
| Submission URL | `{submission:url}` | `craft.formie.ref('submission', 'url')` |
| Submission date | `{submission:date}` | `craft.formie.ref('submission', 'date')` |
| Submission status | `{submission:status}` | `craft.formie.ref('submission', 'status')` |

### Site

| Label | Token | Twig |
| --- | --- | --- |
| Site name | `{site:name}` | `craft.formie.ref('site', 'name')` |
| Site handle | `{site:handle}` | `craft.formie.ref('site', 'handle')` |
| Site URL | `{site:url}` | `craft.formie.ref('site', 'url')` |
| Site language | `{site:language}` | `craft.formie.ref('site', 'language')` |

### System

| Label | Token | Twig |
| --- | --- | --- |
| System name | `{system:name}` | `craft.formie.ref('system', 'name')` |
| System email | `{system:email}` | `craft.formie.ref('system', 'email')` |
| System reply-to | `{system:replyTo}` | `craft.formie.ref('system', 'replyTo')` |

### Current user

Values reflect the logged-in user when the submission is made, or the submission's linked user when applicable.

| Label | Token | Twig |
| --- | --- | --- |
| User IP address | `{user:ip}` | `craft.formie.ref('user', 'ip')` |
| User ID | `{user:id}` | `craft.formie.ref('user', 'id')` |
| User email | `{user:email}` | `craft.formie.ref('user', 'email')` |
| Username | `{user:username}` | `craft.formie.ref('user', 'username')` |
| User full name | `{user:fullName}` | `craft.formie.ref('user', 'fullName')` |
| User first name | `{user:firstName}` | `craft.formie.ref('user', 'firstName')` |
| User last name | `{user:lastName}` | `craft.formie.ref('user', 'lastName')` |

### Current date/time

| Label | Token | Twig |
| --- | --- | --- |
| Current date/time | `{timestamp}` | `craft.formie.ref('timestamp')` |

Use transform metadata for formatted output — for example `{timestamp;transform=format;preset=isoDate}`. The picker exposes the available format options.

### Environment

When Craft environment variables are available, the picker lists `{env:KEY}` tokens for each key. Build them from Twig with:

```twig
{{ craft.formie.ref('env', 'MY_ENV_KEY') }}
```

## Field tokens

Field values use a **stable field reference**, not the field handle. Each field on a form has a reference ID that stays consistent when the handle changes.

| Approach | When to use |
| --- | --- |
| Variable picker (control panel) | Default — inserts `{field:reference}` or `{field:reference:selector}` for you |
| `craft.formie.refField(form, 'handle')` | Twig overrides when you know the field handle |
| `craft.formie.ref('field', 'reference', 'selector')` | Advanced — when you already have the reference string |

```twig
{# Resolves the field handle to the stable reference token #}
{{ craft.formie.refField(form, 'email') }}

{# Nested or composite field selector #}
{{ craft.formie.refField(form, 'address', 'city') }}
```

Some fields expose multiple selectors (for example Name, Address, Date/Time, Table, and Repeater columns). Use the variable picker on that field in the form builder to see which selectors are available.

::: warning
Do not type `{field:myFieldHandle}`. Handles are for templates and `refField()` — stored tokens must use the field reference.
:::

## Custom variables

Register project-specific variables with `{custom:handle}` tokens. See [Custom variable sources](/developers/custom-variable-sources).

```twig
{{ craft.formie.ref('custom', 'acme_campaign') }}
```

## Building tokens from Twig

Use `craft.formie.ref()` and `craft.formie.refField()` when overriding form settings in templates — for example a dynamic [submit action message](/templates/overriding-settings):

```twig
{% set form = craft.formie.forms.handle('contactForm').one() %}

{% do form.setSettings({
    submitActionMessage: 'Thanks! Your reference is ' ~ craft.formie.ref('submission', 'uid'),
}) %}

{{ craft.formie.renderForm(form) }}
```

`craft.formie.ref(target, identifier, selector, options)` parameters:

| Parameter | Description |
| --- | --- |
| `target` | Token target — for example `submission`, `form`, `allFields`, `field`, `custom` |
| `identifier` | Target-specific identifier — omit for summary tokens such as `allFields` |
| `selector` | Optional field selector (third segment of a field token) |
| `options` | Optional metadata (transforms, scopes) and `default` for inline fallbacks |

Concatenate the returned string with other text using Twig's `~` operator.

## Resolving tokens in Twig templates

When you already have a submission in a Twig template and want the **resolved value** (not the token string), use:

```twig
{{ craft.formie.parseContent('{submission:uid}', submission) }}
{{ craft.formie.parseValue('{field:a1b2c3}', submission) }}
```

These run reference resolution immediately in the current request — unlike tokens stored in form settings, which resolve at submit time.

## PHP

```php
use verbb\formie\helpers\References;

// Build a token
References::token('submission', 'uid');
References::field('a1b2c3', 'email');
References::token('field', 'a1b2c3', 'email', ['scope' => 'all']);

// Resolve a token against a submission
References::parseContent('{submission:uid}', $submission);
References::parseValue('{field:a1b2c3}', $submission);
```
