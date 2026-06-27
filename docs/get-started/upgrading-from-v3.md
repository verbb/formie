# Upgrading From v3

Formie 4 changes a lot behind the scenes, especially around form-builder schema, submission handling, front-end assets, and field values.

We have also worked to keep as much as possible backward-compatible through Compatibility Mode to make the upgrade easier.

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
- Custom field compatibility bridges for older schema methods, field config normalization, and legacy Theme Config field tags.

Leave compatibility mode enabled while you update the site. Once the project has no Formie deprecation warnings and any custom code has been updated, you can disable it:

```php
// config/formie.php
return [
    'compatibilityMode' => false,
];
```

## Breaking Changes

Although this is a major release, we have tried to keep existing Formie 3 projects working where we reasonably can. These are the main Formie 3 to 4 breaking changes that can require real code updates.

Jump directly to the sections most likely to need attention:

- [Custom Fields](#custom-fields) if the project defines custom Formie fields.
- [Custom Integrations](#custom-integrations) if the project defines custom integrations, captchas, address providers, or payment providers.
- [Removed legacy captchas](#removed-legacy-captchas) if the site relied on Formie’s built-in Duplicate, JavaScript, or Honeypot captcha types.
- [Custom Front-End Validation](#custom-front-end-validation) if the project registers custom browser validators or overrides front-end message strings.
- [Translation Strings](#translation-strings) if the project overrides Formie **plugin UI** messages in translation files.
- [Form Content Translations](#form-content-translations) if the project mapped field labels or form messages through `formie.php` or `site.php`.
- [Submission and form statuses](#submission-and-form-statuses) if custom PHP, Twig, or module code references status services, models, events, or database tables.

## Changes at a Glance

Not every important change in Formie 4 is a breaking change. Some parts of the plugin now work quite differently, and they are worth understanding before you update custom code or plan new work.

### Integration Settings

Custom integrations no longer build their form settings UI with templates. They now define schema for the form builder.

This changes how integration settings are built and maintained. If you have custom integrations, it is worth thinking of their settings in the same way as field schema: structured, reusable, and easier for Formie to normalize and extend.

### Submission Workflow

Submission processing is no longer one large save step. Formie now runs submissions through a clearer staged workflow.

That makes custom submission handling more predictable. If you need to run your own logic during processing, it is a better fit to add a workflow task or hook into the workflow events than to try to replace the whole pipeline.

The **`screen`** stage now runs built-in submission guards (honeypot, minimum submit time, replay protection) before captcha integrations and spam keyword checks. See [Submission Screening](/forms/submission-screening).

### Spam Protection settings

Spam handling, keyword rules, submission guards, and captcha provider credentials are consolidated on **Settings → Spam Protection**. Legacy **Settings → Spam** and **Settings → Captchas** routes redirect there. Values are stored in dedicated settings tables rather than `plugins.formie.settings` in project config.

If you relied on the old built-in **Honeypot**, **Javascript**, or **Duplicate** captchas, see [Removed legacy captchas](#removed-legacy-captchas).

### Front-end Browser Package

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

### Form and submission statuses

In Formie 3, **Settings → Statuses** managed workflow labels for saved submissions — for example **New**, or custom statuses you created for your team. There was no separate status system for forms themselves.

Formie 4 adds **[Form statuses](/forms/form-statuses)** — lifecycle labels you can assign to forms in the control panel (for example **Active**, **Draft**, **Archived**). That is a new feature, not a rename of something that existed in Formie 3.

Adding form statuses meant the old generic “statuses” naming was no longer clear enough. Everything Formie 3 called **Statuses** is now **[Submission statuses](/submissions/statuses)** in the control panel, PHP APIs, and database:

- **Settings → Submission Statuses** (the legacy **Settings → Statuses** route still opens this)
- `SubmissionStatuses`, `SubmissionStatus`, and related classes — replacing `Statuses`, `Status`, and so on
- Database table `formie_submission_statuses` — renamed from `formie_statuses`

Submission status project config stays at `formie.statuses`. Form statuses use a new `formie.formStatuses` key and `formie_form_statuses` table.

If your project has custom PHP, Twig, or module code that references the Formie 3 names, see [Submission and form statuses](#submission-and-form-statuses).

## Custom Fields

### Field Schema Methods

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

### Field Preview

Field previews should now use preview schema, rather than a HTML template.

::: code-group
```php [Formie 3]
public function getFormBuilderPreviewHtml(): string
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

### Field Values

The public methods trigger Formie events after the value has been defined.

Value method changes:

Formie 3 | Formie 4
--- | ---
`defineValueAsJson()` | `defineValueAsArray()`
`getValueAsJson()` | `getValueAsArray()`
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

### Field Paths

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

### Theme Config

> [!IMPORTANT]
> Compatibility mode will handle these changes automatically.

If your custom field supports Theme Config, update `defineHtmlTag()` usage to `defineFieldSlotTag()`, and return a `SlotTag`.

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

protected function defineFieldSlotTag(string $key, RenderContext $context): ?SlotTag
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

    return parent::defineFieldSlotTag($key, $context);
}
```
:::

The default CSS class prefix is now `formie`, not `fui`, for Formie 4's default theme.

See [Custom Field](/developers/custom-field) for the current custom field guide.

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
```
:::

Always start with `parent::defineFormSettingsSchema($form)` unless you have a specific reason not to. The parent schema includes the standard Enabled setting.

### Captchas and Address Providers

> [!CAUTION]
> Custom captchas and address providers need to move to client modules, and any front-end behavior for them needs to be adapted to Formie's browser module system.

Captchas and address providers now use client modules instead of ad-hoc JavaScript variables. In practice, that means moving both the PHP side and the front-end side of the integration to the current browser module approach.

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
use verbb\formie\models\ClientModule;
use verbb\formie\models\ClientModuleContext;

public function getClientModule(ClientModuleContext $context): ?ClientModule
{
    return new ClientModule([
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

## Removed legacy captchas

Formie 4 no longer ships the **Duplicate**, **JavaScript**, or **Honeypot** captcha integrations that existed in earlier major versions. They are not available in the form builder’s captcha picker, and there is no compatibility shim that re-registers them as captcha integrations.

Instead, their behaviour is covered by **submission guards** — built-in passive checks configured globally under **Formie → Settings → Spam Protection → Submission Guards**.

### What they were

- **Duplicate** attempted to block repeat submissions by comparing new requests to recent activity. In practice it was sensitive to caching, multi-page flows, and Ajax reloads, which produced hard-to-debug false positives and noisy support tickets.
- **JavaScript** was a lightweight “prove the browser ran our script” check. It duplicated behaviour that modern browsers, privacy tools, and static caching already interfere with, and it was a poor accessibility story compared to provider-backed challenges.
- **Honeypot** relied on a hidden field that legitimate users were expected to leave empty. Browser autofill, password managers, accessibility tools, and static or edge-cached HTML often touched that field anyway, which caused false positives; determined bots could also learn to avoid a known pattern.

Those approaches did not match how serious abuse is handled today: explicit provider verification, clearer spam reasons, and a single **screening** stage in the submission workflow.

### What to use instead

| Legacy captcha | Formie 4 replacement | Where to configure |
| --- | --- | --- |
| **Honeypot** | **Honeypot** submission guard | **Settings → Spam Protection → Submission Guards** |
| **Javascript** (including minimum submit time) | **Minimum submit time** submission guard | **Settings → Spam Protection → Submission Guards** |
| **Duplicate** | **Replay protection** submission guard | **Settings → Spam Protection → Submission Guards** |

Submission guards are **global** settings. They apply to all forms automatically when enabled. You do not enable them per form the way you did with the old built-in captchas.

All three guards are enabled by default after upgrade (honeypot on, three-second minimum submit time, replay protection on). Review **Settings → Spam Protection** after upgrading and adjust them for your site.

For stronger abuse protection, also enable one or more supported [Captcha integrations](/integrations/captchas/) (for example Cloudflare Turnstile, hCaptcha, or reCAPTCHA) and combine them with [Spam Protection](/forms/spam-protection) keyword rules where appropriate.

### How guards fit the workflow

Guards are not captcha integrations. They run through dedicated workflow tasks:

1. **`screen.runSubmissionGuards`** — runs first in the **`screen`** stage, before `screen.runCaptchaChecks` and `screen.runSpamChecks`. A failed guard marks the submission as spam and sets `spamReason`.
2. **`finalize.consumeReplayToken`** — runs in the **`finalize`** stage after a successful, complete submission. Replay protection only consumes the `requestToken` once processing succeeds, so failed or incomplete submissions can retry.

Guards only run for normal browser form POST requests that include `handle` and `submitAction`. GraphQL submissions and other headless flows skip them.

The honeypot input and `formStartedAt` timestamp are rendered automatically for browser forms. Replay protection reuses Formie’s existing per-render `requestToken`.

See [Submission Screening](/forms/submission-screening) for the full screening order and [Spam Protection](/forms/spam-protection#submission-guards) for configuration detail.

### Settings and storage changes

Spam handling, keyword rules, submission guards, and captcha provider credentials now live on a single **Settings → Spam Protection** page. Legacy **Settings → Spam** and **Settings → Captchas** routes redirect there.

Those values are stored in Formie’s dedicated settings tables (`formie_spam_settings`, `formie_captcha_providers`) rather than `plugins.formie.settings` in project config. Legacy plugin settings keys are stripped on save and seeded into the new stores automatically while compatibility mode is enabled.

### Custom code and GraphQL

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

Formie 3 event | Formie 4 event
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

Formie 3 event | Formie 4 event
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

> [!CAUTION]
> Custom code that calls Formie’s payment-processing step directly should be updated to let the submission workflow run instead.

Formie now processes submissions through a staged workflow. This affects custom code that expected submission processing to happen as one large save step.

Most integrations and notifications should keep working. If you previously called the payment processing step directly, update that code to let Formie's submission workflow run instead.

::: code-group
```php [Formie 3]
$success = Formie::$plugin->getSubmissions()->processPayments($submission);
```

```php [Formie 4]
// Let Formie process the submission through the normal workflow.
```
:::

`processPayments()` still exists as a compatibility shim, but standalone payment processing is no longer the main workflow path.

If you need to add custom work during submission handling, add a workflow task or listen to the workflow events instead of replacing the whole submission pipeline. See [Submission Workflow](/developers/submission-workflow).


## Form Rendering and Assets

Formie 4 has a cleaner rendering API for form assets. 

### Render Form Assets

::: code-group
```twig [Formie 3]
{{ craft.formie.renderFormAssets(form) }}
{{ craft.formie.registerFormAssets(form) }}
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
{{ craft.formie.renderRuntimeAssets() }}
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

The same method names apply if you are calling `Formie::$plugin->getRendering()` in PHP:

::: code-group
```php [Formie 3]
Formie::$plugin->getRendering()->renderFormAssets($form);
```

```php [Formie 4]
Formie::$plugin->getRendering()->formAssets($form);
```
:::

See [Frontend Assets](/frontend/frontend-assets) and [Render Options](/templates/render-options) for the full rendering options.

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

### Custom integration dispatch

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
    'processMode' => SubmissionWorkflow::PROCESS_MODE_EDIT_EXISTING,
    'triggerEvent' => IntegrationTriggerEvents::CP_SAVE,
]));

// Operator-initiated single integration run
Formie::$plugin->getIntegrationTriggers()->dispatchManualIntegration($integration, $submission);

// Low-level payload send (events still fire on Integrations)
Formie::$plugin->getIntegrations()->sendIntegrationPayload($integration, $submission);
```
:::

Learn more in [Submission Workflow](/developers/submission-workflow) and [Integration Events](/developers/events/integration-events).

### Submission and form statuses

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

#### Submission status renames (Formie 3 → Formie 4)

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
`SubmissionStatuses::getStatusesForForm()` | `SubmissionStatuses::getSubmissionStatusesForForm()`
`FormGroupPolicy::getStatusesForForm()` | `FormGroupPolicy::getSubmissionStatusesForForm()`
`FormGroupPolicy::getStatusSelectOptions()` | `FormGroupPolicy::getSubmissionStatusSelectOptions()`
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
{% set address = submission.getFieldValueAsArray('billingAddress') %}
{% set exportValue = submission.getFieldValueForExport('payment') %}
{% set summaryValue = submission.getFieldValueForSummary('billingAddress') %}
```
:::

### Submission Values

Use array terminology instead of JSON terminology.

::: code-group
```twig [Formie 3]
{% set values = submission.getValuesAsJson() %}
```

```twig [Formie 4]
{% set values = submission.getValuesAsArray() %}
```
:::

The same change applies in PHP:

::: code-group
```php [Formie 3]
$values = $submission->getValuesAsJson();
```

```php [Formie 4]
$values = $submission->getValuesAsArray();
```
:::

### Value Types

Use the helper that matches the job you are doing.

```twig
{# Normal value. Good when you want the field's natural shape. #}
{% set value = submission.getFieldValue('billingAddress') %}

{# String value. Good for text output, logs, and simple display. #}
{% set value = submission.getFieldValueAsString('billingAddress') %}

{# Array value. Good when a field has meaningful structure. #}
{% set value = submission.getFieldValueAsArray('billingAddress') %}

{# Export value. Good for CSVs, spreadsheets, and reports. #}
{% set value = submission.getFieldValueForExport('billingAddress') %}

{# Summary value. Good for review screens and summary output. #}
{% set value = submission.getFieldValueForSummary('billingAddress') %}
```

See [Submission Content](/developers/submission-content) for a fuller explanation of the different value formats.

## Text limits

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

Formie 4 standardizes several **plugin-owned** message keys that sites commonly override in `translations/*/formie.php`, especially for front-end validation and text-limit counters.

These are strings Formie ships in English and translates with `Craft::t('formie', …)`. They are separate from form field labels and messages you edit in the form builder. See [Form Content Translations](#form-content-translations) if you previously mapped builder copy through translation files.

If your project overrides plugin UI strings, update the **source keys** in your translation files. Formie looks up messages by the English source string passed to `Craft::t('formie', …)`, not by a separate message ID.

### Front-end validation placeholders

Formie-owned validation messages now use `{label}` for the field label placeholder instead of `{attribute}`.

Update any overrides that still target the old `{attribute}` keys. The English source strings themselves also changed:

Old source key (Formie 3) | New source key (Formie 4)
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

### Text limit counter copy

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

Old | New
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

### Server-side unique-value messages

The default unique-value validation message source key is now:

```php
'"{label}" must be unique.'
```

If you previously overrode a field-specific unique message via translation files alone, consider using the **Unique Error Message** field on the Validation tab instead (`validationMessages.unique`), which supports `{label}` and other allowed placeholders without requiring a global translation override.

## Form Content Translations

Formie no longer passes user-authored form copy through the `formie` translation category at render time. Labels, placeholders, messages, and option labels come from the database (with [site overrides](/forms/multi-site#content-translation) on multi-site projects).

This replaces an older workaround that mutated the plugin `sourceLanguage` on front-end requests so German (or other non-English) canonical labels would not be reverse-translated to English via `formie.php`.

### Audit your translation files

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

### Migration paths

| Old pattern | New approach |
| --- | --- |
| `'Your name' => 'Votre nom'` in `fr/formie.php` | French **site override** in the form builder |
| German labels written in the builder, no overrides | No change — copy renders as stored |
| Plugin validation overrides in `de/formie.php` | Keep in `formie.php` |
| Per-field validation text | **Validation** tab on the field (`validationMessages.*`) |
| Two English sites, different labels | CP site overrides (not locale files) |

### Single-site projects

Edit the form in the control panel. You do not need translation file entries for field labels.

Learn more in [Translations](/forms/translations).

## Sub-field Label Position

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

The old fieldset-specific instruction positions are normalized to regular positions.

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

## Static Caches

Static-cache handling has changed. Formie can refresh request-specific tokens for statically cached forms when the `staticCacheRefreshOnLoad` plugin setting is enabled, and it assumes static-cache handling is needed when [Blitz](http://) is installed and enabled.

```php
// config/formie.php
return [
    'staticCacheRefreshOnLoad' => true,
];
```

You no longer need to include a JavaScript snippet to refresh the tokens.

See [Cached Forms](/frontend/cached-forms) and [Configuration](/get-started/configuration).

## Save and Continue Later

Formie 4 has proper save-and-continue handling with saved drafts and resume tokens.

In Formie 3, incomplete submission state relied much more on the session. In Formie 4, temporary, incomplete, and saved draft submission state is stored in the database, and resume links are tied to stored draft state and token records.

For normal forms, this should not require template changes. If you customize save buttons, draft handling, or submission retention, review [Save & Continue Later](/forms/save-continue-later).

Related settings include:

Setting | Use
--- | ---
`submissionStateRetentionDays` | How long stored submission state is retained.
`saveResumeTokenTtlDays` | How long resume links remain valid.
`maxSavedDraftsPerSession` | How many saved drafts can be kept for a session.

Learn more in [Save & Continue Later](/forms/save-continue-later) and [Configuration](/get-started/configuration).

## New and Renamed Settings

Review `config/formie.php` if your project keeps a full config file.

The most upgrade-relevant settings are:

Setting | Use
--- | ---
`compatibilityMode` | Enables Formie 3 compatibility shims. Defaults to `true`.
`staticCacheRefreshOnLoad` | Enables token refresh support for static-cache setups that are not auto-detected.
`submissionStateRetentionDays` | Controls retention for stored submission state.
`saveResumeTokenTtlDays` | Controls resume link lifetime.
`maxSavedDraftsPerSession` | Controls saved draft count per session.
`setOnlyCurrentPagePayload` | Limits front-end page payload behavior to the current page.
`anonymousClientBootstrapRateLimit` | Rate limit for anonymous form bootstrap requests.
`anonymousClientRefreshRateLimit` | Rate limit for anonymous token refresh requests.
`anonymousClientRateWindowSeconds` | Rate-limit window used by the anonymous request limits.
`useCssLayers` | Outputs Formie's CSS using CSS layers when enabled.

Removed settings are ignored during settings normalization:

Removed setting | What to do
--- | ---
`enableGatsbyCompatibility` | Remove it.
`submissionStateMode` | Remove it.
`submissionStore` | Remove it.

See [Configuration](/get-started/configuration) for the current config shape.

## Replacement Reference

Old | New
--- | ---
`form.getFormId()` | `form.getRenderId()`
`form.setFormId()` | `form.setRenderId()`
`submission.getValueAsString()` | `submission.getFieldValueAsString()`
`submission.getValueAsJson()` | `submission.getFieldValueAsArray()`
`submission.getValuesAsJson()` | `submission.getValuesAsArray()`
`field.getValueAsJson()` | `field.getValueAsArray()`
`field.defineValueAsJson()` | `field.defineValueAsArray()`
`field.getFieldKey()` | `field.valueKey()`
`field.getErrorKey()` | `field.errorKey()`
`field.getFullHandle()` | `field.handlePath()`
`field.getFullNamespace()` | `field.namespacePath()`
`craft.formie.renderFormAssets(form)` | `craft.formie.formAssets(form)`
`craft.formie.registerFormAssets(form)` | `craft.formie.formAssets(form)`
`craft.formie.renderFormCss(form)` | `craft.formie.formAssets(form, { includeJs: false })`
`craft.formie.renderFormJs(form)` | `craft.formie.formAssets(form, { includeCss: false })`
`craft.formie.renderRuntimeAssets()` | `craft.formie.frontendAssets()`
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
`getFrontEndJsVariables()` for captchas/providers | `getClientModule()`
`enableJsEvents` | `enableClientEvents`
`jsGtmEventOptions` | `clientEventFields`
`onAfterFormieSubmit` | `formie:submit:result`
`formieValidatorInitialized` | `formie:validator:ready`
`verbb\formie\services\Statuses` | `verbb\formie\services\SubmissionStatuses`
`verbb\formie\models\Status` | `verbb\formie\models\SubmissionStatus`
`verbb\formie\events\StatusEvent` | `verbb\formie\events\SubmissionStatusEvent`
`Formie::$plugin->getStatuses()` | `Formie::$plugin->getSubmissionStatuses()`
`craft.formie.getStatuses()` | `craft.formie.getSubmissionStatuses()`
`SubmissionStatuses::getStatusesForForm()` | `SubmissionStatuses::getSubmissionStatusesForForm()`
`Table::FORMIE_STATUSES` | `Table::FORMIE_SUBMISSION_STATUSES`
Database table `formie_statuses` | `formie_submission_statuses`
