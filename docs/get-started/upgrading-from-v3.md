# Upgrading from v3

Use this guide to update a Formie 3 project to Formie 4. Review the required changes for your templates, custom fields and integrations first, then check the compatibility and operational sections before testing the upgraded site.

## Breaking Changes

Check the sections that match your project before deploying. Changes to custom integrations, templates and translation files may need manual edits even when stored form data migrates automatically. Working aliases are covered under [Deprecated Changes](#deprecated-changes).

Jump directly to the sections most likely to need attention:

- [Custom Fields](#custom-fields) if the project defines custom Formie fields.
- [Custom Integrations](#custom-integrations) if the project defines custom integrations, captchas, address providers, or payment providers.
- [Removed legacy captchas](#removed-legacy-captchas) if the site relied on Formie’s built-in Duplicate, JavaScript, or Honeypot captcha types.
- [Custom Front-End Validation](#custom-front-end-validation) if the project registers custom browser validators or overrides front-end message strings.
- [Translation Strings](#translation-strings) if the project overrides Formie **plugin UI** messages in translation files.
- [Form Content Translations](#form-content-translations) if the project mapped field labels or form messages through `formie.php` or `site.php`.
- [Submission and form statuses](#submission-and-form-statuses) if custom PHP, Twig, or module code references status services, models, events, or database tables.
- [Reports and submission export](#reports-and-submission-export) if editors expect an **Export** button on the submissions index.

## Custom Fields

### Field Preview

Field previews should now use preview schema, rather than a HTML template.

::: code-group
```php [Formie 3]
public function getPreviewInputHtml(): string
{
    return Craft::$app->getView()->renderTemplate('my-module/my-field/preview', [
        'field' => $this,
    ]);
}
```

```php [Formie 4]
public function defineFormBuilderPreviewSchema(): array
{
    return [
        SchemaHelper::previewInput(),
    ];
}
```
:::

### Field Templates

Use `getInputTemplatePath()` in place of the old front-end input template path method.

::: code-group
```php [Formie 3]
public static function getFrontEndInputTemplatePath(): string
{
    return 'my-module/my-field/input';
}
```

```php [Formie 4]
public static function getInputTemplatePath(): string
{
    return 'my-module/my-field/input';
}
```
:::

If the field needs template variables, extend `getInputTemplateVariables()`.

```php
use verbb\formie\elements\Form;

public function getInputTemplateVariables(Form $form, mixed $value): array
{
    return array_merge(parent::getInputTemplateVariables($form, $value), [
        'placeholder' => $this->placeholder,
    ]);
}
```

Avoid overriding `renderInput()` unless the field cannot be handled with templates and template variables.

## Custom Integrations

### Form Integration Settings

> [!CAUTION]
> Integrations that add settings to a form's Integrations tab need to move those settings to `defineFormSettingsSchema()`.

Integrations that provide a UI for the form builder's Integrations tab now define their controls with a schema, as opposed to Twig/Vue/HTML templates.

This aims to provide a more solid, consistent and performant approach to configuring these screens, rather than flaky templates tied to a framework.

::: code-group
```php [Formie 3]
public function getFormSettingsHtml($form): string
{
    $variables = $this->getFormSettingsHtmlVariables($form);

    return Craft::$app->getView()->renderTemplate('my-module/integration/form-settings', $variables);
}
```

```php [Formie 4]
use verbb\formie\base\FormInterface;
use verbb\formie\helpers\SchemaHelper;

protected function defineFormSettingsSchema(FormInterface $form): array
{
    $schema = parent::defineFormSettingsSchema($form);

    $schema[] = SchemaHelper::textField([
        'label' => Craft::t('formie', 'Endpoint URL'),
        'instructions' => Craft::t('formie', 'Enter the URL this integration should send submissions to.'),
        'name' => 'endpointUrl',
        'required' => true,
    ]);

    return $schema;
}

#[\verbb\formie\attributes\FormIntegrationSetting]
public ?string $endpointUrl = null;

```
:::

Always start with `parent::defineFormSettingsSchema($form)` unless you have a specific reason not to. The parent schema includes the standard Enabled setting.

Formie 4 only saves and populates form-level integration attributes annotated with `#[FormIntegrationSetting]`. Annotate every existing property used by your form schema. For registered integrations, undeclared values are discarded so form data cannot override plugin-level settings such as credentials or provider base URLs. Opaque settings for a temporarily unavailable integration are retained to avoid data loss, but are not hydrated at runtime.

Custom integrations that send to a URL configured on each form should use `requestPublicEndpoint()` or `deliverPayloadToPublicEndpoint()`. Provider API calls with fixed endpoints should continue to use `request()` or `deliverPayload()`.

### Captchas and Address Providers

> [!CAUTION]
> Custom captchas and address providers need to move to browser modules, and any front-end behaviour for them needs to be adapted to Formie's browser module system.

Captchas and address providers now use browser modules instead of ad-hoc JavaScript variables. In practice, that means moving both the PHP side and the front-end side of the integration to the current browser module approach.

::: code-group
```php [Formie 3]
public function getFrontEndJsVariables(Form $form, $page = null)
{
    return [
        'src' => $src,
        'onload' => 'new MyCaptcha();',
    ];
}
```

```php [Formie 4]
use verbb\formie\models\BrowserModuleEntry;
use verbb\formie\models\BrowserModuleContext;

public function getBrowserModule(BrowserModuleContext $context): ?BrowserModuleEntry
{
    return new BrowserModuleEntry([
        'id' => 'my-captcha',
        'src' => $this->scriptUrl,
        'config' => [
            'siteKey' => $this->siteKey,
        ],
    ]);
}
```
:::

See [Captcha Integration](/developers/custom-integration/captcha-integration) and [Address Provider Integration](/developers/custom-integration/address-provider-integration) for the fuller flow.

## Removed Legacy Captchas

Formie 4 no longer ships the **Duplicate**, **JavaScript**, or **Honeypot** captcha integrations that existed in earlier major versions. They are not available in the form builder’s captcha picker, and there is no compatibility shim that re-registers them as captcha integrations.

Instead, their behaviour is covered by **submission guards** — built-in passive checks configured globally under **Formie → Settings → Spam Protection → Submission Guards**.

### What They Were

- **Duplicate** attempted to block repeat submissions by comparing new requests to recent activity. In practice it was sensitive to caching, multi-page flows, and Ajax reloads, which produced hard-to-debug false positives and noisy support tickets.
- **JavaScript** was a lightweight “prove the browser ran our script” check. It duplicated behaviour that modern browsers, privacy tools, and static caching already interfere with, and it was a poor accessibility story compared to provider-backed challenges.
- **Honeypot** relied on a hidden field that legitimate users were expected to leave empty. Browser autofill, password managers, accessibility tools, and static or edge-cached HTML often touched that field anyway, which caused false positives; determined bots could also learn to avoid a known pattern.

Those approaches did not match how serious abuse is handled today: explicit provider verification, clearer spam reasons, and a single **screening** stage in the submission workflow.

### What to Use Instead

| Formie 3 | Formie 4 | Where to configure |
| --- | --- | --- |
| **Honeypot** | **Honeypot** submission guard | **Settings → Spam Protection → Submission Guards** |
| **Javascript** (including minimum submit time) | **Minimum submit time** submission guard | **Settings → Spam Protection → Submission Guards** |
| **Duplicate** | **Replay protection** submission guard | **Settings → Spam Protection → Submission Guards** |

Submission guards are **global** settings. They apply to all forms automatically when enabled. You do not enable them per form the way you did with the old built-in captchas.

All three guards are enabled by default after upgrade (honeypot on, three-second minimum submit time, replay protection on). Review **Settings → Spam Protection** after upgrading and adjust them for your site.

For stronger abuse protection, also enable one or more supported [Captcha integrations](/integrations/captchas/) (for example Cloudflare Turnstile, hCaptcha, or reCAPTCHA) and combine them with [Spam Protection](/forms/spam-protection) keyword rules where appropriate.

### How Guards Fit the Workflow

Request safeguards run before submission processing, including on draft saves and page changes. Custom REST and GraphQL clients must send the session tokens issued when the form loads; logging in does not bypass these checks.

Retrying a successful request with the same input recovers its result if the response was lost. Different input causes a conflict, except after validation errors, when visitors can correct their answers. Custom HTML/AJAX clients must post `expectedVersion`; client sessions carry `version`; administrative GraphQL edits use the record's `stateVersion` as `expectedVersion`.

The honeypot input and `formStartedAt` timestamp are rendered automatically for browser forms. Replay protection reuses Formie’s existing per-render `requestToken`.

See [Submission Screening](/forms/submission-screening) for the full screening order and [Spam Protection](/forms/spam-protection#submission-guards) for configuration detail.

### Settings and Storage Changes

Spam handling, keyword rules, submission guards, and captcha provider credentials now live on a single **Settings → Spam Protection** page. Legacy **Settings → Spam** and **Settings → Captchas** routes redirect there.

Those values are stored in Formie’s dedicated settings tables (`formie_spam_settings`, `formie_captcha_providers`) rather than `plugins.formie.settings` in project config. Legacy plugin settings keys are stripped on save and seeded into the new stores automatically while compatibility mode is enabled.

### Custom Code and GraphQL

If you had custom code or front-end automation that targeted the old Duplicate, JavaScript, or Honeypot captcha handles:

- Remove references to those captcha integration handles — they no longer exist.
- Switch spam-related checks to the replacement captcha integration handles you enable (for example `turnstile`, `recaptcha`), or hook into the screening stage if you need custom server-side checks.
- Update any GraphQL mutation arguments that referenced the removed captcha types.

Provider-backed captchas and spam rules still evaluate together in the **`screen`** stage. See [Submission Screening](/forms/submission-screening) for how that stage orders checks and how that relates to validation and saves.

## Front-End JavaScript Events

Formie 4 uses namespaced DOM event names. If you listen for old Formie event names, switch them to the new event names.

::: code-group
```js [Formie 3]
const form = document.querySelector('#formie-form');

form.addEventListener('onAfterFormieSubmit', (event) => {
  console.log(event.detail);
});
```

```js [Formie 4]
const form = document.querySelector('#formie-form');

form.addEventListener('formie:submit:result', (event) => {
  console.log(event.detail);
});
```
:::

Common event changes are:

Formie 3 | Formie 4
--- | ---
`onFormieLoaded` | `formie:mount:after`
`onFormieInit` | `formie:mount:after`
`onFormieReady` | `formie:mount:after`
`onBeforeFormieSubmit` | `formie:submit:before`
`onFormieSubmit` | `formie:submit:after`
`onAfterFormieSubmit` | `formie:submit:result`
`onFormieSubmitError` | `formie:submit:result`
`onFormiePageToggle` | `formie:page:navigate:after`
`onFormieValidate` | `formie:stage:validate:before`
`onAfterFormieValidate` | `formie:stage:validate:after`

Some mappings are approximate because the front-end submission flow has changed. Use the new event that matches the point in the form lifecycle you need.

The browser package includes an opt-in compatibility bridge for older event names. If you mount Formie yourself, you can enable it while you migrate listeners:

```ts
import { createFormieClient } from '@verbb/formie-browser';

const formie = createFormieClient();
const form = document.querySelector('[data-formie-form]');

if (form instanceof HTMLElement) {
    await formie.mount(form, {
        mode: 'html',
        compatibility: true,
    });
}
```

You can also enable only part of the bridge:

```ts
await formie.mount(form, {
    mode: 'html',
    compatibility: {
        legacyDomEvents: true,
        legacyValidatorEvents: false,
    },
});
```

For rendered HTML, the bridge can be enabled with a data attribute:

```html
<form data-formie data-formie-form data-formie-compatibility="true">
    <!-- ... -->
</form>
```

Prefer updating to the new event names instead of leaving the bridge enabled permanently.

Learn more in [Frontend Assets](/frontend/frontend-assets).

## Custom Front-End Validation

Custom validator registration should now use the validator exposed by `formie:validator:ready`.

::: code-group
```js [Formie 3]
const form = document.querySelector('#formie-form');

form.addEventListener('onFormieThemeReady', (event) => {
  event.detail.addValidator('businessEmail', ({ input }) => {
    return !input.value.endsWith('@example.com');
  }, () => {
    return 'Please use your business email address.';
  });
});
```

```js [Formie 4]
const form = document.querySelector('#formie-form');

form?.addEventListener('formie:validator:ready', (event) => {
  const { validator } = event.detail;

  validator.addValidator(
    'businessEmail',
    ({ input }) => !input.value.endsWith('@example.com'),
    () => 'Please use your business email address.'
  );
});
```
:::

Validator events have also been renamed:

Formie 3 | Formie 4
--- | ---
`formieValidatorInitialized` | `formie:validator:ready`
`formieValidatorDestroyed` | `formie:validator:destroy`
`formieValidatorShowError` | `formie:validator:show-error`
`formieValidatorClearError` | `formie:validator:clear-error`

Learn more in [Frontend Assets](/frontend/frontend-assets).

## GraphQL

> [!IMPORTANT]
> Compatibility mode will handle these changes automatically.

If you query form page settings, update the renamed client event fields:

::: code-group
```graphql [Formie 3]
settings {
  enableJsEvents
  jsGtmEventOptions
}
```

```graphql [Formie 4]
settings {
  enableClientEvents
  clientEventFields
}
```
:::

If you were loading rendered HTML over GraphQL, switch from the old form query to the dedicated HTML query.

::: code-group
```graphql [Formie 3]
query FormHtml($handle: String!) {
  formieForm(handle: $handle) {
    templateHtml
  }
}
```

```graphql [Formie 4]
query FormHtml($handle: String!, $input: ServerRenderPayloadInput) {
  formieHtmlForm(handle: $handle, input: $input) {
    html
  }
}
```
:::

This is the same HTML-mode flow used by the starter examples. GraphQL loads the HTML payload, and the rendered form still submits through its normal form action.

If you submit forms through GraphQL, review the current GraphQL docs. Formie now has separate docs for querying forms, querying submissions, rendering forms, and creating submissions:

- [Query Forms](/graphql/query-forms)
- [Query Submissions](/graphql/query-submissions)
- [Rendering Forms](/graphql/rendering-forms)
- [Create Submissions](/graphql/create-submissions)

## Submission Workflow

Submission processing now uses Preflight, Validate, Screen, Persist, Dispatch and Finalize. Invalid submissions stop after Validate, before content spam or CAPTCHA. Direct element saves remain persistence-only; code needing submission lifecycle policies must use an explicit operation through `SubmissionProcessor`.

### Stable Formie 3 APIs

| Formie 3 | Formie 4 |
| --- | --- |
| `getSubmissionById()`, retention/pruning and element persistence | Retained. Direct persistence does not automatically dispatch status-change notifications or integrations. |
| `onBeforeSubmission()` / `beforeSubmission` / `beforeIncompleteSubmission` | Move execution-controlling checks to a registered Submit task in Preflight or Validate. There is no cancellation-compatible wrapper around the new command boundary. |
| `onAfterSubmission()` / `afterSubmission` / `afterIncompleteSubmission` | Observe the typed outcome, `EVENT_AFTER_PAGE_ADVANCE` or `Submission::EVENT_AFTER_COMPLETE`, according to the intended lifecycle boundary. |
| `spamChecks()` / `beforeSpamCheck` / `afterSpamCheck` | Register a Submit task around `screen.evaluateSpam`, or observe that public task. Cheap request guards belong outside Screen. |
| `beforeSendNotification`, `beforeTriggerIntegration` on `Submissions` | Compatibility event bridges remain; migrate listeners to `Notifications` and `Integrations`. |
| `afterPruneSubmission` | Retained on `Submissions`. |
| `sendNotifications()`, `sendNotification()`, `sendNotificationEmail()`, `triggerIntegrations()`, `sendIntegrationPayload()` | Deprecated forwarding methods remain; use their owning notification/integration services. |
| `processPayments()` | Deprecated direct-call compatibility method remains. Normal submissions should use the workflow's payment operation. |

Use a registered task for checks that can stop processing, and an event listener to react to a page advance or completed submission. See [Submission Workflow](/developers/submission-workflow) for registration examples.

Control panel creation uses an administrative Submit policy: whole-record validation, status preservation, and no visitor progression, screening, payment or automatic completion dispatch. Existing CP edits use Revise. Public submit/edit actions remain visitor actions even for authenticated administrators; the submission editor posts to the explicit administrative action.


## Render Options

Some render options have been renamed.

### Include CSS and JavaScript

Use `includeCss` and `includeJs` in place of `renderCss` and `renderJs`.

::: code-group
```twig [Formie 3]
{{ craft.formie.renderForm('contact', {
    renderCss: false,
    renderJs: true,
}) }}
```

```twig [Formie 4]
{{ craft.formie.renderForm('contact', {
    includeCss: false,
    includeJs: true,
}) }}
```
:::

### Output CSS and JavaScript

Form template asset flags are now consolidated.

::: code-group
```twig [Formie 3]
{{ craft.formie.renderForm('contact', {
    outputCssLayout: true,
    outputCssTheme: true,
    outputJsBase: true,
    outputJsTheme: true,
}) }}
```

```twig [Formie 4]
{{ craft.formie.renderForm('contact', {
    outputCss: true,
    outputJs: true,
}) }}
```
:::

Formie still normalizes the old render option keys, but update templates to use the new keys.

### Output Location

Use `outputCssLocation` and `outputJsLocation` when you need to control where assets are output.

```twig
{{ craft.formie.renderForm('contact', {
    outputCssLocation: 'page-header',
    outputJsLocation: 'page-footer',
}) }}
```

Available values are:

Value | Meaning
--- | ---
`page-header` | Register output for the document head.
`page-footer` | Register output near the end of the page.
`inside-form` | Output assets near the rendered form.
`manual` | Do not output that asset automatically.

Learn more in [Render Options](/templates/render-options).

## Form Templates

Form Templates now use single CSS and JavaScript output flags.

::: code-group
```php [Formie 3]
[
    'outputCssLayout' => true,
    'outputCssTheme' => true,
    'outputJsBase' => true,
    'outputJsTheme' => true,
]
```

```php [Formie 4]
[
    'outputCss' => true,
    'outputJs' => true,
    'outputCssLocation' => 'page-header',
    'outputJsLocation' => 'page-footer',
]
```
:::

Learn more in [Template Overrides](/theming/template-overrides) and [Theme Config](/theming/theme-config).

## Text Limits

Single-line and multi-line text fields now use `maxType` and `max` instead of `limitType` and `limitAmount`.

::: code-group
```php [Formie 3]
[
    'limitType' => 'characters',
    'limitAmount' => 120,
]
```

```php [Formie 4]
[
    'maxType' => 'characters',
    'max' => 120,
]
```
:::

Learn more in [Single-Line Text](/fields/single-line-text) and [Multi-Line Text](/fields/multi-line-text).

## Translation Strings

Formie 4 standardises several **plugin-owned** message keys that sites commonly override in `translations/*/formie.php`, especially for front-end validation and text-limit counters.

These are strings Formie ships in English and translates with `Craft::t('formie', …)`. They are separate from form field labels and messages you edit in the form builder. See [Form Content Translations](#form-content-translations) if you previously mapped builder copy through translation files.

If your project overrides plugin UI strings, update the **source keys** in your translation files. Formie looks up messages by the English source string passed to `Craft::t('formie', …)`, not by a separate message ID.

### Front-End Validation Placeholders

Formie-owned validation messages now use `{label}` for the field label placeholder instead of `{attribute}`.

Update any overrides that still target the old `{attribute}` keys. The English source strings themselves also changed:

Formie 3 | Formie 4
--- | ---
`{attribute} cannot be blank.` | `{label} cannot be blank.`
`{attribute} is not a valid email address.` | `{label} is not a valid email address.`
`{attribute} is not a valid URL.` | `{label} is not a valid URL.`
`{attribute} is not a valid number.` | `{label} is not a valid number.`
`{attribute} is not a valid format.` | `{label} is not a valid format.`
`{attribute} must match {value}.` | `{label} must match {value}.`
`{attribute} must be between {min} and {max}.` | `{label} must be between {min} and {max}.`
`{attribute} must be no less than {min}.` | `{label} must be no less than {min}.`
`{attribute} must be no greater than {max}.` | `{label} must be no greater than {max}.`
`{attribute} has an invalid value.` | `{label} has an invalid value.`
`{attribute} must select between {min} and {max}.` | `{label} must select between {min} and {max}.`
`{attribute} must select no less than {min}.` | `{label} must select no less than {min}.`
`{attribute} must select no greater than {max}.` | `{label} must select no greater than {max}.`

These strings are included in Formie’s front-end translation seed via `Rendering::getFrontendJsTranslations()`. If you append custom strings through the [`modifyFrontendJsTranslations`](/developers/events/form-events#the-modifyfrontendjstranslations-event) event, use the new `{label}` placeholders in both the source key and your translated value.

Custom per-field validation overrides in the form builder now live under **Validation** as `validationMessages.{key}` (for example `validationMessages.required` and `validationMessages.unique`). Legacy field `errorMessage` values are migrated to `validationMessages.required` automatically.

If a saved override still contains `{name}` or `{attribute}`, Formie still maps those to `{label}` automatically, but new overrides should use `{label}` directly.

### Text Limit Counter Copy

Text-limit counters no longer use `{startTag}` / `{endTag}` HTML placeholders in translation strings. The count is rendered in markup; the translation covers the suffix only.

Remove overrides for these **removed** source keys:

- `{startTag}{num}{endTag} character left`
- `{startTag}{num}{endTag} characters left`
- `{startTag}{num}{endTag} word left`
- `{startTag}{num}{endTag} words left`
- `{num} characters left` (legacy)
- `{num} words left` (legacy)

Replace them with Craft plural syntax. Counters use three states depending on field content:

State | When | Example suffix
--- | --- | ---
Allowed | Field is empty | `100 characters allowed`
Left | Under the limit | `42 characters left`
Over | Over the limit | `5 characters over limit`

Formie 3 | Formie 4
--- | ---
`{startTag}{num}{endTag} characters left` | `{count, plural, one{character allowed} other{characters allowed}}` (empty), `{count, plural, one{character left} other{characters left}}` (typing), `{count, plural, one{character over limit} other{characters over limit}}` (over limit)
`{startTag}{num}{endTag} words left` | `{count, plural, one{word allowed} other{words allowed}}`, `{count, plural, one{word left} other{words left}}`, `{count, plural, one{word over limit} other{words over limit}}`

Example site override:

```php
// translations/de/formie.php
return [
    '{count, plural, one{character allowed} other{characters allowed}}' => '{count, plural, one{Zeichen erlaubt} other{Zeichen erlaubt}}',
    '{count, plural, one{character left} other{characters left}}' => '{count, plural, one{Zeichen übrig} other{Zeichen übrig}}',
    '{count, plural, one{character over limit} other{characters over limit}}' => '{count, plural, one{Zeichen über dem Limit} other{Zeichen über dem Limit}}',
    '{count, plural, one{word allowed} other{words allowed}}' => '{count, plural, one{Wort erlaubt} other{Wörter erlaubt}}',
    '{count, plural, one{word left} other{words left}}' => '{count, plural, one{Wort übrig} other{Wörter übrig}}',
    '{count, plural, one{word over limit} other{words over limit}}' => '{count, plural, one{Wort über dem Limit} other{Wörter über dem Limit}}',
];
```

On the front end, pass `{ count }` when translating these strings. Formie’s browser `t()` helper resolves Craft-style plural branches when the form renders.

### Server-Side Unique-Value Messages

The default unique-value validation message source key is now:

```php
'"{label}" must be unique.'
```

If you previously overrode a field-specific unique message via translation files alone, consider using the **Unique Error Message** field on the Validation tab instead (`validationMessages.unique`), which supports `{label}` and other allowed placeholders without requiring a global translation override.

## Form Content Translations

Formie no longer passes user-authored form copy through the `formie` translation category at render time. Labels, placeholders, messages, and option labels come from the database (with [site overrides](/forms/multi-site-and-translation#content-translation) on multi-site projects).

This replaces an older workaround that mutated the plugin `sourceLanguage` on front-end requests so German (or other non-English) canonical labels would not be reverse-translated to English via `formie.php`.

### Audit Your Translation Files

Review `translations/*/formie.php` and remove keys that match **form builder content**, for example:

```php
// Remove — migrate to CP site overrides or edit the form directly
'Your name' => 'Votre nom',
'Contact us' => 'Contactez-nous',
'Please enter your email' => 'Veuillez saisir votre e-mail',
```

Keep keys that match **Formie-owned English source strings**, for example:

```php
// Keep
'{label} cannot be blank.' => '…',
'(optional)' => '…',
'Drop files here or browse to upload.' => '…',
```

The same applies to user field labels stored in `translations/*/site.php`. Prefer editing the form or using CP site overrides instead.

### Migration Paths

| Old pattern | New approach |
| --- | --- |
| `'Your name' => 'Votre nom'` in `fr/formie.php` | French **site override** in the form builder |
| German labels written in the builder, no overrides | No change — copy renders as stored |
| Plugin validation overrides in `de/formie.php` | Keep in `formie.php` |
| Per-field validation text | **Validation** tab on the field (`validationMessages.*`) |
| Two English sites, different labels | CP site overrides (not locale files) |

### Single-Site Projects

Edit the form in the control panel. You do not need translation file entries for field labels.

Learn more in [Translations](/forms/translations).

## Sub-Field Label Position

The setting key now uses `subField` casing.

::: code-group
```php [Formie 3]
[
    'subfieldLabelPosition' => \verbb\formie\positions\AboveInput::class,
]
```

```php [Formie 4]
[
    'subFieldLabelPosition' => \verbb\formie\positions\AboveInput::class,
]
```
:::

Learn more in [Address](/fields/address), [Name](/fields/name), and [Date/Time](/fields/date-time).

## Instruction Positions

The old fieldset-specific instruction positions are normalised to regular positions.

::: code-group
```php [Formie 3]
[
    'instructionsPosition' => 'verbb\\formie\\positions\\FieldsetStart',
]
```

```php [Formie 4]
[
    'instructionsPosition' => \verbb\formie\positions\AboveInput::class,
]
```
:::

`FieldsetEnd` maps to `BelowInput`.

Learn more in [Form Builder](/forms/form-builder).

## Submission and Form Statuses

> [!IMPORTANT]
> With `compatibilityMode` enabled (the default), legacy submission status class names and helpers keep working and log Craft deprecation warnings when your project uses them. Update custom code when you can; disable compatibility mode once deprecation logs are clear.

In Formie 3, statuses always meant submission workflow labels. Formie 4 introduces form statuses as a new concept, then renames the old submission status APIs so the two systems are not confused in code.

**What you had in Formie 3** — submission statuses only:

- **Settings → Statuses** in the control panel
- `Statuses`, `Status`, `StatusEvent`, and `getStatuses()` in PHP
- `formie_statuses` database table and `formie.statuses` project config

**What Formie 4 adds** — form statuses (new):

- **Settings → Form Statuses**, `FormStatuses`, `FormStatus`, `formie_form_statuses`, and `formie.formStatuses`
- A **Form Status** picker on forms, the index **Status** menu, and bulk actions

**What Formie 4 renames** — your existing submission statuses (same behaviour, explicit naming):

- **Settings → Submission Statuses** (legacy **Settings → Statuses** URL still works)
- `SubmissionStatuses`, `SubmissionStatus`, `SubmissionStatusEvent`, and `getSubmissionStatuses()`
- `formie_submission_statuses` database table (`formie.statuses` project config is unchanged)

#### Submission Status Renames (Formie 3 → Formie 4)

Formie 3 | Formie 4
--- | ---
`verbb\formie\services\Statuses` | `verbb\formie\services\SubmissionStatuses`
`verbb\formie\models\Status` | `verbb\formie\models\SubmissionStatus`
`verbb\formie\records\Status` | `verbb\formie\records\SubmissionStatus`
`verbb\formie\events\StatusEvent` | `verbb\formie\events\SubmissionStatusEvent`
`verbb\formie\controllers\StatusesController` | `verbb\formie\controllers\SubmissionStatusesController`
`Formie::$plugin->getStatuses()` | `Formie::$plugin->getSubmissionStatuses()`
`craft.formie.getStatuses()` | `craft.formie.getSubmissionStatuses()`
`SubmissionStatuses::CONFIG_STATUSES_KEY` | `SubmissionStatuses::CONFIG_SUBMISSION_STATUSES_KEY` (`formie.statuses`)
`Table::FORMIE_STATUSES` | `Table::FORMIE_SUBMISSION_STATUSES`
Database table `formie_statuses` | `formie_submission_statuses`

Legacy aliases for the Formie 3 class names are registered by compatibility mode. Event handler names on `SubmissionStatuses` are unchanged (`beforeSaveStatus`, `afterSaveStatus`, and so on).

If your project listens for submission status events, register handlers on `SubmissionStatuses` and type-hint `SubmissionStatusEvent`:

::: code-group
```php [Formie 3]
use verbb\formie\events\StatusEvent;
use verbb\formie\services\Statuses;
use yii\base\Event;

Event::on(Statuses::class, Statuses::EVENT_BEFORE_SAVE_STATUS, function(StatusEvent $event) {
    $status = $event->status;
    // ...
});
```

```php [Formie 4]
use verbb\formie\events\SubmissionStatusEvent;
use verbb\formie\services\SubmissionStatuses;
use yii\base\Event;

Event::on(SubmissionStatuses::class, SubmissionStatuses::EVENT_BEFORE_SAVE_STATUS, function(SubmissionStatusEvent $event) {
    $status = $event->status;
    // ...
});
```
:::

Compatibility mode also forwards handlers still registered on the legacy `Statuses` class name to the canonical service and logs a deprecation warning.

## Reports and Submission Export

Formie 3 exposed **Export** on the submissions element index (CSV export of the current source and filters). Formie 4 removes that control panel action for all forms — the button disappears when no element exporters are registered, which is expected after upgrade.

Submission export now lives in **[Reports](/reports/reports)**:

- **Formie → Reports** — saved filters, columns, charts, on-demand export (CSV, Excel, JSON, XML, text), and [scheduled email delivery](/reports/scheduled-reports)
- For a one-off file without saving a report definition, run a report scoped to the form(s) you need and choose **Export** on the report run screen

Train editors and support staff on this workflow before cutover if they rely on the old submissions index export.

**Permissions** — report access is split from submission management. Under **Settings → Users → {group} → Formie**, assign:

| Permission | Purpose |
| --- | --- |
| **Access reports** | Open **Formie → Reports** and run saved reports |
| **Export submissions** | Export from reports (without full manage access) |
| **Manage reports** | Create, edit, delete reports; includes export |
| **Manage scheduled reports** | Configure delivery under **Settings → Scheduled Reports** and on a report’s **Scheduled** tab |

Administrators bypass these checks.

**Custom code** — PHP/Twig export helpers such as `getFieldValueForExport()` and `getValuesForExport()` are unchanged. Only the built-in control panel export action moved. Third-party modules can still register custom submission element exporters if needed.

See [Reports](/reports/reports), [Saved reports and scheduled delivery](/guides/submissions-workflows/saved-reports-and-scheduled-delivery), and [Submissions overview](/submissions/submissions#export-submission-data).

## Default Theme Classes

Formie 4’s default theme uses the `formie` CSS class prefix. If your site styles Formie 3’s `.fui-` classes, update those selectors or adopt the current theme variables before deploying. A PHP compatibility bridge cannot rewrite your stylesheet.

Formie 3 | Formie 4
--- | ---
`.fui-form` | `.formie-form`

Inspect each styled form, including validation errors, page navigation and nested fields, after rebuilding your site assets. See [Theme Config](/theming/theme-config) for the current theming approach.

## Deprecated Changes

Working compatibility APIs remain available as described below. Update them after addressing required changes; a deprecation warning does not by itself mean a call has stopped working.

## Compatibility Mode

Formie includes a compatibility layer to make Formie 3 projects easier to update. It is enabled by default.

```php
// config/formie.php
return [
    'compatibilityMode' => true,
];
```

Compatibility mode currently covers four areas:

- A small set of legacy class aliases that were already deprecated in earlier releases.
- A small PHP event bridge for notification and integration events that moved to more specific services.
- Deprecated `Submissions` integration and notification dispatch helpers that delegate to `IntegrationTriggers`, `Integrations`, and `Notifications`.
- Custom field compatibility bridges for older schema methods, field config normalisation, and legacy Theme Config field tags.

Leave compatibility mode enabled while you update the site. Once the project has no Formie deprecation warnings and any custom code has been updated, you can disable it:

```php
// config/formie.php
return [
    'compatibilityMode' => false,
];
```

## Form Rendering and Assets

Formie 4 has a cleaner rendering API for form assets.

### Render Form Assets

::: code-group
```twig [Formie 3]
{{ craft.formie.renderFormAssets(form) }}
```

```twig [Formie 4]
{{ craft.formie.formAssets(form) }}
```
:::

### Render CSS or JavaScript

::: code-group
```twig [Formie 3]
{{ craft.formie.renderFormCss(form) }}
{{ craft.formie.renderFormJs(form) }}
```

```twig [Formie 4]
{{ craft.formie.formAssets(form, {
    includeJs: false,
}) }}

{{ craft.formie.formAssets(form, {
    includeCss: false,
}) }}
```
:::

### Render Shared Assets

If you are not rendering assets for a specific form, use `frontendAssets()`.

::: code-group
```twig [Formie 3]
{{ craft.formie.renderCss() }}
{{ craft.formie.renderJs() }}
```

```twig [Formie 4]
{{ craft.formie.frontendAssets() }}

{{ craft.formie.frontendAssets({
    includeJs: false,
}) }}

{{ craft.formie.frontendAssets({
    includeCss: false,
}) }}
```
:::

The same replacement applies if you are calling `Formie::$plugin->getRendering()` in PHP:

::: code-group
```php [Formie 3]
Formie::$plugin->getRendering()->renderFormAssets($form);
```

```php [Formie 4]
Formie::$plugin->getRendering()->formAssets($form);
```
:::

See [Frontend Assets](/frontend/frontend-assets) and [Render Options](/templates/render-options) for the full rendering options.

## Form Render IDs

Use `getRenderId()` and `setRenderId()` in place of `getFormId()` and `setFormId()`.

::: code-group
```twig [Formie 3]
{% set id = form.getFormId() %}
```

```twig [Formie 4]
{% set id = form.getRenderId() %}
```
:::

::: code-group
```php [Formie 3]
$form->setFormId('contact-form');
```

```php [Formie 4]
$form->setRenderId('contact-form');
```
:::

Learn more in [Rendering Forms](/templates/rendering-forms).

## Submission Values

Submission value helpers have been renamed to make the format explicit.

### Single Field Values

Use the `getFieldValue*()` methods in place of the older `getValue*()` methods.

::: code-group
```twig [Formie 3]
{{ submission.getValueAsString('fullName') }}
{% set address = submission.getValueAsJson('billingAddress') %}
{% set exportValue = submission.getValueForExport('payment') %}
{% set summaryValue = submission.getValueForSummary('billingAddress') %}
```

```twig [Formie 4]
{{ submission.getFieldValueAsString('fullName') }}
{% set address = submission.getFieldValueAsData('billingAddress') %}
{% set exportValue = submission.getFieldValueForExport('payment') %}
{% set summaryValue = submission.getFieldValueForSummary('billingAddress') %}
```
:::

### Submission Values

Use the Data helpers for JSON-safe values.

::: code-group
```twig [Formie 3]
{% set values = submission.getValuesAsJson() %}
```

```twig [Formie 4]
{% set values = submission.getValuesAsData() %}
```
:::

The same change applies in PHP:

::: code-group
```php [Formie 3]
$values = $submission->getValuesAsJson();
```

```php [Formie 4]
$values = $submission->getValuesAsData();
```
:::

### Value Types

Use the helper that matches the job you are doing.

```twig
{# Normal value. Good when you want the field's natural shape. #}
{% set value = submission.getFieldValue('billingAddress') %}

{# String value. Good for text output, logs, and simple display. #}
{% set value = submission.getFieldValueAsString('billingAddress') %}

{# Data value. Good for JSON-safe scalar or structured values. #}
{% set value = submission.getFieldValueAsData('billingAddress') %}

{# Export value. Good for CSVs, spreadsheets, and reports. #}
{% set value = submission.getFieldValueForExport('billingAddress') %}

{# Summary value. Good for review screens and summary output. #}
{% set value = submission.getFieldValueForSummary('billingAddress') %}
```

See [Submission Content](/developers/submission-content) for a fuller explanation of the different value formats.

## Field Schema Methods

> [!IMPORTANT]
> Compatibility mode will handle these changes automatically.

Form builder settings use `defineFormBuilder*Schema()` methods.

::: code-group
```php [Formie 3]
public function defineGeneralSchema(): array
{
    return [
        SchemaHelper::textField([
            'label' => Craft::t('formie', 'Placeholder'),
            'help' => Craft::t('formie', 'The text that will be shown if the field does not have a value.'),
            'name' => 'placeholder',
        ]),
    ];
}
```

```php [Formie 4]
public function defineFormBuilderGeneralSchema(): array
{
    return [
        SchemaHelper::textField([
            'label' => Craft::t('formie', 'Placeholder'),
            'instructions' => Craft::t('formie', 'The text that will be shown if the field does not have a value.'),
            'name' => 'placeholder',
        ]),
    ];
}
```
:::

Schema method changes:

Formie 3 | Formie 4
--- | ---
`defineGeneralSchema()` | `defineFormBuilderGeneralSchema()`
`defineSettingsSchema()` | `defineFormBuilderSettingsSchema()`
`defineAppearanceSchema()` | `defineFormBuilderAppearanceSchema()`
`defineAdvancedSchema()` | `defineFormBuilderAdvancedSchema()`
`defineConditionsSchema()` | `defineFormBuilderConditionsSchema()`

The schema node format also changed. If you were already using `SchemaHelper`, keep using it. If you built schema arrays manually, update FormKit-style nodes such as `$formkit` to the current `$field` format.

::: code-group
```php [Formie 3]
[
    '$formkit' => 'text',
    'label' => Craft::t('formie', 'Placeholder'),
    'help' => Craft::t('formie', 'Shown when the field has no value.'),
    'name' => 'placeholder',
]
```

```php [Formie 4]
[
    '$field' => 'text',
    'label' => Craft::t('formie', 'Placeholder'),
    'instructions' => Craft::t('formie', 'Shown when the field has no value.'),
    'name' => 'placeholder',
]
```
:::

Even when using `SchemaHelper`, check these schema changes:

- `help` is now `instructions`
- old `if` expressions such as `$get(required).value` should be updated to the current expression format

See [Schema](/developers/schema) for the current schema syntax.

## Field Values

The public methods trigger Formie events after the value has been defined.

Value method changes:

Formie 3 | Formie 4
--- | ---
`defineValueAsJson()` | `defineValueAsData()`
`getValueAsJson()` | `getValueAsData()`
`defineValueForVariable()` | `defineValueForReference()`
`getValueForVariable()` | `getValueForReference()`
`defineValueForVariableRaw()` | `defineValueForReference()`
`getValueForVariableRaw()` | `getValueForReference()`
`defineValueForEmail()` | `defineValueForReferenceBlock()`
`getValueForEmail()` | `getValueForReferenceBlock()`

Formie 4 splits field values into two concepts:

- **Reference**: the singular, string-like value used for variable chips, subject lines, and other single-value contexts.
- **Reference block**: the richer block value used when Formie renders looped field content in notification bodies and similar “all fields” output.

Deprecated aliases remain available while you upgrade, but new field code should target the `reference` / `reference block` names directly.

## Additional Deprecated Names

These aliases belong to upgrade work. Use the canonical names in ordinary templates and new extensions.

Formie 3 | Formie 4
--- | ---
GraphQL `emailValue` | `emailFieldSummaryValue`
GraphQL `includeInEmail` | `includeInEmailFieldSummaries`
`includeInEmailField()` | `includeInEmailFieldSummariesField()`
`emailNotificationValue()` | `emailFieldSummaryValue()`

For field/form `modifyHtmlTag` event replacements, see [Field and Form Slot Tag Events](#field-and-form-slot-tag-events). For custom previews, replace template-string previews with the schema described in [Schema](/developers/schema).

## Field Paths

Several field path helpers have clearer names.

Formie 3 | Formie 4
--- | ---
`getFieldKey()` | `valueKey()`
`getErrorKey()` | `errorKey()`
`getFullHandle()` | `handlePath()`
`getFullNamespace()` | `namespacePath()`
`getReservedHandles()` | `Formie::$plugin->getFields()->getReservedHandles()`

::: code-group
```php [Formie 3]
$key = $field->getFieldKey();
$errors = $submission->getErrors($field->getErrorKey());
```

```php [Formie 4]
$key = $field->valueKey();
$errors = $submission->getErrors($field->errorKey());
```
:::

## Theme Config

> [!IMPORTANT]
> Compatibility mode will handle these changes automatically.

If your custom field supports Theme Config, update `defineHtmlTag()` usage to `defineSlotTag()`, and return a `SlotTag`.

::: code-group
```php [Formie 3]
protected function defineHtmlTag(string $key, array $context = []): ?HtmlTag
{
    if ($key === 'fieldInput') {
        return new HtmlTag('input', [
            'class' => ['fui-input'],
        ]);
    }

    return parent::defineHtmlTag($key, $context);
}
```

```php [Formie 4]
use verbb\formie\models\SlotTag;
use verbb\formie\theme\context\RenderContext;

protected function defineSlotTag(string $key, RenderContext $context): ?SlotTag
{
    if ($key === 'fieldInput') {
        return SlotTag::make('input')
            ->core([
                'type' => 'text',
                'name' => $this->getHtmlName(),
                'data-formie-input' => true,
            ])
            ->theme([
                'class' => ['formie-input'],
            ]);
    }

    return parent::defineSlotTag($key, $context);
}
```
:::

The PHP bridge does not update your custom CSS selectors. Review [Default Theme Classes](#default-theme-classes) before deployment. If you use the Formie 3 [Tailwind](https://github.com/verbb/formie-theme-configs/blob/formie-3/tailwind/index.html) or [Bootstrap](https://github.com/verbb/formie-theme-configs/blob/formie-3/bootstrap/index.html) theme examples, update their class selectors and review the resulting markup as part of this upgrade.

The Formie 3 theme grammar remains supported, including flat attribute shorthand, false/null removal, `resetClass`, top-level `resetClasses`, `prepend` and `append`. Theme and instance attributes now merge before required core attributes, so declarative config cannot remove functional or accessibility markup. Trusted `EVENT_MODIFY_SLOT_TAG` listeners still run last when an expert override is required.

Purge cached form HTML during deployment and regenerate saved Summary and Signature links by rendering the form or requesting a fresh image/download URL. Custom Summary refresh requests must use the issued token. Summary tokens expire after seven days, so refresh cached forms within that period. Signature image links retain their existing lifetime. If you run multiple web servers, they must share the database and Formie security key.

See [Custom Field](/developers/custom-field) for the current custom field guide.

## PHP Events

Most PHP events keep the same names and owners. A small number of events moved to more specific services.

### Notifications

> [!IMPORTANT]
> Compatibility mode will handle these changes automatically.

`beforeSendNotification` now belongs on the `Notifications` service.

::: code-group
```php [Formie 3]
use verbb\formie\events\SendNotificationEvent;
use verbb\formie\services\Submissions;
use yii\base\Event;

Event::on(Submissions::class, Submissions::EVENT_BEFORE_SEND_NOTIFICATION, function(SendNotificationEvent $event) {
    // ...
});
```

```php [Formie 4]
use verbb\formie\events\SendNotificationEvent;
use verbb\formie\services\Notifications;
use yii\base\Event;

Event::on(Notifications::class, Notifications::EVENT_BEFORE_SEND_NOTIFICATION, function(SendNotificationEvent $event) {
    // ...
});
```
:::

### Integrations

> [!IMPORTANT]
> Compatibility mode will handle these changes automatically.

`beforeTriggerIntegration` now belongs on the `Integrations` service.

::: code-group
```php [Formie 3]
use verbb\formie\events\TriggerIntegrationEvent;
use verbb\formie\services\Submissions;
use yii\base\Event;

Event::on(Submissions::class, Submissions::EVENT_BEFORE_TRIGGER_INTEGRATION, function(TriggerIntegrationEvent $event) {
    // ...
});
```

```php [Formie 4]
use verbb\formie\events\TriggerIntegrationEvent;
use verbb\formie\services\Integrations;
use yii\base\Event;

Event::on(Integrations::class, Integrations::EVENT_BEFORE_TRIGGER_INTEGRATION, function(TriggerIntegrationEvent $event) {
    // ...
});
```
:::

### Custom Integration Dispatch

> [!IMPORTANT]
> With `compatibilityMode` enabled (the default), Formie 4 keeps the Formie 3 `Submissions` dispatch helpers working and logs Craft deprecation warnings when your project calls them. Update custom code when you can; disable compatibility mode once deprecation logs are clear.

Formie 4 routes integration and notification dispatch through dedicated services. If your Formie 3 project called dispatch helpers on `Submissions`, use the replacements below.

Formie 3 | Formie 4
--- | ---
`getSubmissions()->triggerIntegrations($submission)` | `getIntegrationTriggers()->dispatch(...)` or `dispatchFromWorkflow(...)`
`getSubmissions()->sendIntegrationPayload($integration, $submission)` | `getIntegrations()->sendIntegrationPayload(...)` for workflow dispatch, or `getIntegrationTriggers()->dispatchManualIntegration(...)` for operator-initiated runs
`getSubmissions()->sendNotifications($submission)` | `getNotifications()->sendNotifications(...)`
`getSubmissions()->sendNotification($notification, $submission)` | `getNotifications()->sendNotification(...)`
`getSubmissions()->sendNotificationEmail($notification, $submission)` | `getNotifications()->sendNotificationEmail(...)`

Status-change email notifications (notifications with a `{submission:status}` condition) are handled by `NotificationTriggers` when a submission’s status changes. You do not need an `Submission::EVENT_AFTER_SAVE` listener for that behaviour.

Avoid triggering integrations from `Submission::EVENT_AFTER_SAVE`. That bypasses re-run policies, workflow idempotency, and the CP save vs workflow split. Prefer listening to `Integrations::EVENT_BEFORE_TRIGGER_INTEGRATION`, or call `IntegrationTriggers` explicitly when you need custom dispatch.

::: code-group
```php [Formie 3]
use verbb\formie\Formie;
use verbb\formie\elements\Submission;

$submission = Submission::find()->id(123)->one();

Formie::$plugin->getSubmissions()->triggerIntegrations($submission);

Formie::$plugin->getSubmissions()->sendIntegrationPayload($integration, $submission);
```

```php [Formie 4]
use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\IntegrationTriggerEvents;
use verbb\formie\models\IntegrationTriggerRequest;
use verbb\formie\services\SubmissionWorkflow;

$submission = Submission::find()->id(123)->one();

// Automatic dispatch (respects re-run policies and orchestration)
Formie::$plugin->getIntegrationTriggers()->dispatch(new IntegrationTriggerRequest([
    'submission' => $submission,
    'operation' => \verbb\formie\enums\SubmissionOperation::REVISE,
    'triggerEvent' => IntegrationTriggerEvents::CP_SAVE,
]));

// Operator-initiated single integration run
Formie::$plugin->getIntegrationTriggers()->dispatchManualIntegration($integration, $submission);

// Low-level payload send (events still fire on Integrations)
Formie::$plugin->getIntegrations()->sendIntegrationPayload($integration, $submission);
```
:::

Learn more in [Submission Workflow](/developers/submission-workflow) and [Integration Events](/developers/events/integration-events).

### Field and Form Slot Tag Events

If your project listens for field or form tag-mutation events, switch from the old `htmlTag` names to the canonical `slotTag` names.

The old constants and event classes still exist as deprecated aliases, but new code should use the slot-tag names.

Formie 3 | Formie 4
--- | ---
`Field::EVENT_MODIFY_HTML_TAG` | `Field::EVENT_MODIFY_SLOT_TAG`
`ModifyFieldHtmlTagEvent` | `ModifyFieldSlotTagEvent`
`Form::EVENT_MODIFY_HTML_TAG` | `Form::EVENT_MODIFY_SLOT_TAG`
`ModifyFormHtmlTagEvent` | `ModifyFormSlotTagEvent`

:::: code-group
```php [Formie 3]
use verbb\formie\base\Field;
use verbb\formie\elements\Form;
use verbb\formie\events\ModifyFieldHtmlTagEvent;
use verbb\formie\events\ModifyFormHtmlTagEvent;
use yii\base\Event;

Event::on(Field::class, Field::EVENT_MODIFY_HTML_TAG, function(ModifyFieldHtmlTagEvent $event) {
    // ...
});

Event::on(Form::class, Form::EVENT_MODIFY_HTML_TAG, function(ModifyFormHtmlTagEvent $event) {
    // ...
});
```

```php [Formie 4]
use verbb\formie\base\Field;
use verbb\formie\elements\Form;
use verbb\formie\events\ModifyFieldSlotTagEvent;
use verbb\formie\events\ModifyFormSlotTagEvent;
use yii\base\Event;

Event::on(Field::class, Field::EVENT_MODIFY_SLOT_TAG, function(ModifyFieldSlotTagEvent $event) {
    // ...
});

Event::on(Form::class, Form::EVENT_MODIFY_SLOT_TAG, function(ModifyFormSlotTagEvent $event) {
    // ...
});
```
::::

See [Field Events](/developers/events/field-events) and [Form Events](/developers/events/form-events) for the current event reference.

## Field Reference-Block Templates

Reference-block templates replace the old field-level email template hook.

:::: code-group
```php [Formie 3]
public static function getEmailTemplatePath(): string
{
    return 'my-module/my-field/email';
}
```

```php [Formie 4]
public static function getReferenceBlockTemplatePath(): string
{
    return 'my-module/my-field/email';
}
```
::::

`getEmailTemplatePath()` still exists as a deprecated compatibility alias, but new code should use `getReferenceBlockTemplatePath()`.

## Behaviour Changes

The following sections explain operational changes to review after applying the required code changes above.

## Changes at a Glance

Not every important change in Formie 4 is a breaking change. Some parts of the plugin now work quite differently, and they are worth understanding before you update custom code or plan new work.

### Integration Settings

Custom integrations no longer build their form settings UI with templates. They now define schema for the form builder.

This changes how integration settings are built and maintained. If you have custom integrations, it is worth thinking of their settings in the same way as field schema: structured, reusable, and easier for Formie to normalise and extend.

### Submission Workflow

Submission processing is no longer one large save step. Formie now runs submissions through a clearer staged workflow.

That makes custom submission handling more predictable. If you need to run your own logic during processing, it is a better fit to add a workflow task or hook into the workflow events than to try to replace the whole pipeline.

The **`screen`** stage now runs built-in submission guards (honeypot, minimum submit time, replay protection) before captcha integrations and spam keyword checks. See [Submission Screening](/forms/submission-screening).

### Spam Protection Settings

Spam handling, keyword rules, submission guards, and captcha provider credentials are consolidated on **Settings → Spam Protection**. Legacy **Settings → Spam** and **Settings → Captchas** routes redirect there. Values are stored in dedicated settings tables rather than `plugins.formie.settings` in project config.

If you relied on the old built-in **Honeypot**, **Javascript**, or **Duplicate** captchas, see [Removed legacy captchas](#removed-legacy-captchas).

### Front-End Browser Package

Formie’s front-end JavaScript and CSS are now part of the browser package and related front-end packages.

If your project needs custom validation, DOM event listeners, front-end theming, or a more app-driven setup with React, Vue, Barba, Sprig, or similar tooling, it is worth getting familiar with that package-based front-end model.

Learn more in [Custom Integration](/developers/custom-integration/overview), [Submission Workflow](/developers/submission-workflow), and [Frontend Assets](/frontend/frontend-assets).

### Field References

Formie now uses a more consistent field reference concept across places like notifications, calculations, conditions, and integration mapping.

Instead of relying on a field handle as the token value, Formie uses a stable field reference. That makes these links more reliable as forms evolve over time.

### Database Submission State

Formie 3 relied much more heavily on session-based submission state. That could be fragile, especially across devices, longer sessions, or more complex front-end flows.

Formie 4 stores temporary, incomplete, and saved draft submission state in the database instead. That gives automatic submission state, save-and-continue, retention, and resume tokens a much more reliable foundation.

### Static Caching

Formie can now handle static-cache token refresh more directly, instead of relying on extra custom snippets to keep cached forms usable.

If your site uses static caching, it is worth understanding the new refresh-on-load handling and the related config settings, especially if you previously added your own refresh logic.

### Form and Submission Statuses

In Formie 3, **Settings → Statuses** managed workflow labels for saved submissions — for example **New**, or custom statuses you created for your team. There was no separate status system for forms themselves.

Formie 4 adds **[Form statuses](/forms/form-statuses)** — lifecycle labels you can assign to forms in the control panel (for example **Active**, **Draft**, **Archived**). That is a new feature, not a rename of something that existed in Formie 3.

Adding form statuses meant the old generic “statuses” naming was no longer clear enough. Everything Formie 3 called **Statuses** is now **[Submission statuses](/submissions/submission-statuses)** in the control panel, PHP APIs, and database:

- **Settings → Submission Statuses** (the legacy **Settings → Statuses** route still opens this)
- `SubmissionStatuses`, `SubmissionStatus`, and related classes — replacing `Statuses`, `Status`, and so on
- Database table `formie_submission_statuses` — renamed from `formie_statuses`

Submission status project config stays at `formie.statuses`. Form statuses use a new `formie.formStatuses` key and `formie_form_statuses` table.

If your project has custom PHP, Twig, or module code that references the Formie 3 names, see [Submission and form statuses](#submission-and-form-statuses).

## Static Caches

Static-cache handling has changed. Formie can refresh request-specific tokens for statically cached forms when the `staticCacheRefreshOnLoad` plugin setting is enabled, and it assumes static-cache handling is needed when Blitz is installed and enabled.

```php
// config/formie.php
return [
    'staticCacheRefreshOnLoad' => true,
];
```

You no longer need to include a JavaScript snippet to refresh the tokens.

See [Cached Forms](/frontend/cached-forms) and [Configuration](/get-started/configuration).

## Save and Continue Later

Formie 4 stores form progress in the database so Save & Continue links can resume the same draft across browsers. Automatic page saving remembers the visitor’s browser; it does not create a shareable link. Links for resuming drafts and editing completed submissions are separate and cannot be used interchangeably.

For normal forms, this should not require template changes. If you customise save buttons, draft handling, or submission retention, review [Save & Continue Later](/forms/save-continue-later).

Related settings include:

Setting | Use
--- | ---
`submissionStateRetentionDays` | How long stored submission state is retained.
`saveResumeTokenTtlDays` | How long resume links remain valid.

Learn more in [Save & Continue Later](/forms/save-continue-later) and [Configuration](/get-started/configuration).

### Continuity and Upload Upgrade Actions

Existing submission IDs, field content and authorised Formie 3 template hooks such as `form.setSubmission(submission)` and `form.getSubmissionEditToken()` remain available. Continue to check ownership before calling `setSubmission()`. Render authorised edit forms again to obtain fresh edit tokens; existing signed tokens must be replaced.

Formie 3 asset-ID inputs remain supported only when ownership can be reconstructed from the current browser's staged upload or the authorised submission's existing field value. Arbitrary asset IDs must be replaced with a new upload. REST and GraphQL clients can submit `{ uploadUid, attachToken }`; the Upload Manager returns separate view, attach and delete credentials. Rebuild client assets together with the PHP upgrade.

Test Save & Continue on a second browser, then submit conflicting changes from both browsers: only the first version should succeed. Check completed-record editing separately and confirm that copied asset IDs and cross-field upload capabilities are rejected. [Submission Workflow](/developers/submission-workflow#progress-grants-and-uploads) documents the services and interrupted-promotion recovery.

## New and Renamed Settings

Review `config/formie.php` if your project keeps a full config file.

The most upgrade-relevant settings are:

Setting | Use
--- | ---
`compatibilityMode` | Enables Formie 3 compatibility shims. Defaults to `true`.
`staticCacheRefreshOnLoad` | Enables token refresh support for static-cache setups that are not auto-detected.
`submissionStateRetentionDays` | Controls retention for stored submission state.
`saveResumeTokenTtlDays` | Controls resume link lifetime.
`setOnlyCurrentPagePayload` | Limits front-end page payload behaviour to the current page.
`anonymousClientBootstrapRateLimit` | Rate limit for anonymous form bootstrap requests.
`anonymousClientRefreshRateLimit` | Rate limit for anonymous token refresh requests.
`anonymousClientRateWindowSeconds` | Rate-limit window used by the anonymous request limits.
`useCssLayers` | Outputs Formie's CSS using CSS layers when enabled.

Removed settings are ignored during settings normalisation:

Removed setting | What to do
--- | ---
`enableGatsbyCompatibility` | Remove it.

See [Configuration](/get-started/configuration) for the current config shape.

## Replacement Reference

Formie 3 | Formie 4
--- | ---
`form.getFormId()` | `form.getRenderId()`
`form.setFormId()` | `form.setRenderId()`
`submission.getValueAsString()` | `submission.getFieldValueAsString()`
`submission.getValueAsJson()` | `submission.getFieldValueAsData()`
`submission.getValuesAsJson()` | `submission.getValuesAsData()`
`field.getValueAsJson()` | `field.getValueAsData()`
`field.defineValueAsJson()` | `field.defineValueAsData()`
`field.getFieldKey()` | `field.valueKey()`
`field.getErrorKey()` | `field.errorKey()`
`field.getFullHandle()` | `field.handlePath()`
`field.getFullNamespace()` | `field.namespacePath()`
`craft.formie.renderFormAssets(form)` | `craft.formie.formAssets(form)`
`craft.formie.renderFormCss(form)` | `craft.formie.formAssets(form, { includeJs: false })`
`craft.formie.renderFormJs(form)` | `craft.formie.formAssets(form, { includeCss: false })`
`craft.formie.renderCss()` | `craft.formie.frontendAssets({ includeJs: false })`
`craft.formie.renderJs()` | `craft.formie.frontendAssets({ includeCss: false })`
`renderCss` render option | `includeCss`
`renderJs` render option | `includeJs`
`outputCssLayout` / `outputCssTheme` | `outputCss`
`outputJsBase` / `outputJsTheme` | `outputJs`
`defineGeneralSchema()` | `defineFormBuilderGeneralSchema()`
`defineSettingsSchema()` | `defineFormBuilderSettingsSchema()`
`defineAppearanceSchema()` | `defineFormBuilderAppearanceSchema()`
`defineAdvancedSchema()` | `defineFormBuilderAdvancedSchema()`
`defineConditionsSchema()` | `defineFormBuilderConditionsSchema()`
`getPreviewInputHtml()` | `defineFormBuilderPreviewSchema()`
`getFrontEndInputTemplatePath()` | `getInputTemplatePath()`
`getFormSettingsHtml()` for integration form settings | `defineFormSettingsSchema()`
`getFrontEndJsVariables()` for captchas/providers | `getBrowserModule()`
`enableJsEvents` | `enableClientEvents`
`jsGtmEventOptions` | `clientEventFields`
`onAfterFormieSubmit` | `formie:submit:result`
`formieValidatorInitialized` | `formie:validator:ready`
`verbb\formie\services\Statuses` | `verbb\formie\services\SubmissionStatuses`
`verbb\formie\models\Status` | `verbb\formie\models\SubmissionStatus`
`verbb\formie\events\StatusEvent` | `verbb\formie\events\SubmissionStatusEvent`
`Formie::$plugin->getStatuses()` | `Formie::$plugin->getSubmissionStatuses()`
`craft.formie.getStatuses()` | `craft.formie.getSubmissionStatuses()`
`Table::FORMIE_STATUSES` | `Table::FORMIE_SUBMISSION_STATUSES`
Database table `formie_statuses` | `formie_submission_statuses`
Submissions index **Export** button | **Formie → Reports** → run report → **Export**

## Payment And Subscription Boundary

Formie 4 keeps `verbb\formie\base\Payment`. Custom Formie 3 providers need the following explicit upgrade mappings; provider-specific event names survive where their semantics remain valid.

| Formie 3 | Formie 4 |
| --- | --- |
| Provider `processPayment(): bool` | Implement protected `executePayment(): PaymentDecision`; inherit `processPayment()` so durable intent, locking and uncertainty handling cannot be bypassed. Boolean success cannot express pending or unknown. |
| Floating point `Payment.amount` and conversions | Decimal strings and `PaymentMoney`; reject precision loss. `getAmount()` and `getPaymentAmount()` retain numeric-compatible return signatures for old extensions, but native adapters return exact strings or integer minor units. Update extension calculations to exact strings. |
| Payment before/after events and payload events | Existing event names/classes and their boolean legacy result projection remain. Payment authority and provider verification must still succeed. |
| `getPaymentByReference()` / `getSubscriptionByReference()` | Optional integration ID scopes the lookup. Native provider adapters always pass it. Identifiers alone never authorize public requests. |
| Subscription boolean flags | Read-only `hasStarted`, `isSuspended`, `isCanceled`, `isExpired` projections remain. Write the coherent `status` instead. Contradictory legacy flags migrate to unknown with original flags retained in history. |
| Subscription deletion | Archive through the service. Financial foreign keys use SET NULL and preserve owner snapshots. |
| Generic callback handlers | Move to the return, status, session or provider-challenge endpoint matching the operation. No generic public callback dispatcher remains. |
| Cancellation links | Reissue cancellation-only capabilities. Other submission/status/resume credentials cannot cancel subscriptions. GET only displays confirmation; POST requires CSRF. |
| Stripe `invoice.created` automatic payment request | Stripe automatic collection owns charging. Formie observes the invoice instead of issuing an unreceipted additional pay request. Paid/failed invoices create distinct history. |

Unresolved legacy payments migrate to unknown rather than inventing a confirmed provider result. Successful and failed historical payments retain their meaning and exact stored decimal text. The upgrade preserves payment and subscription identities, linkage and provider snapshots. Deploy when active checkout sessions have drained. Regenerate payment links using the scoped return, status and session endpoints.

Webhook signatures must be valid before Formie acknowledges an event. Stripe and GoCardless now retain encrypted authenticated evidence; Mollie URLs include a per-payment secret and use the provider API to authenticate the observed state. Reconfigure registered URLs where needed and retain the Formie security key for historical evidence decryption. See [Payment Integration](../developers/custom-integration/payment-integration) and [Console Commands](../developers/console-commands) for outcomes, replay, diagnostics and retention.


## Field Extensions and Portable Forms

Existing field subclasses continue to extend `verbb\formie\base\Field`. Register each concrete class through `Fields::EVENT_REGISTER_FIELDS`; classes that only implement `FieldInterface` are rejected when registration is resolved. Static metadata and inherited builder-schema defaults remain supported.

| Formie 3 | Formie 4 |
|---|---|
| `Field`, `id`, `uid` | Preserved; these identify the form-field instance |
| `fieldId` | `definitionId`; PHP/config alias remains available |
| `syncId` | Shared `definitionId` plus `isSynced`; legacy PHP/config input remains supported |
| `getAllFields()` | Preserved as fully hydrated runtime field objects |
| Boolean field traversal flags | `getFields()`, `getEnabledFields()`, `getFieldsRecursively()` |

Update custom templates that pass traversal flags. For example, replace `row.getFields(false)` with `row.getEnabledFields()` and `page.getRows(false)` with `page.getEnabledRows()`. Repeater row context uses `field.getFields(rowKey)`. Do not change native handle-based input names or replace instance IDs/UIDs in stored content with definition identity.

Existing exports remain importable. Review the import preview before applying changes: it shows which fields are kept, added or removed, and which related resources are reused or created. New imports and duplicates receive their own field identities; updates preserve matched fields. Imports roll back if they fail. Custom export tools should retain `schemaVersion`, `formieVersion` and field references.

Unknown, disabled or unregistered imported field types remain recoverable Missing Fields with their settings. Restore and register the owning extension before recovering them. Portable synced links use `syncedDefinitionUid`. A stencil whose shared definition cannot be resolved creates an independent definition; verify shared-field links after importing.

Changing a stencil or default after creating a form does not update that form. Custom field developers should review [field definitions and instances](/developers/custom-field#definition-and-instance-identity) when adapting settings or translations.

## Field Value Contracts

Name values now consistently use `NameFieldValue`, including single-name mode. Text, Email and Phone return strings, Agree normalises missing input to false, and Number keeps decimal text without float conversion. Use `getFieldValueAsString()` for human-readable output and `getFieldValueAsData()` for natural JSON-safe data. Update Phone templates that read `.number` or `.country` to use the string or the field's explicit `serializeValueForClientInput()` result.

GraphQL Number fields and numeric Table cells use `FormieDecimal`, which returns decimal strings and preserves literal digits. Change explicitly declared `$amount: Number` variables to `$amount: FormieDecimal` and send decimal variables as strings. JSON numeric variables may have lost precision in the caller before Formie receives them. Malformed text still reaches ordinary field validation. Control-panel number inputs also retain all stored digits when the configured decimal count changes.

| Existing API | Replacement |
|---|---|
| `Submission::getValueAsJson()` / `Field::getValueAsJson()` | `Submission::getFieldValueAsData()` / `Field::getValueAsData()`; deprecated adapters remain available |
| Formie 3 `defineValueAsJson()` override | Supported through a deprecated protected-method adapter; implement `defineValueAsData()` |
| Formie 3 `serializeValue()` override | Supported through the storage adapter; implement `defineValueForDb()` and call `serializeValueForDb()` |

The Formie 3 `EVENT_MODIFY_VALUE_AS_JSON` constant aliases `EVENT_MODIFY_VALUE_AS_DATA`; email event constants identify the corresponding reference-block event. Each projection dispatches one event. Register with the constants rather than hard-coded legacy event strings. A reference projection does not dispatch the public string event.

Rich values are immutable after normalisation. Replace assignments to Name/Address properties and option selections with construction of a new value. Date casts use canonical date/time strings; configured display formatting belongs to `getFieldValueAsString()`. Payment values contain submitted parts only; use `submission.getPayments()` and `submission.getSubscriptions()` to retrieve payment records.

Back up the database and retain the original Formie security key before upgrading. Trusted storage accepts existing scalar, UID-keyed nested and exact legacy encrypted representations. New writes encrypt the complete stored representation in a versioned envelope. This is a read adaptation and rewrite-on-save upgrade, so no bulk destructive rewrite is required. Earlier releases cannot read newly encrypted values; restore the pre-upgrade backup when rolling back. Refresh cached forms after deployment so recipient inputs use current opaque option tokens. Previously rendered encrypted recipient tokens are not decrypted from requests.

## Reference Runtime and Variable Picker

Reference slots use one parseable grammar with explicit native-value and text operations. The following Formie 3 tokens remain supported without Twig evaluation: `{field:handle}`, `{field.handle}`, the form/submission/system/site/user catalogue tokens such as `{formName}` and `{userEmail}`, the four date/time presets, and all-fields summary tokens. Simple `{submission.id}`-style aliases remain supported for PDF filenames. Handle resolution stays inside the owning form; ambiguous handles and deleted fields produce diagnostics.

Back up the database before upgrading. The reference-slot migration writes `{kind, value}` objects only within integration field mappings and is idempotent. Existing exact tokens become reference slots; literals retain literal semantics, including decoded provider options. Notification, form, field, redirect and rich-text token strings use compatibility parsing, so an unsafe blanket rewrite is unnecessary. Existing field-reference migrations retain exact instance identity. Roll back by restoring the pre-upgrade database, because older versions cannot read the slot objects.

Environment access changes deliberately: configure `referenceEnvironmentAllowlist` with safe names in `config/formie.php`. A `FORMIE_` prefix does not grant access. Authored notification `$NAME` aliases use the same allowlist. Submitted `$NAME` values remain literal. Secrets are never included in picker values. Header values containing CR, LF or NUL fail; HTML substitutions are escaped once and URL substitutions are encoded as components. A whole legacy URL reference retains exact URL semantics followed by destination validation.

Stored reference slots cannot execute arbitrary Twig filters, globals, functions or object traversal. Move those expressions to deliberately authored template files or [registered reference sources](/developers/custom-variable-sources). Explicit Hidden template mode and HTML template rendering remain separate template surfaces. PDF filenames and upload subpaths preserve their explicit sandboxed template surface. References in those templates use the shared runtime and their resolved values are inserted after rendering, so submitted text never becomes template code. Automation URLs use reference interpolation; move arbitrary expressions there into registered sources. Unknown references are diagnosed instead of collapsing to empty strings, and defaults do not conceal missing sources.

| Formie 3 | Formie 4 |
| --- | --- |
| `Variables::getParsedValue()` | `References::interpolateText()` with an explicit context and output context; exact destinations use `resolveValue()` |
| Formie 3 `RegisterVariablesEvent::$variables` / `ParseVariablesEvent::$variables` | Register a `ReferenceSource` through `ReferenceCatalogue::EVENT_REGISTER`; declare a namespaced ID and `FieldValueType` |

Register custom sources with namespaced IDs such as `vendor/name` and update their stored tokens to `{custom:vendor/name}`. Formie cannot infer third-party ownership or semantics. Formie 3 field email projection adapters remain available through reference blocks.

A direct fixed child reference is valid. A direct repeater-child reference needs a current row in `ReferenceContext::$rows`; parent collection selectors use `scope=first`, `last`, `index`, `all`, `count` or `rows`. Table columns are selectors on their parent, not field identities. Update code that treated an unscoped child or an unknown extension as an empty value to inspect `ResolvedReference::$diagnostic`.

## Integration Delivery Storage

Back up the database, finish pending integration and notification jobs, and retain the Formie security key before upgrading. The upgrade encrypts literal persisted connection and per-form secrets. Environment references remain portable. Formie 3 providers returning `bool` or `IntegrationResponse` remain callable through a compatibility adapter. A `false` result is a non-retryable failure unless the provider supplies more precise evidence through a guarded request. Update providers to return `IntegrationResult` and wrap each remote write in a named child operation; see [Custom Integrations](/developers/custom-integration/overview#results-and-safe-retries).

After upgrading, use Submission Delivery History to inspect delivery attempts and reconcile uncertain results before retrying. Downgrading requires the matching pre-upgrade database and code backup.

## Browser module declarations

Formie 4 distinguishes server-rendered HTML from client-rendered definitions. Browser modules apply to either product; CP edit configuration uses `getCpEditConfig()`. Public field definitions use `getClientRenderedDefinition()` and `getClientRenderedInput()`. Custom module declarations use `BrowserModuleEntry`, with a namespaced `moduleId`, unique occurrence key, explicit surfaces and form-field UID targets. The versioned manifest contains no executable `src` URLs.

Stable `getFrontEndJsModules()` declarations are adapted with a deprecation warning. Register third-party JavaScript in your trusted application bundle under `legacy:<kebab-name>`; old source URLs are ignored. Repeated declarations remain distinct. Custom client-rendered forms use a versioned bootstrap with `contractVersion: 1`; the client-rendered web component is `<formie-client-form>`.

## Completion and Runtime Configuration

| Formie 3 | Formie 4 |
| --- | --- |
| `form.setRedirectUrl(url)` | Preserved; final destination policy applies after all overrides |
| `SubmissionsController::EVENT_AFTER_SUBMISSION_REQUEST` redirect override | Preserved at actual completion for all submission transports |
| `craft.formie.populateFormValues(form, values, force = false)` | Preserved; `true` enforces values across pages and resume |
| `prePopulate` | `prefillQueryParam`; stable PHP alias and stored-configuration migration |
| Hidden `defaultOption` | `valueSource`; hydration alias and migration |
| `submitAction` values `entry` / `url` | `completionBehavior: redirect`, with `completionRedirectSource: entry` / `url` |
| `$updateSnapshot` argument on `setSettings()`, `setFieldSettings()` and `setIntegrationSettings()` | Remove this argument; configuration lifetime is managed internally |
| Settings retained in the visitor’s session | Settings needed to continue the form are stored with its progress and submission |

Back up the database before upgrading and retain Craft’s security key. The migration adds storage for temporary form settings without resaving submissions or running integrations. Refresh cached forms after deployment.

Review external completion destinations and add their exact origins to `completionRedirectAllowedOrigins`. Query forwarding now defaults to five UTM parameters; add required campaign keys to `completionQueryAllowlist`. An empty allowlist disables forwarding. Explicitly empty posted values remain empty, including Hidden fields. Query input is captured only when a new form instance starts.

Runtime overrides are deliberately allowlisted. Remove attempts to override identities, provider credentials or global integration settings. Custom fields opt their supported settings in through `runtimeOverridableSettings()`. See [Overriding Settings](/templates/overriding-settings) and [Completion and Redirects](/templates/completion-and-redirects).

## Conditions and Validation

Formie 4 accepts the stable Formie 3 `showRule`, `conditionRule` and `conditions` arrays and migrates them to the versioned condition contract. Known `==`, `equals` and `notEquals` aliases normalize to `=`, `=` and `!=`. Stable condition context selectors such as `{submission:formName}`, `{submission:siteName}`, `{submission:siteHandle}` and `{submission:dateCreated}` normalize to the shared form, site and submission sources. Unknown rules remain diagnosable invalid configuration. Existing acyclic forward references retain dependency ordering; new builder rules select preceding sources, and cycles must be corrected.

Test conditions that compare text, numbers, dates or selected options. Text comparisons use alphabetical order (`10` comes before `2`), while Number fields compare numeric values. Options match complete values rather than parts of an option. Invalid conditions block actions instead of allowing them. Date/time rules need complete dates and times, with a timezone offset for ambiguous local times.

Hidden and disabled public values are cleared recursively before validation, including nested and repeater values. Browser-posted page targets cannot bypass progression rules. Validation errors are plain text and retain complete nested paths. Stable Formie 3 AJAX still uses handle-based error keys and may return HTTP 200; the client-rendered APIs use form-field instance IDs and typed domain outcomes.

For custom front-end validation, follow [Custom Front-End Validation](#custom-front-end-validation) and [Conditions and Validation](/developers/conditions-and-validation).
