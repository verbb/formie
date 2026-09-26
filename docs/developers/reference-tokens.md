# Reference Tokens

Reference tokens are Formie's variable syntax for inserting dynamic submission, form, site, and user values into settings and content. They resolve **when Formie processes a submission** — not when your Twig template renders.

Use the variable picker in the control panel wherever it is available, or build the same tokens from Twig or PHP when you override settings in templates.

## Where Tokens Are Used

Reference tokens appear anywhere Formie parses variable-aware content, including:

- Submit action messages, error messages, and other form rich-text settings
- [Email notifications](/forms/email-notifications) — subject, body, recipients
- [PDF templates](/templates/pdf-templates)
- Integration mapping fields (CRM, payments, messaging, and so on)
- Hidden field default values, calculations formulas, client event payloads, and spam keyword lines

::: tip
Calculations use the same field-reference tokens, but the formula editor is **not** Twig. See [Calculations](/fields/calculations) for expression syntax.
:::

## How Tokens Work

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

- Twig output syntax such as <code v-pre>{{ submission.uid }}</code>
- A field handle in place of the stable field reference inserted by the variable picker

Use reference tokens instead — the same strings the variable picker inserts.

### Inline Defaults

Add a fallback when the resolved value is empty:

```text
{submission:uid|pending}
```

### Transforms and Metadata

Some contexts support transforms and extra metadata on the token body:

```text
{timestamp;transform=format;preset=isoDate}
{field:a1b2c3:email;scope=all}
```

The variable picker configures these for you. When building tokens manually, match the picker output. See [Calculations](/fields/calculations) for transform examples on field references.

## Built-in Tokens

These tokens are available on every form. In the control panel, open the variable picker to insert them. From Twig, use `craft.formie.ref()` with the target and identifier shown in the **Twig** column.

### Summary Selectors

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

### Current User

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

### Current Date/Time

| Label | Token | Twig |
| --- | --- | --- |
| Current date/time | `{timestamp}` | `craft.formie.ref('timestamp')` |

Use transform metadata for formatted output — for example `{timestamp;transform=format;preset=isoDate}`. The picker exposes the available format options.

### Environment

Environment references are disabled by default. Add only safe names to `referenceEnvironmentAllowlist` in `config/formie.php`:

```php
return [
    'referenceEnvironmentAllowlist' => ['PUBLIC_CONTACT_EMAIL'],
];
```

The picker lists only those names and never includes their values. A reference to any other name is denied. Build an allowlisted token from Twig with:

```twig
{{ craft.formie.ref('env', 'PUBLIC_CONTACT_EMAIL') }}
```

## Field Tokens

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

## Custom Variables

Register project-specific variables with `{custom:handle}` tokens. See [Custom variable sources](/developers/custom-variable-sources).

```twig
{{ craft.formie.ref('custom', 'acme_campaign') }}
```

## Building Tokens from Twig

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

## Resolving Tokens in Twig Templates

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

## Exact Values and Text

Use exact resolution for a slot that expects a value, such as an integration mapping. A Name field returns its `NameFieldValue`, and a relation field returns its normalized query. Use interpolation when the destination is text. It asks the field for its string representation and encodes replacements for the destination.

In PHP code that already has a Formie submission, create the context once and reuse it:

```php
use verbb\formie\helpers\References;
use verbb\formie\references\ReferenceContext;
use verbb\formie\references\ReferenceOutputContext;

$context = ReferenceContext::forSubmission($submission);
$token = References::field($submission->getForm()->getFieldByHandle('name')->reference);
$result = References::resolveValue($token, $context);

if ($result->diagnostic !== null) {
    throw new \RuntimeException($result->diagnostic->value);
}

$name = $result->value;
$html = References::interpolateText('Hello ' . $token, $context, ReferenceOutputContext::Html);
```

The four immutable concepts have separate purposes: `ReferenceDefinition` describes a source for authoring, `ReferenceExpression` holds parsed syntax, `ReferenceContext` supplies evaluation inputs, and `ResolvedReference` holds the native result, field/definition metadata and any diagnostic. `requireValue()` throws `ReferenceException` when a consumer cannot continue after an error. Error messages omit submitted values and secrets.

### Output Contexts

| Context | Replacement behaviour |
| --- | --- |
| `PlainText` | Field-owned string value; no HTML interpretation |
| `Html` | Encode HTML special characters once; registered summary blocks use their trusted field templates |
| `EmailHeader` | Reject carriage returns, line feeds and NUL characters |
| `UrlComponent` | Percent-encode a value inserted into an authored URL component |
| `StructuredData` | Emit JSON-safe data with JSON encoding; whole native values should use exact resolution |

Do not pre-escape values or interpolate once and then reinterpret the result as another reference template. Submitted values containing `$SECRET`, braces or Twig remain data. Authored Twig template files and explicit template fields have their own rendering contract; the reference grammar never executes Twig or PHP.

### Stored Slots

Consumers declare whether a slot accepts `reference`, `text` or `literal`. Integration mappings persist this distinction:

```php
['kind' => 'reference', 'value' => '{field:instance-reference}']
['kind' => 'text', 'value' => 'Contact: {field:instance-reference}']
['kind' => 'literal', 'value' => '{This stays literal}']
```

The Variable Picker's field mode stores an exact reference. Its custom value editor stores interpolated text. Provider options store literals. The custom editor’s **Use text exactly as entered** action switches to a literal input; **Use variables in text** restores interpolation. A literal never becomes executable because it contains braces.

## Grammar and Diagnostics

The grammar uses `{source:identifier:selector;key=value|default}`. The selector applies to fields. `transform` names a registered transform; `scope`, `index` and `rows` select collection values. Metadata values and defaults are percent-encoded by the serializer, so `;`, `|`, braces, plus signs and percent signs round-trip. Version 1 is implicit; `;v=1` is accepted and unsupported versions produce an invalid-expression diagnostic. Use `References::token()` or `ReferenceParser::serialize()` instead of concatenating untrusted strings.

Exact field references identify one persisted form-field instance. Handles are secondary, form-local selectors; ambiguous handles fail instead of choosing the first match. Fixed child fields have their own references. A direct repeater-child reference requires `ReferenceContext::forSubmission($submission, rows: [$parentReference => 0])`, or select a collection through its parent token. Row indices are zero-based; the picker displays one-based row numbers. Table columns use parent selectors and never become synthetic persisted fields.

Deleted fields produce `missingField`; unscoped repeater children produce `missingRowScope`; undeclared selectors produce `invalidSelector`. Unknown sources and transforms produce `unknownSource` and `unknownTransform`. A default replaces a successfully resolved empty value (null, an empty string or list, or a field value that declares itself empty). Zero and false remain values. It does not hide a deleted field, missing extension or forbidden source.
