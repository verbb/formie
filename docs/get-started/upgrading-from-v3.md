# Upgrading from v3

Formie 4 enables compatibility mode by default. Work through this guide in three stages:

1. [Breaking Changes](#breaking-changes): fix affected code or configuration before upgrading, even with compatibility mode enabled.
2. [Before Disabling Compatibility Mode](#before-disabling-compatibility-mode): keep compatibility mode enabled until you have replaced these usages.
3. [Deprecated Changes](#deprecated-changes): these APIs and configuration aliases still work with compatibility mode off. Update them as you maintain your project.

The [Behaviour Changes](#behaviour-changes) section covers automatic migrations and other differences to be aware of. [Earlier Formie 4 extensions](#updating-earlier-formie-4-extensions) have separate notes; skip those when upgrading from stable Formie 3.

Test the upgrade on staging first. Before upgrading, back up the database and code, retain the existing Craft and Formie security keys, finish pending notification/integration jobs and allow active checkout sessions to finish. Deploy matching PHP and browser assets, then purge cached form HTML. Roll back using the matching pre-upgrade database and code backup.

## Breaking Changes

These changes are required **only if your project uses the affected API or depends on the old behaviour**. Compatibility mode does not restore that behaviour. Most sections concern custom templates, modules, integrations or submission clients; they are not a checklist of edits for every site.

- **Custom fields and integrations:** update [field UI/templates](#custom-fields), [parent fields](#custom-parent-fields), [field traversal](#field-and-row-traversal), [integration settings](#custom-integrations) and [browser modules](#browser-module-declarations).
- **Custom frontend code:** review [JavaScript event behaviour](#javascript-event-behaviour), [validators](#custom-front-end-validation), [GraphQL queries](#graphql), [submission clients and uploads](#continuity-and-upload-upgrade-actions), and [CSS selectors](#default-theme-classes).
- **Templates and configuration:** review [draft contexts replacing `sessionKey`](#scoping-in-progress-submissions), [field values](#field-value-contracts), [references](#references-and-variable-picker), [Twig in saved settings](#twig-expressions-in-references), [redirects and overrides](#completion-and-setting-overrides), [plugin translations](#translation-strings) and [form content translations](#form-content-translations).
- **Custom PHP and SQL:** update [submission hooks](#submission-workflow), [payment providers](#payment-and-subscription-boundary) and [literal status-table references](#submission-status-database-table).
- **Spam protection and conditions:** configure [spam protection per installation](#local-spam-protection-settings), replace references to [removed captcha types](#removed-legacy-captchas) and test [conditions and validation](#conditions-and-validation) that depend on changed comparisons.

### Custom Fields

#### Field Preview

Custom field previews must use schema instead of an HTML template.

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
use verbb\formie\helpers\SchemaHelper;

public function defineFormBuilderPreviewSchema(): array
{
    return [
        SchemaHelper::previewInput(),
    ];
}
```
:::

#### Custom Input Rendering

Formie 4 no longer calls `getFrontEndInputHtml()`. Replace the override with a template path and template variables:

::: code-group
```php [Formie 3]
use craft\helpers\Template;
use verbb\formie\elements\Form;
use Twig\Markup;

public function getFrontEndInputHtml(Form $form, mixed $value, array $renderOptions = []): Markup
{
    $variables = $this->getFrontEndInputOptions($form, $value, $renderOptions);
    $variables['placeholder'] = $this->placeholder;

    return Template::raw($form->renderTemplate('my-module/my-field/input', $variables));
}
```

```php [Formie 4]
use verbb\formie\elements\Form;

public static function getInputTemplatePath(): string
{
    return 'my-module/my-field/input';
}

public function getInputTemplateVariables(Form $form, mixed $value): array
{
    $variables = parent::getInputTemplateVariables($form, $value);
    $variables['placeholder'] = $this->placeholder;

    return $variables;
}
```
:::

For rendering that cannot use a template, override `renderInput(Form $form, mixed $value)`; see [Custom Field Templates](/developers/custom-field/overview#templates). Existing template-path aliases can be updated [later](#field-templates).

#### Custom Field Tags

If your custom field overrides `defineHtmlTag()` for a tag already supplied by its base field, move that override to `defineSlotTag()`. The new tag takes precedence even with compatibility mode enabled. Also replace any `parent::defineHtmlTag()` calls: that parent method no longer exists.

The [compatibility fallback](#theme-config) only supports legacy tags for which the new renderer returns no tag. Use the following pattern when updating an override:

::: code-group
```php [Formie 3]
public function defineHtmlTag(string $key, array $context = []): ?HtmlTag
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

#### Field Value Hooks

`EVENT_MODIFY_VALUE_FOR_VARIABLE` and `EVENT_MODIFY_VALUE_FOR_VARIABLE_RAW` have been removed. For values inserted into text, such as a notification subject:

::: code-group
```php [Formie 3]
use verbb\formie\base\Field;
use verbb\formie\events\ModifyFieldEmailValueEvent;
use yii\base\Event;

Event::on(Field::class, Field::EVENT_MODIFY_VALUE_FOR_VARIABLE_RAW, function(ModifyFieldEmailValueEvent $event) {
    if ($event->field->handle === 'referenceCode') {
        $event->value = strtoupper((string)$event->value);
    }
});
```

```php [Formie 4]
use verbb\formie\base\Field;
use verbb\formie\events\ModifyFieldValueEvent;
use yii\base\Event;

Event::on(Field::class, Field::EVENT_MODIFY_VALUE_FOR_REFERENCE, function(ModifyFieldValueEvent $event) {
    if ($event->field->handle === 'referenceCode') {
        $event->value = strtoupper((string)$event->value);
    }
});
```
:::

For a field's value in an all-fields notification summary, use `EVENT_MODIFY_VALUE_FOR_REFERENCE_BLOCK` with `ModifyFieldEmailValueEvent` instead. See [Field Events](/developers/events/field-events) for the event reference.

If your `EVENT_MODIFY_VALUE_AS_STRING` listener also changed variable output, move that part to the reference event above; variable output no longer triggers the string event.

Replace hard-coded event names with the current constants:

Formie 3 | Formie 4
--- | ---
`'modifyValueAsJson'` | `Field::EVENT_MODIFY_VALUE_AS_DATA`
`'modifyValueForEmail'` | `Field::EVENT_MODIFY_VALUE_FOR_REFERENCE_BLOCK`

Before disabling compatibility mode, update any old [field schema methods](#field-schema-methods). Other [field template](#field-templates) and [value method](#field-values) renames can wait until later.

### Custom Parent Fields

Update custom nested field bases to the Formie 4 classes:

| Formie 3 | Formie 4 |
| --- | --- |
| `NestedField` | `ParentField` (shared parent behaviour) |
| `SingleNestedField` with editable child fields | `ContainerParentField` |
| `SubField` with intrinsic parts, such as Name | `FixedParentField` |
| `MultiNestedField` | `RepeatableParentField` |

See [Custom Field](/developers/custom-field/overview) for child definitions, storage and rendering.

<span id="field-extensions-and-portable-forms"></span>

### Field and Row Traversal

Update templates that pass boolean traversal flags:

Formie 3 | Formie 4
--- | ---
`row.getFields(false)` | `row.getEnabledFields()`
`page.getRows(false)` | `page.getEnabledRows()`

Use `getFieldsRecursively()` for recursive traversal and `field.getFields(rowKey)` for a repeater row.


### Custom Integrations

#### Form Integration Settings

Custom integrations that add controls to a form's **Integrations** tab must replace their Twig/Vue/HTML settings UI with `defineFormSettingsSchema()`.

::: code-group
```php [Formie 3]
public function getFormSettingsHtml($form): string
{
    $variables = $this->getFormSettingsHtmlVariables($form);

    return Craft::$app->getView()->renderTemplate('my-module/integration/form-settings', $variables);
}
```

```php [Formie 4]
use verbb\formie\attributes\FormIntegrationSetting;
use verbb\formie\base\FormInterface;
use verbb\formie\helpers\SchemaHelper;

#[FormIntegrationSetting]
public ?string $endpointUrl = null;

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

Formie 4 only saves and populates form-level integration attributes annotated with `#[FormIntegrationSetting]`. Annotate every existing property used by your form schema. Unannotated properties will not be saved as form settings; keep credentials and other global settings on the integration.

#### Custom Integration Endpoints

If your integration sends submissions to a URL configured on each form, update its delivery call. These examples use the `endpointUrl` setting above and your existing `$submission` and `$payload` variables.

::: code-group
```php [Formie 3]
$response = $this->deliverPayload($submission, $this->endpointUrl, $payload);
```

```php [Formie 4]
$response = $this->deliverPayloadToPublicEndpoint($submission, $this->endpointUrl, $payload);
```
:::

If your integration uses `request()` directly, change that call instead:

::: code-group
```php [Formie 3]
$response = $this->request('POST', $this->endpointUrl, [
    'json' => $payload,
]);
```

```php [Formie 4]
$response = $this->requestPublicEndpoint('POST', $this->endpointUrl, [
    'json' => $payload,
]);
```
:::

Keep using `request()` and `deliverPayload()` for the integration provider's fixed API endpoints. See [Custom Integrations](/developers/custom-integration/overview#form-settings-schema) for form-configured URL requirements.

#### Captchas and Address Providers

Custom captchas and address providers must replace `getFrontEndJsVariables()` with `getBrowserModule()` and register their JavaScript in the application bundle.

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
use verbb\formie\models\BrowserModule;
use verbb\formie\models\BrowserModuleContext;

public function getBrowserModule(BrowserModuleContext $context): ?BrowserModule
{
    return new BrowserModule([
        'moduleId' => 'example:my-captcha',
        'surfaces' => [BrowserModule::SURFACE_SERVER_RENDERED],
        'config' => [
            'siteKey' => $this->siteKey,
        ],
    ]);
}
```
:::

See [Captcha Integration](/developers/custom-integration/captcha-integration) and [Address Provider Integration](/developers/custom-integration/address-provider-integration) for the fuller flow.

#### Captcha GraphQL Data

GraphQL no longer invokes `getGqlVariables()`. Update custom captchas to the [Captcha Integration](/developers/custom-integration/captcha-integration) contract.

The [integration config](#integration-config) and [method-name](#integration-method-names) aliases remain available with compatibility mode off; update those when maintaining your integration.

### Browser Module Declarations

Formie 4 no longer loads JavaScript from the `src` URL returned by `getFrontEndJsModules()`. Adapt your JavaScript to the browser module API and register it in your application bundle before mounting the form.

You can keep the existing PHP declaration during this step. For example, a field returning `'module' => 'MyRating'` from `getFrontEndJsModules()` needs JavaScript registered as `legacy:my-rating`.

In your Twig template, disable automatic initialisation so your bundle can register the module first:

```twig
{{ craft.formie.renderForm(form, { initJs: false }) }}
```

In your application bundle, register the field's behaviour before mounting the form. This example assumes your custom field template contains a range input and an `<output>` element inside Formie's standard field wrapper:

```js
import { createFormieClient, formie } from '@verbb/formie-browser';

const client = createFormieClient();

client.registerModule({
  moduleId: 'legacy:my-rating',
  version: 2,
  surfaces: ['server-rendered'],
  kind: 'field',
  match: ({ target }) => Boolean(target.querySelector('input[type="range"]')),
  async setup({ target }) {
    const input = target.querySelector('input[type="range"]');
    const output = target.querySelector('output');
    const update = () => { output.textContent = input.value; };

    input.addEventListener('input', update);
    update();

    return { destroy: () => input.removeEventListener('input', update) };
  },
});

await formie({ element: '[data-formie-form]', client });
```

You can update the [PHP declaration](#browser-module-php-declarations) later. See [Build a Custom Module](https://docs.verbb.io/formie/browser/modules/build-a-custom-module) for more on field markup, configuration and module registration.

### JavaScript Event Behaviour

Most existing event listeners continue to work with compatibility mode enabled. See [Front-End JavaScript Events](#front-end-javascript-events) below for the full before-and-after examples and event-name mappings. Only these exceptions need attention before upgrading:

- `onFormieLoaded` and `onFormieInit` now fire when each form mounts. Update code that assumes a single page-wide initialisation event or relies on the old JavaScript instance API.
- Legacy submission and validation events do not forward `preventDefault()` to the new workflow. Update listeners that use these events to stop processing.
- `onFormieThemeReady` no longer fires. Use `formie:validator:ready`; see the [custom validation example](#custom-front-end-validation) below.

Other supported event names can be updated later, before disabling compatibility mode.

### Custom Front-End Validation

Update custom validators registered through `onFormieThemeReady` to use `formie:validator:ready`. The browser bridge does not restore `onFormieThemeReady`. Test both valid and invalid input after updating.

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

Learn more in [Frontend Assets](/frontend/frontend-assets).

### GraphQL

#### Loading Form HTML

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

#### Decimal Query Variables

Number fields and numeric Table cells now use `FormieDecimal` and return decimal strings. Update declared variables and send decimal values as strings to preserve precision.

Formie 3 | Formie 4
--- | ---
`$amount: Number` | `$amount: FormieDecimal`

#### Integration Settings in GraphQL

Form integration settings returned through GraphQL contain only values needed by the browser. Make, n8n and Web Request destination URLs are server-side settings and are not returned. Custom clients should keep those destinations on the server rather than reading them from form queries.

#### Custom GraphQL Submission Clients

Custom submission clients must also update their [tokens and version handling](#continuity-and-upload-upgrade-actions). See [Rendering Forms](/graphql/rendering-forms) and [Create Submissions](/graphql/create-submissions) for complete requests. The [page-setting renames](#graphql-page-settings) remain covered by compatibility mode.

### Submission Workflow

If your module uses `onBeforeSubmission()`, `onAfterSubmission()` or the old before/after submission events, update it before upgrading. Formie 4 no longer calls these hooks, and compatibility mode does not restore them. Choose the replacement according to what your code does.

#### Checking or Changing Values Before Saving

For checks previously run in `beforeSubmission` or `beforeIncompleteSubmission`, move the check into a **workflow task**: a PHP class whose `execute()` method lets processing continue or stops it with an error.

For example, this task checks an order reference and shows a validation error when it is invalid:

```php
use verbb\formie\enums\SubmissionOutcomeType;
use verbb\formie\workflow\WorkflowContext;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;

class CheckOrderTask implements TaskInterface
{
    public function execute(WorkflowContext $context): TaskResult
    {
        if ($context->command->form->handle !== 'orders') {
            return TaskResult::continue();
        }

        $submission = $context->command->submission;
        $reference = (string)$submission->getFieldValue('orderReference');

        if ($reference !== '' && !str_starts_with($reference, 'ORD-')) {
            $submission->addError('field:orderReference', 'Enter a reference beginning with ORD-.');

            return TaskResult::stop($context->result(SubmissionOutcomeType::VALIDATION_FAILED));
        }

        return TaskResult::continue();
    }
}
```

Replace the form handle, field handle and check with your own. Register the task in your Craft module using the [task registration example](/guides/submissions-workflows/adding-a-custom-workflow-task-from-scratch#register-the-task). That places it after Formie's field validation and before saving, so a failed check cannot save the submission or send notifications.

If your old hook changed field values, place that task **before** field validation so Formie validates the changed values. The same guide shows [how to choose a position](/guides/submissions-workflows/adding-a-custom-workflow-task-from-scratch#choose-a-position).

#### Running Code After a Page or Form Submit

If you used `afterSubmission` or `afterIncompleteSubmission`, choose when your code should run:

- **When the form becomes complete:** use `Submission::EVENT_AFTER_COMPLETE`. This does not fire for intermediate pages or later edits to an already complete submission.
- **After a successful Next-page submission:** use `SubmissionWorkflow::EVENT_AFTER_PAGE_ADVANCE`. This does not fire for Back navigation, saved drafts or final completion.

For example, replace a listener that runs after a successful final submission with:

```php
use verbb\formie\elements\Submission;
use verbb\formie\events\SubmissionCompleteEvent;
use yii\base\Event;

Event::on(Submission::class, Submission::EVENT_AFTER_COMPLETE, function(SubmissionCompleteEvent $event) {
    $submission = $event->submission;

    // Move your successful-submit code here.
});
```

These events run after saving and before notifications and integrations. They do not report failed attempts. See [Run Custom Code on Page Submit or Form Submit](/guides/submissions-workflows/run-custom-code-on-page-submit-or-form-submit) for both listener examples, or [workflow events](/developers/events/submission-events#submission-workflow-events) if your code observes another point in processing.

<span id="spam-checks-and-direct-saves"></span>

#### Custom Spam Checks

Move custom checks from `spamChecks()`, `beforeSpamCheck` or `afterSpamCheck` into a task in the Screen stage, before or after Formie's `screen.evaluateSpam` task. Use the same [task registration pattern](/guides/submissions-workflows/adding-a-custom-workflow-task-from-scratch#choose-a-position); [Screen stage](/developers/submission-workflow#screen) explains where custom spam checks belong. Test custom checks with invalid input, page changes and a completed submission.

#### Programmatic Submissions

Direct element saves persist data but do not run the submission workflow or automatically dispatch notifications and integrations. Code that submits a form programmatically must use [SubmissionProcessor](/developers/submission-workflow). Existing notification and integration forwarding methods are covered under [Custom Integration Dispatch](#custom-integration-dispatch).

Next, review the [notification and integration event changes](#php-events) before disabling compatibility mode.

### Local Spam Protection Settings

Spam settings and provider credentials migrate automatically into local database storage. The upgrade preserves each installation’s effective values, enabled state and credential references, including settings previously managed through project config. Authorised clients can edit them directly in **Spam Protection** with admin changes disabled. The old **Settings → Spam** and **Settings → Captchas** URLs redirect there.

Spam rules and provider credentials are not exported to or updated by project config. Configure each installation separately; a database copy still carries these settings. Existing `formie.captchaProviders` and `formie.spamSettings` entries are ignored. Rebuilding project config removes those obsolete entries. Upgrade all installations before deploying that removal, because older versions can interpret it as an instruction to clear settings. See [Setting Up Production](/forms/spam-protection#setting-up-production).

### Removed Legacy Captchas

Remove references to these captcha types or handles from custom PHP and GraphQL requests. Their replacements are built-in [submission guards](/forms/spam-protection#submission-guards):

Formie 3 | Formie 4
--- | ---
**Honeypot** captcha | **Honeypot** submission guard
**JavaScript** captcha | **Minimum submit time** submission guard
**Duplicate** captcha | **Replay protection** submission guard

Built-in configuration is [migrated automatically](#spam-protection). Use [Submission Workflow](/developers/submission-workflow#screen) for custom server-side checks. Custom clients must still follow the [submission token and version requirements](#continuity-and-upload-upgrade-actions).

### Translation Strings

Update custom text-limit counter translations and browser translation event listeners before upgrading. These are separate from [form content translations](#form-content-translations).

Supported [validation-message keys](#validation-message-translations) can wait until compatibility mode is disabled. One exception is `{attribute} must select between {min} and {max}.`: if you overrode that combined key, replace it with the separate [minimum and maximum option messages](#front-end-validation-placeholders).

#### Text Limit Counter Copy

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

Use these six source keys in your translation file. For example, in `translations/de/formie.php`:

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

#### Browser Translation Event

Update custom browser translation listeners:

Formie 3 | Formie 4
--- | ---
`Rendering::EVENT_MODIFY_FRONT_END_JS_TRANSLATIONS` | `Rendering::EVENT_MODIFY_BROWSER_JS_TRANSLATIONS`

### Form Content Translations

Formie no longer passes user-authored form copy through the `formie` translation category at render time. Labels, placeholders, messages, and option labels come from the database (with [site overrides](/forms/multi-site#content-translation) on multi-site projects).

#### Audit Your Translation Files

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

#### Migration Paths

For labels, placeholders and messages previously translated through locale files, select the destination site in the form builder and enter its site overrides. This also supports two sites with the same language but different copy.

Keep plugin validation translations in `formie.php`. For a message specific to one field, use its **Validation** tab (`validationMessages.*`). See [Content Translation](/forms/multi-site#content-translation) for the site-override controls.

#### Single-Site Projects

Edit the form in the control panel. You do not need translation file entries for field labels.

Learn more in [Static Translations](/forms/static-translations).

<span id="theme-attributes"></span>

### Default Theme Classes

Formie 4’s default theme uses the `formie` CSS class prefix. If your site styles Formie 3’s `.fui-` classes, update those selectors or adopt the current theme variables before deploying. A PHP compatibility bridge cannot rewrite your stylesheet.

Formie 3 | Formie 4
--- | ---
`.fui-form` | `.formie-form`

Theme configuration can no longer remove required functional or accessibility attributes. Update customisations that depended on removing them; trusted `EVENT_MODIFY_SLOT_TAG` listeners remain available for deliberate overrides.

If you use the Formie 3 [Tailwind](https://github.com/verbb/formie-theme-configs/blob/formie-3/tailwind/index.html) or [Bootstrap](https://github.com/verbb/formie-theme-configs/blob/formie-3/bootstrap/index.html) theme examples, use the corresponding Formie 4 [Tailwind](https://github.com/verbb/formie-theme-configs/blob/formie-4/tailwind/index.html) or [Bootstrap](https://github.com/verbb/formie-theme-configs/blob/formie-4/bootstrap/index.html) example as your starting point and carry over your customisations. These examples include the theme configuration changes below, as well as the updated classes.

#### Theme Configuration Keys

You can still pass `themeConfig` to `craft.formie.renderForm()`. Static framework classes such as Bootstrap’s `form-control` and `btn` remain valid, but some keys identifying the parts of the form have changed. Update these keys in your Twig or PHP theme configuration:

Formie 3 | Formie 4
--- | ---
`alertError` | `messageError`
`alertSuccess` | `messageSuccess`
`buttonWrapper` | `pageButtons`

Review other customisations against the [Theme Tag Reference](/reference/theme-tag-reference), particularly those targeting wrappers whose markup has changed. Compatibility mode does not restore the old default markup or these keys.

#### Conditional Classes

Formie 3 theme examples contain strings with embedded Twig expressions. Formie 4 rejects these expressions, including method calls, even with compatibility mode enabled. Replace them with structured `if`, `then`, and optional `else` values. This affects expressions stored inside configuration strings; you can still use Twig to build the configuration before passing it to Formie.

For example, replace this `checkboxes` entry inside your `themeConfig` to add Bootstrap’s inline class when the field uses a horizontal layout:

::: code-group
```twig [Formie 3]
checkboxes: {
    fieldOption: {
        attributes: {
            class: [
                'form-check',
                "{{ field.layout == 'horizontal' ? 'form-check-inline' : '' }}",
            ],
        },
    },
},
```

```twig [Formie 4]
checkboxes: {
    fieldOption: {
        attributes: {
            class: [
                'form-check',
                {
                    if: { context: 'field.layout', equals: 'horizontal' },
                    then: 'form-check-inline',
                },
            ],
        },
    },
},
```
:::

For states that change in the browser, use the top-level [browser theme state keys](/reference/browser-theme-state) instead of render-time conditions. For example, move Bootstrap’s `active` class from a conditional `pageTabLink` class to `tabLinkCurrent.attributes.class`, `d-none` for hidden pages to `pageHidden.attributes.class`, and `border-danger` for invalid inputs to `fieldControlError.attributes.class`. This lets Formie update the classes during Ajax validation and page changes. The Formie 4 framework examples include these state configurations.

Inspect each styled form, including validation errors, page navigation and nested fields, after rebuilding your site assets. See [Theme Config](/theming/theme-config) for the current theming approach. Update the [legacy custom tag fallback](#theme-config) before disabling compatibility mode.

### Submission Status Database Table

Custom SQL that queries `formie_statuses` must use `formie_submission_statuses`. The migration renames the table automatically, but compatibility aliases cannot rewrite SQL.

Formie 3 | Formie 4
--- | ---
`formie_statuses` | `formie_submission_statuses`

PHP/Twig status aliases remain available as described under [Submission and Form Statuses](#submission-and-form-statuses).

### Field Value Contracts

<span id="name-and-date-output"></span>

#### Name Field Output

Name fields now return `NameFieldValue`, including single-name mode. Ordinary Twig output still displays the name. When your code requires a string, use `getFieldValueAsString()`:

```twig
{{ submission.getFieldValueAsString('fullName') }}
```

#### Date Display Formatting

For a Date field's configured display format instead of its raw date/time string, use `getFieldValueAsString()`:

```twig
{{ submission.getFieldValueAsString('appointmentDate') }}
```

Replace these example handles with your own. See [Reading Submission Values](/developers/submission-content#reading-submission-values) for the output helpers.

#### Changing a Field Value

Name, Address and option values are now immutable: assigning to a property on the existing value no longer works. Create a replacement value and assign it to the submission. For example, to change the first name in a multi-part Name field while keeping its other parts:

```php
use verbb\formie\fields\values\NameFieldValue;

$name = $submission->getFieldValue('fullName');
$parts = $name->toArray();
$parts['firstName'] = 'Ada';

$submission->setFieldValue('fullName', new NameFieldValue($parts));
```

Save the submission through your existing save or submission workflow after assigning the replacement. Apply the same replacement approach to Address properties and selected options.

<span id="numbers-and-phone-values"></span>

#### Number Values

GraphQL returns Number values as decimal strings. Update client types and the [GraphQL query type](#decimal-query-variables). For a Number field named `amount`:

::: code-group
```ts [Formie 3]
type SubmissionValues = { amount: number | null };
```

```ts [Formie 4]
type SubmissionValues = { amount: string | null };
```
:::

#### Phone Values

Replace `hasCountryCode` checks with a check for the value's country. For a Phone field named `phone`:

::: code-group
```php [Formie 3]
$hasCountry = $submission->getFieldValue('phone')->hasCountryCode;
```

```php [Formie 4]
$hasCountry = $submission->getFieldValue('phone')->country !== null;
```
:::

To check whether the field enables country selection, use `$submission->getFieldByHandle('phone')->countryEnabled`. String output uses a valid E.164 number, or preserves invalid entered text.

#### Empty Agree Values

Missing Agree input normalises to `false`. For an Agree field named `agree`, update empty-value checks:

::: code-group
```php [Formie 3]
$isUnchecked = $submission->getFieldValue('agree') === null;
```

```php [Formie 4]
$isUnchecked = $submission->getFieldValue('agree') === false;
```
:::

#### Payment Records

If your template reads payment records from a Payment field's value, switch to `submission.getPayments()` or `submission.getSubscriptions()`. The field value now contains only submitted input.

Working method aliases are listed under [Field Values](#field-values). Encryption has its own [activation and conversion process](#encrypted-submission-fields).

<span id="reference-runtime-and-variable-picker"></span>

### References and Variable Picker

#### Parsing Values in Custom Code

Replace `craft.formie.getParsedValue()` or PHP’s `Variables::getParsedValue()` according to the result you need: `parseContent()` returns text with tokens replaced; `parseValue()` returns the value of one token without converting it to display text.

::: code-group
```twig [Twig]
{{ craft.formie.parseContent('Reference: {submission:uid}', submission) }}
{% set submissionId = craft.formie.parseValue('{submission:id}', submission) %}
```

```php [PHP]
use verbb\formie\helpers\References;

$text = References::parseContent('Reference: {submission:uid}', $submission);
$submissionId = References::parseValue('{submission:id}', $submission);
```
:::

Code needing explicit output encoding or a custom context can use `References::interpolateText()` or `References::resolveValue()`. See [Exact Values and Text](/developers/reference-tokens#exact-values-and-text) for that example.

#### Custom Variable Sources

If your module added picker variables through `RegisterVariablesEvent::$variables` or `ParseVariablesEvent::$variables`, replace that registration with `ReferenceCatalogue::EVENT_REGISTER`. Each `ReferenceSource` supplies a label, its value type (`FieldValueType`) and a callback that returns the value.

Follow the [Custom Variable Sources example](/developers/custom-variable-sources), then update your custom tokens to the registered ID. For example, a source registered as `acme/campaign` uses `{custom:acme/campaign}`. Formie cannot infer replacements for third-party variable names.

<span id="environment-variables-and-twig-expressions"></span>

#### Environment Variable Access

If notifications or other references read an environment variable, add its name to `referenceEnvironmentAllowlist` in `config/formie.php`:

```php
return [
    'referenceEnvironmentAllowlist' => ['CAMPAIGN_CODE'],
];
```

This also applies to authored notification `$NAME` aliases. A `FORMIE_` prefix does not allow access automatically.

#### Twig Expressions in References

Formie 3 passed many variable-aware settings through Craft's object-template renderer, which also allowed sandboxed Twig expressions and filters. Formie 4 resolves [reference tokens](/developers/reference-tokens) in these settings instead. Review any saved settings containing Twig output, Twig tags, filters or object-property expressions. Compatibility mode does not restore Twig evaluation in reference-only settings.

The settings to review include:

| Setting | Formie 4 Behaviour |
| --- | --- |
| File Upload **Upload Location** subpath and **Filename Format** | Resolve references and supported transforms. Uploaded asset filenames have the same reference-only behaviour as subpaths. |
| Hidden **Custom Value** defaults | Resolve references in the authored default. Use the separate **Template** source if the field needs sandboxed Twig. |
| Form **Submission Title Format** | Resolve references and supported transforms. |
| Notification subject, sender and recipient settings, and authored **Email Content** | Resolve references. Twig in the notification's content setting is not evaluated. |
| Variable-aware form messages and completion redirect URLs | Resolve references. A redirect URL containing Twig output or tags is rejected. |
| Automation URLs, reference-aware integration mappings and payment settings | Use references for dynamic values; custom text does not become a Twig template. |

Replace straightforward values and filters with the tokens inserted by the Variable Picker. For example:

| Setting | Formie 3 | Formie 4 |
| --- | --- | --- |
| Upload subpath using a submission ID | `uploads/{id}` | `uploads/{submission:id}` |
| Upload subpath using the submitting user | `{user.fullName\|kebab}-{user.id}` | `{user:fullName;transform=kebab}-{user:id}` |
| Upload subpath using a form field | `uploads/{myFieldHandle}` | `uploads/{field:a1b2c3}` |
| Uploaded filename using a form handle and submission ID | `{form.handle}-{id}` | `{form:handle}-{submission:id}` |
| Hidden Custom Value using an uppercase form handle | `{form.handle\|upper}` | `{form:handle;transform=upper}` |
| Submission title using the current date | `{{ "now"\|date("Y-m-d") }}` | `{timestamp;transform=format;preset=isoDate}` |
| Notification subject using an uppercase form name | `{formName\|upper}` | `{form:name;transform=upper}` |

`a1b2c3` represents the field's stable reference, not its handle. Insert that token with the Variable Picker; do not copy the placeholder or type `{field:myFieldHandle}`. For the user-based subpath, enable **Collect User** and require the person submitting to be logged in. A user named Ada Lovelace with ID 123 produces `ada-lovelace-123`.

Transforms use `;transform=...`, such as `;transform=kebab` or `;transform=upper`. A pipe in a reference token introduces an empty-value fallback, not a Twig filter: `{user:fullName|Anonymous}` returns `Anonymous` when the full name is empty. Use the picker to configure the available transforms and their parameters.

Twig conditionals, method calls, custom user properties and filters without a built-in transform need a [custom variable source](/developers/custom-variable-sources). Move that calculation into the source's PHP resolver, register it, then select it in the Variable Picker. Replace expressions manually where no equivalent reference or transform exists.

##### Settings That Still Support Twig

Hidden fields using **Template**, HTML fields with **Allow Twig** enabled and PDF template **Filename Format** retain sandboxed Twig support. Hidden and HTML template availability is controlled in **Settings → Field Types and Restrictions**; see [Field Templates and HTML Purification](#field-templates-and-html-purification). Changing a Hidden field from **Custom Value** to **Template** also changes its submitted-value behaviour: Formie re-resolves the value on the server and ignores posted input. See [Hidden fields](/fields/hidden#custom-value-vs-template) before switching sources.

PDF attachment filenames are separate from uploaded asset filenames: a PDF filename template can still use sandboxed Twig, while a File Upload field's filename format cannot. Your own Twig template files, including form, email and PDF templates, also remain Twig templates.

After updating the affected settings, render and submit the form. Check the saved submission title, messages and redirect, send a test notification, and check any affected integration values. For File Upload fields, upload a test file and verify both its final asset folder and filename.

<span id="missing-and-repeated-fields"></span>

#### Missing Field References

Test references in notifications, redirects and integration mappings. Missing or ambiguous fields now produce diagnostics instead of silently returning an empty value; correct the field reference when this happens.

#### Repeater Field References

For a repeater child outside the current row, select which rows to read in the Variable Picker. Custom tokens need an explicit selector such as `scope=first`, `last`, `index`, `all`, `count` or `rows`; see [Reference Tokens](/developers/reference-tokens#transforms-and-metadata).

<span id="completion-and-runtime-configuration"></span>

### Completion and Setting Overrides

The [completion setting renames](#completion-setting-names) are handled automatically. Review the following only if your site uses external redirects, custom query parameters or setting overrides in Twig or PHP.

<span id="redirect-destinations-and-query-parameters"></span>

#### Redirect Destinations

Relative redirect URLs and URLs on configured Craft sites still work. If completion redirects to another site, add its exact origin to `completionRedirectAllowedOrigins` in `config/formie.php`:

```php
return [
    'completionRedirectAllowedOrigins' => ['https://partner.example.com'],
];
```

Use the destination's scheme, hostname and port, without a path.

#### Redirect Query Parameters

Only five UTM query parameters are forwarded by default. Add other parameters your destination needs to `completionQueryAllowlist`. For example, this adds `affiliate` to the default campaign parameters:

```php
return [
    'completionQueryAllowlist' => ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'affiliate'],
];
```

Set `completionQueryAllowlist` to `[]` to disable query forwarding. See [Completion and Redirects](/templates/completion-and-redirects) for examples.

<span id="runtime-setting-overrides"></span>

#### Overriding Form Settings

Remove attempts to override form or field identities, provider credentials or global integration settings at render time. Configure credentials and global settings on the integration instead. If a custom field needs an additional setting to be overridden, declare it through `runtimeOverridableSettings()`; see [Configuration Lifetime](/templates/overriding-settings#configuration-lifetime).

#### Settings Snapshot Argument

Remove named `updateSnapshot:` arguments from `setSettings()`, `setFieldSettings()` and `setIntegrationSettings()`. If your code passed `false` to keep an override out of the saved configuration, revise that assumption: Formie now retains settings needed to continue the form automatically. Apply overrides before rendering, as shown in [Overriding Settings](/templates/overriding-settings).

#### Prefilled and Empty Values

An explicitly empty posted value now stays empty, including Hidden fields. Query input is captured when a new form instance starts. Test prefilled values and resumed forms if your code previously relied on restoring defaults after a value was cleared or rereading query parameters on later pages.

### Conditions and Validation

Update conditions or custom validation code that depends on these changed behaviours:

- Text compares alphabetically (`10` comes before `2`); Number fields compare numerically.
- Option comparisons match complete values, not parts of an option.
- Invalid conditions block actions. Correct unknown rules and dependency cycles.
- Date/time rules need complete values and a timezone offset for ambiguous local times.
- Hidden and disabled values are cleared before validation, including nested and repeater values.

Test conditional visibility, required fields and page navigation.

#### Custom AJAX Validation Responses

Validation errors are plain text. Custom AJAX clients should inspect validation results, not rely on HTTP status alone: the Formie 3 transport can still return HTTP 200 for errors.

See [Conditions](/developers/conditions) for condition evaluation and [Validation](/developers/validation) for request and validation details.

### Scoping In-Progress Submissions

If you used `sessionKey` to keep unfinished submissions separate when reusing a form across entries, replace it with `form.setDraftContext()`. Formie 4 still accepts the `sessionKey` render option, but it does not scope the current progress lookup. `form.applyRenderOptions()` is no longer available. Compatibility mode does not restore this behaviour.

Formie 4’s default draft context separates a visitor’s progress by form, site and URL path. Query parameters are not included: `/booking?trip=3333` and `/booking?trip=4444` share a default context. Use the entry ID as an explicit context when those URLs represent separate bookings.

In your Twig template, set the context immediately after fetching the form, before calling `populateFormValues()` or rendering. Population can load an existing submission, so setting the context afterwards is too late. These examples assume `target` is an entry already loaded by the template and the `booking` form has a Single-Line Text field named `tripTitle`:

::: code-group
```twig [Formie 3]
{% set form = craft.formie.forms.handle('booking').one() %}
{% set renderOptions = {
    sessionKey: 'booking_' ~ target.id,
} %}

{% do form.applyRenderOptions(renderOptions) %}
{% do craft.formie.populateFormValues(form, {
    tripTitle: target.title,
}, true) %}

{{ craft.formie.renderForm(form, renderOptions) }}
```

```twig [Formie 4]
{% set form = craft.formie.forms.handle('booking').one() %}

{% do form.setDraftContext('booking_' ~ target.id) %}
{% do craft.formie.populateFormValues(form, {
    tripTitle: target.title,
}, true) %}

{{ craft.formie.renderForm(form) }}
```
:::

Remove `sessionKey` from your render options. Continue passing other options, such as `themeConfig`, to `renderForm()` as usual. Use a stable context for each entry; no manual encoding is needed. The final `true` still forces the populated value across pages and resumed submissions; omit it for an editable starting value. Changing populated values alone does not isolate drafts.

#### Automatic Restoration

The form builder’s **Behaviour → Advanced → Restore In-Progress Submissions Automatically** setting (`automaticSubmissionState`) is enabled by default. Keep it enabled to resume the visitor’s unfinished submission for the selected draft context. Disable it when each normal revisit should start fresh. Disabling restoration still allows multi-page progression during the current attempt, saves incomplete submissions, and permits explicit resume links.

With restoration enabled, test two entries using the same form: enter different answers and advance a page for each, then revisit both and confirm that each restores its own answers and page. With restoration disabled, confirm a normal revisit starts fresh and an explicit resume link still restores the saved draft. See [Draft Contexts](/templates/render-options#draft-contexts) and [Save and Continue Later](/forms/save-continue-later) for the current behaviour.

### Continuity and Upload Upgrade Actions

Standard Formie rendering supplies the current tokens and version inputs automatically. The changes below apply to overridden templates, custom submission clients and existing edit links. Rebuild custom client assets alongside the PHP upgrade.

#### Overridden Hidden Inputs

If you copied or overrode `form/hidden-inputs.html`, update it from the Formie 4 template. Keep its `expectedVersion`, upload credentials and `formStartedAt` inputs, and keep Formie's JavaScript enabled so it populates the submission guard inputs.

#### Custom Submission Requests

When continuing or editing a submission, send the current version returned by Formie so an older request cannot overwrite newer changes:

- **Custom HTML/AJAX requests:** include the issued hidden inputs and `expectedVersion`. A new submission starts at version `0`.
- **Custom client-rendered requests:** send the issued session tokens and current `version`.
- **Administrative GraphQL edits:** read the record's `stateVersion` and send it as `expectedVersion`.

Keep the issued tokens alongside the version; logging in does not bypass visitor submission checks. See [Submission Workflow](/developers/submission-workflow#progress-grants-and-uploads) and [GraphQL Submission Examples](/graphql/create-submissions) for request formats.

#### Existing Edit Links

Previously generated edit tokens must be replaced. Check ownership before calling `form.setSubmission(submission)`, then render the authorised edit form again to issue a fresh token.

#### Uploads and Asset IDs

Custom clients can no longer attach an arbitrary asset by posting its ID. Upload the file first and send the returned `{ uploadUid, attachToken }`, or reference an asset already owned by the authorised submission and field.

Legacy asset IDs remain accepted when Formie can verify them against the current browser's staged upload or the submission's existing field value. See [GraphQL File Uploads](/graphql/create-submissions#file-upload-fields) for an example and [upload request contracts](/developers/submission-workflow#progress-grants-and-uploads) for REST clients.

Test page changes, draft resume in another browser, completed-submission editing and uploads. When two clients submit changes against the same version, only the first should succeed.

### Payment and Subscription Boundary

#### Preparing a Payment Site

Finish active checkout sessions before deployment. Afterwards, regenerate payment and subscription cancellation links; cancellation links must show confirmation on GET and cancel through a CSRF-protected POST. Keep the Formie security key needed to read historical delivery and webhook evidence.

Check the providers your site uses:

- **GoCardless:** configure the **Webhook Secret Key** and enable billing request, payment and subscription events using [GoCardless Setup](/integrations/payments/gocardless#step-3-configure-webhooks). Unsigned webhooks are rejected.
- **Stripe:** keep the existing signing secret configured.
- **Mollie:** reconcile outstanding Formie 3 payments before cutover. Their old callback URLs lack the payment-specific credentials required by Formie 4.

Existing handle-based webhook URLs remain routable with compatibility mode enabled, subject to provider verification. Updating them to integration-UID URLs can wait until [disabling compatibility mode](#payment-webhooks).

#### Custom Payment Providers

If your provider overrides `processPayment(): bool`, move its payment request code into protected `executePayment()` and return a `PaymentDecision`. This result tells Formie whether payment succeeded, failed, needs customer action or is still pending. Inherit `processPayment()` from the base class. The [PHP Integration example](/developers/custom-integration/payment-integration#php-integration) shows the method signature and result construction.

Update code that requires payment amounts to be PHP floats to accept decimal strings. Use `PaymentMoney` for calculations that must preserve currency precision; see [Money and Outcomes](/developers/custom-integration/payment-integration#money-and-outcomes).

If your provider used generic public callbacks, separate them by purpose: customer return, status lookup, session initialisation or provider challenge. Follow the [Endpoint Contracts](/developers/custom-integration/payment-integration#endpoint-contracts) for the matching routes and credentials.

#### Subscription Records

Stored subscription statuses migrate automatically. Update custom code that compares the old status strings:

Formie 3 | Formie 4
--- | ---
`suspended` | `pastDue` or `paused`, according to provider state.
`cancelling` | `active` with `cancelAt`.
`expired` | `failed` for incomplete setup, otherwise `completed`.

The `hasStarted`, `isSuspended`, `isCanceled` and `isExpired` flags remain readable, but cannot be assigned. Update code that writes those flags to use `status`; custom providers should apply state through the [subscription snapshot API](/developers/custom-integration/payment-integration#subscriptions).

If your code reads the full provider response from `Subscription.subscriptionData`, update it to use the selected identifiers and flags in `providerData`. The compatibility getter no longer returns the full response.

Test a payment, provider return, webhook and cancellation before deployment. Working aliases and provider adapters are listed under [Payment Compatibility](#payment-compatibility).

## Before Disabling Compatibility Mode

<span id="compatibility-mode"></span>

The changes in this section can wait until after the initial upgrade. Their Formie 3 behaviour requires `compatibilityMode`; complete the applicable updates before turning it off.

`compatibilityMode` is enabled by default. Leave it enabled while updating legacy class names, field schemas, PHP and browser event listeners. Use this section to identify the usages that depend on the switch, then test with it disabled on staging. Deprecation logs help locate old code, but not every warning is a reason to keep compatibility mode on.

```php
// config/formie.php
return [
    'compatibilityMode' => false,
];
```

Compatibility mode also enables the [browser event bridge](#front-end-javascript-events) automatically on rendered forms. Purge cached form HTML after changing the setting. Once these updates are complete, the [Deprecated Changes](#deprecated-changes) are optional cleanup and do not require compatibility mode.

### Front-End JavaScript Events

With `compatibilityMode` enabled (the default), Formie automatically enables supported Formie 3 events on rendered forms, including HTML loaded through REST or GraphQL. Existing listeners do not need a custom JavaScript bundle or manual mounting. Update to the namespaced events before disabling compatibility mode.

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

Some mappings are approximate because the submission flow has changed. Use the event that matches the point in the form lifecycle you need.

These validator event names also remain available while compatibility mode is enabled:

Formie 3 | Formie 4
--- | ---
`formieValidatorInitialized` | `formie:validator:ready`
`formieValidatorDestroyed` | `formie:validator:destroy`
`formieValidatorShowError` | `formie:validator:show-error`
`formieValidatorClearError` | `formie:validator:clear-error`

If you mount Formie yourself, the browser inherits the setting from the rendered form. An explicit `compatibility` mount option overrides it. For standalone HTML without Formie's rendered attributes, use `compatibility: true` or `data-formie-compatibility="true"`. See [Frontend Assets](/frontend/frontend-assets) for custom setups.

### Other PHP Class Names

Replace these older class names before disabling compatibility mode:

Formie 3 | Formie 4
--- | ---
`verbb\formie\base\FormField` | `verbb\formie\base\Field`
`verbb\formie\base\FormFieldInterface` | `verbb\formie\base\FieldInterface`
`verbb\formie\services\PredefinedOptions` | `verbb\formie\services\OptionSources`
`verbb\formie\services\Phone` | `verbb\formie\services\Countries`
`verbb\formie\options\Countries` and other predefined option classes directly in `options` | The matching class in `verbb\formie\options\predefined`

Register predefined-option event listeners on `OptionSources` instead of `PredefinedOptions`. Access the service through `getOptionSources()` instead of the old `predefinedOptions` component. The deprecated `getPredefinedOptions()` method remains callable with compatibility mode off.

### Submission and Form Statuses

Formie 3 submission statuses are now named **Submission Statuses** to distinguish them from the new **Form Statuses**. The old PHP class names and handlers registered on the old service class work while `compatibilityMode` is enabled.

Formie 3 | Formie 4
--- | ---
`verbb\formie\services\Statuses` | `verbb\formie\services\SubmissionStatuses`
`verbb\formie\models\Status` | `verbb\formie\models\SubmissionStatus`
`verbb\formie\records\Status` | `verbb\formie\records\SubmissionStatus`
`verbb\formie\events\StatusEvent` | `verbb\formie\events\SubmissionStatusEvent`
`verbb\formie\controllers\StatusesController` | `verbb\formie\controllers\SubmissionStatusesController`

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

The [status getter and constant renames](#submission-status-methods) can wait until later.

### GraphQL Page Settings

These page-setting names remain available with `compatibilityMode` enabled. Update queries before disabling it.

Formie 3 | Formie 4
--- | ---
`enableJsEvents` | `enableClientEvents`
`jsGtmEventOptions` | `clientEventFields`

### Field Schema Methods

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

If you built schema arrays manually, update FormKit-style nodes such as `$formkit` to the current `$field` format.

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

See [UI Schemas](/developers/ui-schemas) for the current schema syntax. The remaining [field authoring helpers](#field-authoring-helpers) and [value method renames](#field-values) can be updated later, with compatibility mode off.

### Theme Config

With compatibility mode enabled, a public `defineHtmlTag()` override can still supply a tag when the new `defineSlotTag()` implementation returns `null`. Update these remaining custom tags to `defineSlotTag()` before disabling compatibility mode; see [Custom Field Theme Config](/developers/custom-field/overview#theme-config) for the implementation.

This fallback does not replace tags already supplied by a base field or restore `parent::defineHtmlTag()`. The [tag event renames](#field-and-form-slot-tag-events) can wait until later.

### PHP Events

Update listeners for the events that moved to these services:

#### Notifications

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

#### Integrations

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

After updating these event listeners, the [submission dispatch helpers](#custom-integration-dispatch) and [field/form tag events](#field-and-form-slot-tag-events) can be updated later; both still work with compatibility mode off.

### Validation Message Translations

With compatibility mode enabled, Formie falls back to supported Formie 3 validation-message keys in `translations/*/formie.php`. Update those keys before disabling the setting; otherwise custom translations can fall back to the default copy.

This fallback only covers the supported plugin validation-message keys below.

#### Front-End Validation Placeholders

Formie-owned validation messages now use `{label}` for the field label placeholder instead of `{attribute}`.

Before disabling compatibility mode, update overrides that target the old `{attribute}` source keys and use `{label}` in translated values. Common source-key changes are:

Formie 3 | Formie 4
--- | ---
`{attribute} cannot be blank.` | `{label} cannot be blank.`
`{attribute} is not a valid email address.` | `{label} is not a valid email address.`
`{attribute} is not a valid URL.` | `{label} is not a valid URL.`
`{attribute} is not a valid number.` | `{label} is not a valid number.`
`{attribute} is not a valid format.` | `{label} is not a valid format.`
`{attribute} must match {value}.` | `{label} must match {value}.`
`{attribute} must be no less than {min}.` | `{label} must be no less than {min}.`
`{attribute} must be no greater than {max}.` | `{label} must be no greater than {max}.`
`{attribute} has an invalid value.` | `{label} has an invalid value.`
`{attribute} must select no less than {min}.` | `{label} should contain at least {min, number} {min, plural, one{option} other{options}}.`
`{attribute} must select no greater than {max}.` | `{label} should contain at most {max, number} {max, plural, one{option} other{options}}.`

If you overrode `{attribute} must be between {min} and {max}.`, provide separate translations for the minimum and maximum number messages above. Compatibility mode uses the old combined translation for both until you replace it.

These strings are included in Formie’s browser translation seed via `Rendering::getBrowserJsTranslations()`. If you append custom strings through the [`modifyBrowserJsTranslations`](/developers/events/form-events#the-modifybrowserjstranslations-event) event, use the new `{label}` placeholders in both the source key and your translated value.

Custom per-field validation overrides in the form builder now live under **Validation** as `validationMessages.{key}` (for example `validationMessages.required` and `validationMessages.unique`). Legacy field `errorMessage` values are migrated to `validationMessages.required` automatically.

If a saved override still contains `{name}` or `{attribute}`, Formie still maps those to `{label}` automatically, but new overrides should use `{label}` directly.

#### Server-Side Unique-Value Messages

The default unique-value validation message source key is now:

```php
'“{label}” must be unique.'
```

If you previously overrode a field-specific unique message via translation files alone, consider using the **Unique Error Message** field on the Validation tab instead (`validationMessages.unique`), which supports `{label}` and other allowed placeholders without requiring a global translation override.

### Payment Webhooks

Existing `processWebhook()` provider overrides and handle-based webhook URLs require compatibility mode. Update them before disabling it.

When updating a `processWebhook()` override, split it into `verifyWebhook()` to authenticate the request and `handleWebhook()` to process the verified event. The old method runs synchronously through the compatibility adapter while compatibility mode is enabled. See [Webhook Evidence](/developers/custom-integration/payment-integration#webhook-evidence) for the implementation contract.

Existing `process-webhook?handle=…` URLs remain routable while `compatibilityMode` is enabled; provider authentication still applies. Change registered endpoints to the integration-UID URL shown by Formie before disabling compatibility mode.

The webhook adapter does not provide the durable receipt handling of the new API. See [Payment Integration](/developers/custom-integration/payment-integration) when updating it. Other [payment method and property renames](#payment-compatibility), including the cancellation adapter, can wait until later.

## Deprecated Changes

<span id="replacement-reference"></span>

These aliases and automatic conversions work even with `compatibilityMode` disabled. They do not block the initial upgrade or prevent you from turning compatibility mode off. Use the current names when updating custom code and templates.

### Integration Config

Provider metadata (lists, fields, pipelines and channels) is now called `IntegrationConfig`, separate from the settings saved on each form.

Formie 3 | Formie 4
--- | ---
`IntegrationFormSettings` | `IntegrationConfig`
`fetchFormSettings()` | `fetchConfig()`
`getFormSettings()` | `getConfig()`
`getFormSettings(false)` | `refreshConfig()`
`getFormSettingValue($key)` | `getConfigValue($key)`
`EVENT_BEFORE_FETCH_FORM_SETTINGS` | `EVENT_BEFORE_FETCH_CONFIG`
`EVENT_AFTER_FETCH_FORM_SETTINGS` | `EVENT_AFTER_FETCH_CONFIG`
`IntegrationFormSettingsEvent` / `$event->settings` | `IntegrationConfigEvent` / `$event->config`

When updating a provider, return `IntegrationConfig` from `fetchConfig()` and implement `supportsConfigRefresh()` if it supports manual refresh. See [Custom Integrations](/developers/custom-integration/overview) for the current API.

### Integration Method Names

Update method calls using these replacements:

Formie 3 | Formie 4
--- | ---
`populateQueueJobContext()` | `recordDeliveryRequest()`
`getTransaction()` | `refreshPayment()`
`getRefreshJsVariables()` | `getRefreshData()`
Stripe `getReturnUrl($submission)` | `createPaymentReturnUrl($submission)`
`OAuthRequest()` | `request()`

When updating a custom provider's **overrides**, use the implementation hooks:

- Replace `populateQueueJobContext()` customisation with `recordDeliveryRequestEvidence()` to supply prepared request evidence; see [Results and Safe Retries](/developers/custom-integration/overview#results-and-safe-retries).
- Replace `getTransaction()` customisation with `refreshPaymentState()` to fetch and save verified provider state; see [Refresh and Builder Hooks](/developers/custom-integration/payment-integration#refresh-and-builder-hooks). Existing `getTransactionStatus()` calls retain provider-specific handling; use `refreshPayment()` when new code needs to refresh a payment.
- Replace `getRefreshJsVariables()` customisation with `defineRefreshData()` to return refreshed captcha data; see [Captcha Browser Behaviour](/developers/custom-integration/captcha-integration#browser-behaviour).

#### Integration Service Methods

Update service calls and overrides together. The deprecated entry points remain callable, and canonical calls honour older overrides, including parent calls. A canonical override declared on the same or a more-derived class takes precedence. Do not override both spellings for the same operation.

Previous method | Current method
--- | ---
`getFormIntegrationByHandle()` | `getBuilderIntegrationByHandle()`
`getAllIntegrationsForForm()` | `getGroupedBuilderIntegrations()`
`getIntegrationSummariesForForm()` | `getGroupedBuilderIntegrationSummaries()`
`getFormIntegrationDefinitionByHandle()` | `resolveIntegrationDefinitionByHandle()`
`getFormIntegrationsForForm($form)` | `getEnabledFormIntegrations($form)`
`getAllEnabledIntegrationsForForm($form)` | `getEnabledIntegrationRuntimes($form)`
`populateIntegrationFromFormSettings()` | `createIntegrationRuntime()`
`createIntegrationConfig()` | `createProjectConfig()`
Dispatcher `loadContext()` / `saveContext()` | `loadRunResults()` / `saveRunResults()`
Triggers `dispatchManualIntegration()` | `runManualIntegration()`

This table includes Formie 3 service methods and renamed methods from the Formie 4 beta. Builder choices are different from a form's `FormIntegration` bindings. Runtime creation returns a clone, and `createProjectConfig()` serialises configured connection settings rather than returning fetched `IntegrationConfig` metadata. See [Connections, Bindings and Runtimes](/developers/custom-integration/overview#connections-bindings-and-runtimes).

Provider metadata overrides of `fetchFormSettings()` may continue to call their parent; acquisition returns the compatible `IntegrationFormSettings` model. This also applies to subclasses of native providers. Refresh events run once around acquisition, including for element integrations. When migrating the override, implement `fetchConfig()` and return `IntegrationConfig`.

Existing `sendPayload(): bool` overrides may still return or check their parent result; the deprecated facade preserves boolean behavior while the canonical execution path retains its detailed result. Metadata compatibility also includes `getFormSettings()` and `getFormSettingValue()` read overrides, not only acquisition.

Keep zero-argument `populateContext()` and single-argument `enforceOptInField($submission)` signatures in old providers. Call `populateSubmissionContext($submission)` to prepare submission metadata through an existing context override. Keep the string parameter and its original name in `getMappedFieldValue(string $mappedFieldValue, ...)`; use `resolveMappedFieldValue()` for structured reference slots. Older overrides participate in both single-value and bulk mappings.

For provider implementations, migrate `sendPayload()` overrides to `executePayload()`. For application callers, use the [execution entry point matching the action](/developers/custom-integration/overview#calling-integrations); replacing a direct send with `execute()` does not apply the runner's complete dispatch policy. Web calls to the manual trigger service require permission to save submissions for the submission's form, including through the deprecated method. Custom controllers remain responsible for POST and CSRF requirements.

Custom providers must extend `Integration` or a category base. Registration, connection creation and form binding reject classes that only implement `IntegrationInterface` or `DispatchableIntegrationInterface`, because the execution lifecycle requires the base implementation.

#### Payment Amount Helpers

The base `getAmount()` adapter honours `getPaymentAmount()` overrides and preserves older overrides that call their parent. Stripe and Opayo distinguish API units from displayed currency amounts. Use `getProviderAmount()` for provider API requests and `getPaymentAmount()` for major currency units when replacing `getAmount()` calls.

#### Integration Class Names

Replace `className()` with `getShortClassName()` for a short class name, or use `::class` for a fully qualified class name. Replace `kebabClassName()` with `getClassHandle()` for an implementation handle. See [Method Contracts](/developers/custom-integration/overview#method-contracts-and-compatibility) for signatures.

### Render Options

The `sessionKey` replacement is a [required draft-context update](#scoping-in-progress-submissions). The asset option aliases below remain supported.

Update render options to these names:

Formie 3 | Formie 4
--- | ---
`renderCss` | `includeCss`
`renderJs` | `includeJs`
`outputCssLayout` / `outputCssTheme` | `outputCss`
`outputJsBase` / `outputJsTheme` | `outputJs`

Use `outputCssLocation` and `outputJsLocation` to choose `page-header`, `page-footer`, `inside-form`, or `manual`. See [Render Options](/templates/render-options) for examples.

### Form Templates

Update asset flags in custom Form Templates:

Formie 3 | Formie 4
--- | ---
`outputCssLayout` / `outputCssTheme` | `outputCss`
`outputJsBase` / `outputJsTheme` | `outputJs`

Review [Template Overrides](/theming/template-overrides) when updating custom templates.

### Text Limits

Update custom text-limit configuration for Single-Line Text and Multi-Line Text fields:

Formie 3 | Formie 4
--- | ---
`limitType` | `maxType`
`limitAmount` | `max`

### Sub-Field Label Position

Use the updated casing in custom field configuration:

Formie 3 | Formie 4
--- | ---
`subfieldLabelPosition` | `subFieldLabelPosition`

### Instruction Positions

Update custom configuration to the current instruction-position classes:

Formie 3 | Formie 4
--- | ---
`verbb\formie\positions\FieldsetStart` | `verbb\formie\positions\AboveInput`
`verbb\formie\positions\FieldsetEnd` | `verbb\formie\positions\BelowInput`

### Form Rendering and Assets

Replace asset helpers in templates as shown below. The same method names apply on the PHP Rendering service.

Formie 3 | Formie 4
--- | ---
`craft.formie.renderFormAssets(form)` | `craft.formie.formAssets(form)`
`craft.formie.renderFormCss(form)` | `craft.formie.formAssets(form, { includeJs: false })`
`craft.formie.renderFormJs(form)` | `craft.formie.formAssets(form, { includeCss: false })`
`craft.formie.renderCss()` | `craft.formie.browserAssets({ includeJs: false })`
`craft.formie.renderJs()` | `craft.formie.browserAssets({ includeCss: false })`

See [Frontend Assets](/frontend/frontend-assets) for asset loading examples.

### Form Render IDs

Formie 3 | Formie 4
--- | ---
`form.getFormId()` | `form.getRenderId()`
`form.setFormId(id)` | `form.setRenderId(id)`

See [Rendering Forms](/templates/rendering-forms) when rendering multiple instances of a form.

### Submission Values

Formie 3 | Formie 4
--- | ---
`submission.getValueAsString(handle)` | `submission.getFieldValueAsString(handle)`
`submission.getValueAsJson(handle)` | `submission.getFieldValueAsData(handle)`
`submission.getValueForExport(handle)` | `submission.getFieldValueForExport(handle)`
`submission.getValueForSummary(handle)` | `submission.getFieldValueForSummary(handle)`
`submission.getValuesAsJson()` | `submission.getValuesAsData()`

These mappings also apply in PHP. See [Submission Content](/developers/submission-content) for the available output formats.

### Field Templates

Replace `getFrontEndInputTemplatePath()` overrides with `getInputTemplatePath()`:

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
    $variables = parent::getInputTemplateVariables($form, $value);
    $variables['placeholder'] = $this->placeholder;

    return $variables;
}
```

### Field Setting Names

Field configuration accepts the names below and saves their canonical equivalents. If both names are supplied, the canonical key wins. The same mappings apply to subclasses, `setAttributes()` and PHP property access; legacy PHP property access is deprecated. Existing GraphQL setting names remain available as deprecated aliases where those settings are exposed.

| Formie 3 | Formie 4 |
| --- | --- |
| Element fields `limitOptions` | `optionLimit` |
| File Upload `sizeLimit` | `maxFileSize` |
| File Upload `sizeMinLimit` | `minFileSize` |
| File Upload `limitFiles` | `maxFiles` |
| Phone / Address `countryAllowed` | `allowedCountryCodes` |
| Repeater `addLabel` | `addRowLabel` |

The `limitOptions` rename applies only to Element fields; do not rename the selection-count toggle on choice fields.

When updating a subclass, rename its property declaration as well as reads and writes. Existing public declarations such as `public ?string $sizeLimit = '1'` remain synchronized with the new property, including defaults and array mutations. A canonical declaration on the same class or a more-derived class takes precedence over the legacy default; explicit configuration still takes precedence over defaults. `getSettings()` emits canonical names, and cloned fields keep their own settings values.

Phone and Address retain `countryAllowed` as a deprecated public array linked to `allowedCountryCodes`. Existing appends, element removal and functions that take an array by reference continue to work on native fields and subclasses; clones keep independent values. Use `allowedCountryCodes` in new code.

Saved Repeater translations using `addLabel` are applied as `addRowLabel`, including site-switch data and builder saves. An explicit `addRowLabel` translation wins when both names are present. Reading a translation does not rewrite its stored record.

#### Recipients Display Settings

Recipients accepts its old `multi` / `multiple` configuration during upgrading, but cardinality is determined by `displayType`. It is not emitted as a separate saved setting. Read `displayType` to determine the field's presentation.

### Field Authoring Helpers

#### Field Class Names

Replace `className()` with `getShortClassName()` for a short class name. Use `::class` for the fully qualified field class.

Existing `className()` overrides remain supported, including inherited overrides and calls to `parent::className()`. Their results still feed `kebabClassName()`, `lowerClassName()`, default input/reference-block template paths and the default Theme Config key. For example, an override returning `SharedWidget` keeps the default template path `fields/shared-widget`.

When updating an override, rename it to `getShortClassName()` and update its parent call too. A canonical override on the same class or a more-derived class takes precedence when Formie resolves the name. The inherited deprecated `className()` method forwards to the canonical method; a direct call to your own `className()` override still runs your implementation according to PHP inheritance.

#### Browser Condition Data

Use `getBrowserConditionsJson()` instead of `getConditionsJson()`.

#### Field Template Helpers

Replace `getFrontEndInputTemplatePath()` and `getEmailTemplatePath()` overrides with `getInputTemplatePath()` and `getReferenceBlockTemplatePath()`. Existing overrides also work on native field subclasses, including subfields, and can call their parent implementation. A canonical override on the same class or a more-derived class takes precedence.

#### Field Reference-Block Rendering

Update these public rendering overrides and calls together:

| Formie 3 | Formie 4 |
| --- | --- |
| `getEmailHtml()` | `getReferenceBlockHtml()` |
| `getEmailOptions()` | `getReferenceBlockOptions()` |
| `hasEmailLabel()` | `hasReferenceBlockLabel()` |
| `hasEmailPlaceholder()` | `hasReferenceBlockPlaceholder()` |

The HTML and options methods retain their `(Submission $submission, Notification $notification, mixed $value, array $renderOptions = [])` arguments. Existing overrides remain supported on native field subclasses and can call their parent implementation. A canonical override on the same class or a more-derived class takes precedence. This includes fields with their own rendering behaviour, such as Section, Content and HTML, and the label/placeholder defaults on Agree and cosmetic fields.

#### Browser Module Configuration

`getConfigJson()` only exposes legacy module declarations for old templates; use registered browser modules and the rendering API for new templates.

#### Field Value Type Declarations

Declare value types with protected `defineValueType()` rather than `phpType()` or `defineValueClass()`.

#### Recipients Value Helpers

Replace Recipients `getFakeValue()` and `getRealValue()` calls with `normalizeFieldValue()` and the normalised value's accessors. Use `serializeValueForClientInput()` when you need browser tokens.

### Field Values

Formie 3 | Formie 4
--- | ---
`defineValueAsJson()` | `defineValueAsData()`
`getValueAsJson()` | `getValueAsData()`
`defineValueForVariable()` / `defineValueForVariableRaw()` | `defineValueForReference()`
`getValueForVariable()` / `getValueForVariableRaw()` | `getValueForReference()`
`defineValueForEmail()` | `defineValueForReferenceBlock()`
`getValueForEmail()` | `getValueForReferenceBlock()`
`EVENT_MODIFY_VALUE_AS_JSON` | `EVENT_MODIFY_VALUE_AS_DATA`
`EVENT_MODIFY_VALUE_FOR_EMAIL` | `EVENT_MODIFY_VALUE_FOR_REFERENCE_BLOCK`

Choose the new hook according to where the value appears:

- **Reference text:** implement `defineValueForReference($value, $element = null)` for values inserted into text, such as subject lines. Old variable overrides are not called outside notification contexts, so implement this hook if the same custom output is needed there.
- **Notification blocks:** implement `defineValueForReferenceBlock($value, $notification, $element = null)` and a [reference-block template](#field-reference-block-templates) for richer field output. Callers needing the rendered HTML use `getReferenceBlockHtml($submission, $notification, $value)`.

See [Custom Field Values](/developers/custom-field/overview#normalised-values-and-projections) for the value hooks and their arguments. When a projection reuses the data representation, protected `resolveValueAsData($value, $element = null)` preserves old `defineValueAsJson()` overrides without firing an additional data event. Dedicated projections, such as text conditions, continue to use their own shape.

For a `serializeValue()` override, move the storage conversion into `defineValueForDb()` when updating your field. Code requesting the stored representation calls `serializeValueForDb()`.

### Additional Deprecated Names

Formie 3 | Formie 4
--- | ---
GraphQL `emailValue` | `emailFieldSummaryValue`
GraphQL `includeInEmail` | `includeInEmailFieldSummaries`
`includeInEmailField()` | `includeInEmailFieldSummariesField()`
`emailNotificationValue()` | `emailFieldSummaryValue()`

For field/form `modifyHtmlTag` event replacements, see [Field and Form Slot Tag Events](#field-and-form-slot-tag-events).

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

### Field Reference-Block Templates

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

### Completion Setting Names

Use these names when updating completion settings:

| Formie 3 | Formie 4 |
| --- | --- |
| `prePopulate` | `prefillQueryParam` |
| Hidden `defaultOption` | `valueSource` |
| `submitActionTab` | `redirectTarget` |
| `submitActionUrl` | `redirectUrl` |
| `submitActionEntry` | `redirectEntry` |
| `submitActionFormHide` | `hideFormAfterSubmit` |
| `submitActionMessage` | `successMessage` |
| `submitActionMessageTimeout` | `successMessageTimeout` |
| `submitActionMessagePosition` | `successMessagePosition` |

When replacing `submitAction: entry` or `submitAction: url`, set `completionBehavior: redirect` and set `completionRedirectSource` to `entry` or `url` respectively.

### Populating Submission Values

Review calls to `craft.formie.populateFormValues()` with `force = true`: forced population applies across pages and resume.

### Settings Method Arguments

Remove the obsolete trailing positional `true` argument from `setSettings()`, `setFieldSettings()` and `setIntegrationSettings()` when updating code; PHP ignores that extra argument.

### Payment Compatibility

Replace direct `Submissions::processPayments()` calls with the submission workflow when processing form submissions.

When updating `getAmount()` and `getPaymentAmount()` calculations, use decimal strings to avoid precision loss.

Formie 3 | Formie 4
--- | ---
`verbb\formie\models\Plan` | `SubscriptionPlan`
`getRedirectUri()` | `getWebhookUrl()`
`nextPaymentDate` | `nextPaymentAt`
`dateSuspended` | `pausedAt`
`dateCanceled` | `cancelledAt`
`dateExpired` | `endedAt`

Replace `cancelSubscription()` provider overrides with `cancelSubscriptionSnapshot()` and return a `SubscriptionSnapshot` describing the provider's resulting state; see [Subscriptions](/developers/custom-integration/payment-integration#subscriptions).

`getPaymentByReference()` and `getSubscriptionByReference()` accept an integration ID to scope lookups. Pass it in updated providers. Payment identifiers alone do not authorise public requests.

### Browser Module PHP Declarations

Existing `getFrontEndJsModules()` declarations are adapted even with compatibility mode off. Replace them with `defineBrowserModules()` when updating your field:

::: code-group
```php [Formie 3]
public function getFrontEndJsModules(): ?array
{
    return [
        'src' => '/assets/my-rating.js',
        'module' => 'MyRating',
        'settings' => ['max' => 10],
    ];
}
```

```php [Formie 4]
use verbb\formie\models\BrowserModule;

protected function defineBrowserModules(): array
{
    return [new BrowserModule([
        'moduleId' => 'my-site:rating',
        'config' => ['max' => 10],
    ])];
}
```
:::

When making this change, update the JavaScript registration's `moduleId` from `legacy:my-rating` to `my-site:rating` too. Both declarations must use the same ID. Choose your own prefix, such as your plugin or site name, before the colon.

See [Build a Custom Module](https://docs.verbb.io/formie/browser/modules/build-a-custom-module) for configuration and supported form types.

### Submission Status Methods

Formie 3 | Formie 4
--- | ---
`Formie::$plugin->getStatuses()` | `Formie::$plugin->getSubmissionStatuses()`
`craft.formie.getStatuses()` | `craft.formie.getSubmissionStatuses()`
`SubmissionStatuses::CONFIG_STATUSES_KEY` | `SubmissionStatuses::CONFIG_SUBMISSION_STATUSES_KEY` (`formie.statuses`)
`Table::FORMIE_STATUSES` | `Table::FORMIE_SUBMISSION_STATUSES`

### Custom Integration Dispatch

Notification sends move to the Notifications service:

Formie 3 | Formie 4
--- | ---
`getSubmissions()->sendNotifications($submission)` | `getNotifications()->sendNotifications($submission)`
`getSubmissions()->sendNotification($notification, $submission)` | `getNotifications()->sendNotification($notification, $submission)`
`getSubmissions()->sendNotificationEmail($notification, $submission)` | `getNotifications()->sendNotificationEmail($notification, $submission)`

For `triggerIntegrations()` and `sendIntegrationPayload()`, choose the replacement according to why the integration is running:

- **Automatic delivery after a submission change:** use `getIntegrationTriggers()->dispatch()` with an `IntegrationTriggerRequest`, as shown below. Workflow extensions can use `dispatchFromWorkflow()`; see [Integration Dispatch and Policies](/guides/integrations/integration-dispatch-and-policies).
- **A deliberate run of one integration:** use `runManualIntegration($integration, $submission)`.
The provider-send service is internal runner plumbing. For application code, choose automatic dispatch or a manual trigger; provider implementations send their HTTP payload through `deliverPayload()` inside `executePayload()`. The deprecated `getSubmissions()->sendIntegrationPayload()` entry point retains its boolean/`IntegrationResponse` return shape, including acknowledged event cancellation.

Do not trigger integrations from a general `Submission::EVENT_AFTER_SAVE` listener: that also catches saves outside the visitor workflow and can bypass re-run rules or duplicate delivery. To customise an integration before it runs, use `Integrations::EVENT_BEFORE_TRIGGER_INTEGRATION` instead. Status-change notifications are already handled by `NotificationTriggers` when their `{submission:status}` condition matches.

The examples below show separate alternatives; choose the call your custom code needs.

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
use verbb\formie\enums\SubmissionOperation;
use verbb\formie\helpers\IntegrationTriggerEvents;
use verbb\formie\models\IntegrationTriggerRequest;

$submission = Submission::find()->id(123)->one();

// Automatic dispatch (respects re-run policies and orchestration)
Formie::$plugin->getIntegrationTriggers()->dispatch(new IntegrationTriggerRequest([
    'submission' => $submission,
    'operation' => SubmissionOperation::REVISE,
    'triggerEvent' => IntegrationTriggerEvents::CP_SAVE,
]));

// Operator-initiated single integration run
Formie::$plugin->getIntegrationTriggers()->runManualIntegration($integration, $submission);

```
:::

Learn more in [Submission Workflow](/developers/submission-workflow) and [Integration Events](/developers/events/integration-events).

### Field and Form Slot Tag Events

If your project listens for field or form tag-mutation events, switch from the old `htmlTag` names to the canonical `slotTag` names.

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

## Behaviour Changes

### Field Templates and HTML Purification

Upgrading explicitly enables **Allow Hidden Field Templates**, **Allow Twig in HTML Fields** and **Allow Template Includes in HTML Fields**, and leaves **Require HTML Purification** off. This preserves existing field behavior, including each HTML field’s Purify Content choice. Previously configured values and `config/formie.php` overrides are respected. New installs use the opposite defaults.

Review these controls in **Settings → Field Types and Restrictions** before tightening them. Disabling Hidden templates makes their computed values empty while still ignoring posted input; disabling HTML Twig suppresses output from fields configured for Twig. Stored source is retained, but affected forms must switch away from disabled modes before they can be saved. Disabling includes rejects those Twig calls. Requiring purification can remove markup that existing fields previously allowed. Clear rendered-form caches after changing the policy. PDF filename templates remain unchanged.


Review these operational changes while testing the upgrade. They cover automatic migrations, editor workflows and settings you may need to adjust for your site.

### Changes at a Glance

Stored form settings and content are migrated automatically unless a section above calls for a manual edit. You do not need to recreate forms or resave every submission.

Formie 3 | Formie 4
--- | ---
**Settings → Spam** and **Settings → Captchas** | **Spam Protection**
**Settings → Statuses** | **Settings → Submission Statuses**; form statuses are a separate feature.
Session-based form progress | Database-backed progress and separate links for resuming drafts and editing completed submissions.
Integration field-mapping tokens | Migrated reference values; existing notification and form tokens retain compatibility parsing.

Custom frontends should review [Frontend Assets](/frontend/frontend-assets) when adopting the browser packages.

### Spam Protection

Spam settings and provider credentials migrate automatically into local storage. Review [Local Spam Protection Settings](#local-spam-protection-settings) for the deployment and permission changes.

Built-in captcha configuration is replaced by submission guards:

Formie 3 | Formie 4
--- | ---
**Honeypot** captcha | **Honeypot** submission guard
**JavaScript** captcha | **Minimum submit time** submission guard
**Duplicate** captcha | **Replay protection** submission guard

All three guards are enabled by default, with a three-second minimum submit time. They apply to every form, and standard rendering supplies the required inputs. Review **Formie → Spam Protection → Spam Rules → Submission Guards** after upgrading. Custom references to the [removed captcha types](#removed-legacy-captchas) still need updating.

### Encrypted Submission Fields

You can activate the dedicated Formie field-content key after upgrading; existing encrypted fields use their previous format until you activate the new key.

Before enabling encryption on another field, generate `FORMIE_ENCRYPTION_KEY` with `php craft formie/encryption/generate-key`. The command saves the key to the project’s configured `.env` file; use `--stdout` if another system manages the environment. Store the same key in every environment that must read copied submissions. Deploy and restart all web and queue processes before running `php craft formie/encryption/activate`. Make a verified database backup, then follow the [submission content encryption commands](/developers/console-commands#submission-content-encryption) to convert existing values. See [Content Encryption](/submissions/content-encryption) for key management.

Keep `FORMIE_SECURITY_KEY` and the previous Craft security key available until conversion is complete if the site has used both. You can supply old keys temporarily through `FORMIE_ENCRYPTION_PREVIOUS_KEYS`. Formie preserves and locks any value it cannot decrypt instead of displaying it as blank or overwriting it during a resave.


### Production Settings Permissions

Settings permissions cover tools available with admin changes disabled, including import/export, support, encryption status, synced field usage and viewing scheduled reports. Project configuration, such as form groups, defaults, statuses and templates, requires a Craft administrator in an environment with admin changes enabled.

Upgrade migrations remove the retired configuration grants. Existing blanket settings grants become explicit production-page permissions and separate spam-rule and CAPTCHA management permissions. Existing scheduled-report management grants become **View scheduled reports** and **Send scheduled report test emails**. Direct user assignments, group assignments and their project-config entries are migrated; existing import and export grants remain independent.

### Reports and Submission Export

The submissions index **Export** action has moved to **Formie → Reports**. Update editor permissions and workflows if they rely on it.

Submission export now lives in **[Reports](/reports/reports)**:

- **Formie → Reports** — saved filters, columns, charts, on-demand export (CSV, Excel, JSON, XML, text), and [scheduled email delivery](/reports/scheduled-reports)
- For a one-off file without saving a report definition, run a report scoped to the form(s) you need and choose **Export** on the report run screen

**Permissions** — report access is split from submission management. Under **Settings → Users → {group} → Formie**, assign:

| Permission | Purpose |
| --- | --- |
| **Access reports** | Open **Formie → Reports** and run saved reports |
| **Export submissions** | Export from reports (without full manage access) |
| **Manage reports** | Create, edit, delete reports; includes export |
| **View scheduled reports** | Inspect saved deliveries under **Settings → Scheduled Reports** |
| **Send scheduled report test emails** | Send a saved scheduled report to your own email address |

See [Reports](/reports/reports), [Saved reports and scheduled delivery](/guides/submissions-workflows/saved-reports-and-scheduled-delivery), and [Submissions overview](/submissions/submissions#export-submission-data).

### Static Caches

Static-cache handling has changed. Formie can refresh request-specific tokens for statically cached forms when the `staticCacheRefreshOnLoad` plugin setting is enabled, and it assumes static-cache handling is needed when Blitz is installed and enabled.

```php
// config/formie.php
return [
    'staticCacheRefreshOnLoad' => true,
];
```

You no longer need to include a JavaScript snippet to refresh the tokens.

See [Cached Forms](/frontend/cached-forms) and [Configuration](/get-started/configuration).

Purge cached form HTML on deployment so recipients, Summary fields and editing flows receive current tokens. Custom Summary refresh requests must send the issued token; Summary tokens expire after seven days, so refresh cached forms within that period. Regenerate saved Summary and Signature links by rendering the form or requesting a fresh URL. Multiple web servers must share the database and Formie security key.

### Save and Continue Later

Formie 4 stores form progress in the database so Save & Continue links can resume the same draft across browsers. Automatic page saving remembers the visitor’s browser; it does not create a shareable link. Links for resuming drafts and editing completed submissions are separate and cannot be used interchangeably.

If you customise save buttons, draft handling, or submission retention, review [Save & Continue Later](/forms/save-continue-later).

Related settings include:

Setting | Use
--- | ---
`submissionStateRetentionDays` | How long stored submission state is retained.
`saveResumeTokenTtlDays` | How long resume links remain valid.

Custom clients and edit templates also need the [continuity and upload changes](#continuity-and-upload-upgrade-actions).

### Portable Forms and Synced Fields

Review the import preview and verify shared-field links afterwards. If a stencil's shared definition cannot be resolved, it creates an independent definition. Unknown or unregistered field types retain their settings as Missing Fields; restore and register the owning extension before recovery.

Keep instance IDs/UIDs and handle-based input names; do not replace them with shared definition IDs. See [Shared and Per-Form Settings](/developers/custom-field/overview#shared-and-per-form-settings) when adapting custom field settings or translations.

Replace the deprecated `syncId` PHP/config property with `definitionId` and `isSynced`; portable links use `syncedDefinitionUid`. Custom export tools should preserve `schemaVersion`, `formieVersion` and field references.

### Automation Test Payloads

Move sample sends out of `fetchFormSettings()` or `fetchConfig()` into `sendTestPayload()` when updating the integration, so metadata refresh does not send an unwanted request. The builder's test action now invokes `sendTestPayload()` separately through the integration's normal execution hook.

### Integration Delivery Storage

Existing providers returning `bool` or `IntegrationResponse` still work through a compatibility adapter. Update to `IntegrationResult` when adopting detailed delivery outcomes and safe retries. A legacy `false` result is non-retryable unless the provider supplies more precise request evidence.

After upgrading, inspect **Formie delivery diagnostics** on failed jobs in **Utilities → Queue Manager** and reconcile uncertain outcomes before retrying. See [Custom Integrations](/developers/custom-integration/overview#results-and-safe-retries) for the provider API.

### Integration Scope

Existing integrations remain project integrations after upgrading, so their configuration continues to deploy through Craft’s project config. In the integration list, these connections display **Project** in the **Scope** column.

For new connections, **New Integration** creates an instance-level integration. Choose **New project integration** from the arrow beside the button to continue managing new connections through project config. See [Integrations and Project Integrations](/integrations/overview#integrations-and-project-integrations) for permissions, deployment and credential handling.

### Integration Connection Checks

`checkConnection()` now returns `false` when the connection status is `fail`.

### Payment History

Reconcile legacy payments that migrate to `unknown` because their outcome could not be determined. Contradictory subscription flags also become `unknown`, with the original flags retained in history.

Formie 3 subscription payment rows remain monetary history because they cannot reliably be separated into setup attempts and charges. Stripe automatic collection owns invoice charging; Formie observes the result rather than issuing an additional payment request for `invoice.created`.

Existing `deleteSubscription()` and `deleteSubscriptionById()` calls now archive the subscription and preserve its financial history. Review custom code that depended on physical deletion.

Use the [payment console commands](/developers/console-commands) to inspect and reconcile unresolved outcomes before retrying.

### New and Renamed Settings

Review `config/formie.php` if your project keeps a full config file.

The most upgrade-relevant settings are:

Setting | Use
--- | ---
`compatibilityMode` | Enables Formie 3 compatibility shims, including supported browser events. Defaults to `true`.
`allowLegacySignatureImageUrls` | Keeps unsigned Signature images in previously sent Formie 2/3 emails available for explicitly grandfathered submissions. Defaults to `true`; new emails use signed URLs.
`staticCacheRefreshOnLoad` | Enables token refresh support for static-cache setups that are not auto-detected.
`submissionStateRetentionDays` | Controls retention for stored submission state.
`saveResumeTokenTtlDays` | Controls resume link lifetime.
`setOnlyCurrentPagePayload` | Limits front-end page payload behaviour to the current page.
`anonymousFormBootstrapRateLimit` | Rate limit for anonymous form bootstrap requests.
`anonymousFormRefreshRateLimit` | Rate limit for anonymous token refresh requests.
`anonymousFormRateWindowSeconds` | Rate-limit window used by the anonymous request limits.
`useCssLayers` | Outputs Formie's CSS using CSS layers when enabled.

Remove `enableGatsbyCompatibility` from your config when convenient; Formie ignores this obsolete setting.

See [Configuration](/get-started/configuration) for the current config shape.

## Updating Earlier Formie 4 Extensions

Skip this section when upgrading from stable Formie 3. It applies only to custom code written against earlier Formie 4 betas; deploy updated PHP and npm packages together. The replacements below have no compatibility aliases unless noted.

| Earlier Formie 4 Beta | Current Formie 4 |
|---|---|
| `client\modules` namespace | `browser\modules` |
| `compatibility\client\RefreshTokensCompatibility` | `compatibility\browser\RefreshTokensCompatibility` |
| `ClientSessionService` and `getClientSessionService()` | `services\FormSessions` and `getFormSessions()` |
| `client\BaseClientModel` | `base\FormPayload` |
| `client\ClientMutationRequest` | `models\FormMutationRequest` |
| `client\models\FormSession`, `PageTransitionRequest`, `SessionRefreshRequest` | The corresponding classes in `models` |
| `client\models\SubmitRequest`, `SubmitResult` | `models\FormSubmitRequest`, `FormSubmitResult` |
| `ManagedSubmissionRequest` and `executeManaged()` | `HtmlSubmissionRequest` and `executeHtml()` |
| `getClientFormDefinitionBuilder()->build($form, $context)` | `$form->getClientRenderedDefinition($context)` |
| Custom Craft-field adapter `getClientInput()` | `getClientRenderedInput()` |
| `definePrimaryOptionVariableSourceTypes()` | `definePrimaryOptionReferenceTypes()` |
| `ClientConditionRule`, `ClientConditionSettings`, `ClientValidationRule` npm types | `BrowserConditionRule`, `BrowserConditionSettings`, `BrowserValidationRule` |
| `anonymousClientBootstrapRateLimit`, `anonymousClientRefreshRateLimit`, `anonymousClientRateWindowSeconds` settings | `anonymousFormBootstrapRateLimit`, `anonymousFormRefreshRateLimit`, `anonymousFormRateWindowSeconds` |

### Beta Field Rendering

For fields that implemented `clientRenderedDefinition()` or returned `FieldClientRenderedDefinition`, put the field type in `defineClientRenderedType()` and its input settings in `defineClientRenderedInput()`. Call `getClientRenderedDefinition()` to obtain the assembled payload. The [Custom Field reference](/developers/custom-field/overview) documents these hooks.

Code reading field `runtime.structure` must read `client.children.model` and `client.valueType` instead.

### Beta Reference Types

`definePrimaryOptionReferenceTypes()` must return `ReferenceType` enum cases; renaming the method alone is insufficient.

### Beta Condition and Validation Helpers

Code using the old `ConditionsHelper::getClientFieldReferenceMap()` forwarding method should call `FieldReferenceHelper::getBrowserFieldReferenceMap($form->getFields())` directly.

The CP condition helper uses `CpSubmissionFieldConditions::browserDisplayMode()` and `BROWSER_DISPLAY_HIDE`. Shared PHP condition helpers now use browser terminology: `compileBrowserConditions()`, `getBrowserSubmissionContext()`, `getSubmitButtonBrowserConditions()`, and `toBrowserConditionDefinition()`. Field-reference helpers use `getBrowserFieldReferenceMap()`, `resolveBrowserFieldKey()`, and `normalizeBrowserFieldReference()`. Shared validation helpers use `BrowserAttribute` or `BrowserAttributes` suffixes; text-limit configuration uses `getTextLimitBrowserConfig()`. Client-rendered text-limit and option-limit inputs use `getTextLimitClientRenderedInput()` and `getOptionsLimitClientRenderedInput()`.

### Beta Submission Sessions

`FormSessions::persistPageState($request)` takes only the page-transition request. Remove the unused second boolean argument; visitor page transitions enforce the shared refresh rate limit through the submission guards.

Session submission helpers use `applySessionRequestContext()`, `getRequiredSessionMutationScope()`, and `resolveSessionContinuationSubmission()`.

### Removed Beta Classes

The empty `FieldRequiredValidator` wrapper has been removed; use Yii’s `RequiredValidator`. Removed beta compatibility classes include `ClientCompatibility`, `ClientGuestCsrfTrait`, and the `options\sources` class aliases. Use the canonical `options`, `options\resolvers`, and `options\predefined` classes. Replace `OptionSourceProviderInterface` with `OptionSourceResolverInterface` and the `providers` classes with their corresponding `resolvers` classes.

<span id="integration-connection-settings"></span>

### Beta Integration Secrets

Existing encrypted settings from earlier Formie 4 betas need their original key or replacement values.

Remove calls to `IntegrationSecrets::protect()` and `protectBindings()`. Use `FormIntegrationSetting` to allow a property to be configured per form and `Sensitive` to mark it for redaction.

### Beta Craft-Field Storage

Custom Craft-field adapters must also rename their persistence implementation from `serializeValue()` to `serializeValueForDb()`.

### Beta Subscription Setup Records

Earlier beta subscription setup rows become non-monetary `subscriptionSetup` operations; recurring invoices remain separate `recurringCharge` payments.

### Beta Field Helpers

Beta extension code should replace `isOptionFrontEndDisabled()` with `isOptionDisabled()`, `getOptionSourceSourceOptions()` with `getOptionSourceOptions()`, and protected `conditionDefinition()` with `defineConditions()`. The partial public `collectBrowserModules()` helper is removed; presentation delegates compose the protected declaration, while consumers use `browserModules($context)` for completed declarations.

### Beta Recipients Values

The beta `RecipientsFieldValue` no longer stores `displayType`; use its read-only `value`, `label`, `valid` and `multiple` properties and `all()` / `values()` / `labels()` methods.

### Beta Quiz Settings

Quiz's beta property `fieldType` is now `displayType`, and its `FIELD_TYPE_*` constants are now `DISPLAY_*`. Stored beta configuration is migrated when loaded; the old PHP property and constants are not retained.

### Beta Browser Module Settings

Map adapter configuration helpers use `BrowserSettings` suffixes. Update beta browser-module configurations to use `allowedCountryCodes`, `maxFiles`, `minFileSize` and `maxFileSize`; client-rendered Repeater definitions use `addRowLabel`.

### Working Beta Integration Aliases

These earlier beta methods and their overrides remain supported. Update them when convenient.

Earlier Formie 4 Beta | Current Formie 4
--- | ---
`getIntegrationFieldMappingField()` | `createFieldMappingField()`
`getGraphqlPaymentInputFieldKeys()` | `getGqlPaymentInputFieldKeys()`
`getOptionalGraphqlPaymentInputFieldKeys()` | `getOptionalGqlPaymentInputFieldKeys()`
`getConfigRefreshParams()` | `getOptionSourceRefreshParams($provider)`
