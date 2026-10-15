# Static Translations

Static translations provide locale-specific text for messages defined in PHP or Twig, such as Formie’s validation errors, optional-field indicators, and upload prompts. Craft calls these [static message translations](https://craftcms.com/docs/5.x/system/sites.html#static-message-translations). Store them in PHP files under your project’s `translations/{locale}/` directory.

Use `formie.php` for Formie’s built-in messages and `site.php` for strings defined in your own templates or modules. These files work on single-site and multi-site projects; the language determines which file is used.

Form labels, instructions, option labels, and notification copy are edited in the form builder. To give that content different wording per Craft site, use [Multi-Site content translation](/forms/multi-site#content-translation).

## Formie-Owned Strings (`formie` Category)

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

Browser JavaScript validation messages are seeded from the same strings via `Rendering::getBrowserJsTranslations()`. Override them in `formie.php` the same way.

## Project Strings (`site` Category)

Use `translations/{locale}/site.php` for your own plugin, module, or template strings — not for form field labels created in the Formie builder.

Formie uses `site` in a few places for Craft element names (for example site names in the builder site switcher). That follows Craft conventions for project-level copy.

## Rendering Behaviour

Formie translates its built-in strings through `Craft::t('formie', …)` and passes the resolved browser messages to JavaScript. The source keys in `formie.php` must match the plugin’s English strings exactly, including placeholders such as `{label}`.

Form content is output as written in the builder after applying any site overrides. Adding a field label to `formie.php` or `site.php` does not translate it. Site overrides are tied to a Craft site, so two sites with the same language can still use different form wording.

## Troubleshooting

**Validation messages stay in English**

Check that the file is in the directory for the active locale and that its keys match the English source strings. If a field has its own validation message configured in the builder, edit that message there. Plugin-wide defaults can be configured under **Formie → Settings → Defaults**.

**Field labels or notification copy do not change**

Edit the content in the form builder. For another Craft site, use [site overrides](/forms/multi-site#content-translation); translation files only apply to messages passed through Craft’s translation system.
