# Translations

Formie handles text on forms in three separate ways. Each has its own purpose — form copy from the builder, Formie-owned UI strings, and project-level developer strings.

## Three layers

| Layer | What it covers | Where it lives |
| --- | --- | --- |
| **Form content** | Labels, placeholders, messages, option labels, notification copy | Form builder (database). Per-site wording via [site overrides](/forms/multi-site#content-translation) on multi-site projects. |
| **Formie static strings** | Validation templates, `(optional)`, file-upload prompts, payment UI labels | `translations/{locale}/formie.php` and Formie’s bundled translation files |
| **Project strings** | Custom client event templates, developer-defined labels outside Formie forms | `translations/{locale}/site.php` |

Form content is **not** translated through Craft’s message system at render time. After site overrides are merged, Formie outputs the string from the database as-is.

## When to use what

### Multi-site form copy

Use the form builder **site switcher** and [content translation overrides](/forms/multi-site#content-translation). This is the supported way to give each Craft site different labels or messages while sharing one form structure.

Site overrides are keyed by **Craft site**, not locale file. That matters when two sites share a language but need different wording — for example `en-US` and `en-AU` with different labels for the same field.

### Single-site projects

Write copy directly in the form builder. You do not need translation files for field labels or form messages.

Use `formie.php` only if you want to override Formie-owned UI strings (see below).

### Formie-owned strings (`formie` category)

Use `translations/{locale}/formie.php` for strings Formie owns — strings passed to `Craft::t('formie', '…')` with a fixed English source key in the plugin.

Examples:

```php
// translations/de/formie.php
return [
    '{label} cannot be blank.' => '{label} darf nicht leer sein.',
    '(optional)' => '(optional)',
    'Are you sure you want to leave?' => 'Möchten Sie die Seite wirklich verlassen?',
    'Drop files here or browse to upload.' => 'Dateien hier ablegen oder zum Hochladen durchsuchen.',
];
```

These keys must match the **English source string** in the plugin exactly. Formie does not use separate message IDs.

Front-end JavaScript validation messages are seeded from the same strings via `Rendering::getFrontendJsTranslations()`. Override them in `formie.php` the same way.

### Project strings (`site` category)

Use `translations/{locale}/site.php` for your own plugin, module, or template strings — not for form field labels created in the Formie builder.

Formie uses `site` in a few places for Craft element names (for example site names in the builder site switcher). That follows Craft conventions for project-level copy.

## What does not belong in translation files

Do **not** put form builder content in `formie.php` or `site.php`:

- Field labels, instructions, placeholders
- Page labels and submit/back/save button labels
- Dropdown, radio, or checkbox option labels
- Success, error, limit, or scheduling messages
- Notification subject or body edited in the form builder

Field labels and form messages belong in the form builder (or [site overrides](/forms/multi-site#content-translation) on multi-site projects), not in translation files. If your project still has entries like `'Your name' => 'Votre nom'` in `formie.php`, move that wording into the builder — see [Upgrading From v3 → Form content translations](/get-started/upgrading-from-v3#form-content-translations).

## Multi-site vs static files

| Approach | Per Craft site | Editor-managed | Same language, different sites |
| --- | --- | --- | --- |
| CP site overrides | Yes | Yes | Yes |
| `formie.php` | No (per locale only) | No (deploy) | No |

For multi-site forms, prefer CP overrides. Static `formie.php` files remain the right place for plugin UI strings only.

## Rendering behaviour

When a form is rendered — via Twig, GraphQL, or the React bootstrap — Formie:

1. Loads the canonical form from the primary site.
2. Merges [site overrides](/forms/multi-site#content-translation) for the current site when multi-site is enabled.
3. Outputs user-authored strings without passing them through `Craft::t()`.

Plugin-owned strings (validation defaults, required indicators, upload prompts) still use `Craft::t('formie', …)` and your locale’s `formie.php` overrides.

Submissions load forms with the same site override merge, so notifications and exports use the wording for the submission’s site.

## Troubleshooting

**French site shows English field labels**

You need French [site overrides](/forms/multi-site#example-translating-a-contact-form) in the form builder, not entries in `formie.php`.

**German primary site shows English labels**

Check the canonical labels on the primary site in the builder. Form content is stored and output as written.

**Validation messages stay in English**

Add overrides to `translations/{locale}/formie.php` for the English source keys (for example `{label} cannot be blank.`), or set plugin-wide defaults under **Formie → Settings → Defaults**.

**Two English sites need different labels**

Use CP site overrides. A single `translations/en/formie.php` file cannot distinguish between Craft sites.

