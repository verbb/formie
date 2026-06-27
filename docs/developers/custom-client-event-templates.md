# Custom Client Event Templates

::: tip
For author workflow and a SaaS registration example, see [Custom client event templates](/guides/frontend-headless/custom-client-event-templates).
:::

Register predefined client event templates for the form builder **Tracking** tab. Templates appear in the **Add event** menu and in **Suggested for this page** when they match the current page context.

Formie ships built-in templates for GTM, GA4, Meta, and a blank starter event. Use this event to add your own analytics presets for a project or plugin.

## Register a template

Listen for `ClientEventTemplates::EVENT_REGISTER_CLIENT_EVENT_TEMPLATES` in your module's `init()` method and push one or more template definitions onto the event.

```php
use Craft;
use verbb\formie\events\RegisterClientEventTemplatesEvent;
use verbb\formie\services\ClientEventTemplates;
use yii\base\Event;

Event::on(ClientEventTemplates::class, ClientEventTemplates::EVENT_REGISTER_CLIENT_EVENT_TEMPLATES, function(RegisterClientEventTemplatesEvent $event) {
    $event->templates[] = [
        'handle' => 'acme_demo_request',
        'label' => Craft::t('site', 'Acme — Demo request'),
        'description' => Craft::t('site', 'Push a demo-request event after the final page submits.'),
        'category' => 'acme',
        'categoryLabel' => Craft::t('site', 'Acme Analytics'),
        'event' => 'demo_request',
        'pageContexts' => ['last-page', 'single-page'],
        'payload' => [
            ['key' => 'form_id', 'value' => '{form:handle}', 'kind' => 'static'],
            ['key' => 'form_name', 'value' => '{form:name}', 'kind' => 'static'],
            [
                'key' => 'email',
                'value' => '',
                'kind' => 'field',
                'fieldTypes' => ['email'],
                'mappingLabel' => Craft::t('site', 'Email field'),
                'required' => true,
            ],
        ],
    ];
});
```

When an author picks the template, Formie materializes it into a normal page client event. Authors can edit the event name, payload, and conditions afterwards.

## Template properties

| Property | Required | Description |
| --- | --- | --- |
| `handle` | Yes | Unique template identifier. Use a project or plugin prefix, e.g. `acme_demo_request`. |
| `label` | Yes | Author-facing name in the **Add event** menu. |
| `description` | No | Short helper text shown under the label in the menu. |
| `category` | No | Internal grouping key. Defaults to `general`. |
| `categoryLabel` | No | Group heading in the **Add event** menu. Defaults to `General`. |
| `event` | No | Default analytics event name. Defaults to `formPageSubmission`. |
| `pageContexts` | No | Where the template may be suggested. Defaults to `['any']`. |
| `payload` | No | Default payload rows. See below. |

Handles must be unique across all registered templates. If two templates share a handle, the last one registered wins.

## Page contexts

`pageContexts` controls which templates appear in **Suggested for this page**. They do not prevent an author from adding a template manually from **Add event**.

| Value | Suggested when |
| --- | --- |
| `any` | Any page |
| `single-page` | The form has one page |
| `first-page` | The first page of a multi-page form |
| `middle-page` | A middle page of a multi-page form |
| `last-page` | The final page of a multi-page form |

## Payload rows

Each payload row becomes one property on the materialized client event.

### Static rows

Use `kind` => `static` (or omit `kind`) for values that are already known or use Formie reference tokens.

```php
['key' => 'form_id', 'value' => '{form:handle}', 'kind' => 'static'],
['key' => 'currency', 'value' => 'USD', 'kind' => 'static'],
```

Static values can use the same reference tokens as other variable-aware builder fields, such as `{form:handle}`, `{form:name}`, and `{field:reference}`.

### Field mapping rows

Use `kind` => `field` when the author should choose a form field during template insertion.

```php
[
    'key' => 'email',
    'value' => '',
    'kind' => 'field',
    'fieldTypes' => ['email'],
    'mappingLabel' => Craft::t('site', 'Email field'),
    'required' => true,
],
```

| Property | Description |
| --- | --- |
| `fieldTypes` | Limits which field types can be mapped. Use Formie field type handles such as `email`, `number`, `single-line-text`, and `calculations`. |
| `mappingLabel` | Label shown in the mapping dialog. Defaults to the payload `key`. |
| `required` | When `true`, the author must map the field before the template can be inserted. |

When the template is inserted, Formie stores the mapped value as a field reference token, e.g. `{field:a1b2c3}`.

Templates with one or more `field` rows open a mapping dialog before insertion. Templates with only static rows are inserted immediately.

## Materialized event shape

After insertion, a template becomes a normal page client event:

```php
[
    'event' => 'demo_request',
    'payload' => [
        ['key' => 'form_id', 'value' => '{form:handle}'],
        ['key' => 'email', 'value' => '{field:a1b2c3}'],
    ],
    'templateHandle' => 'acme_demo_request',
    'templateLabel' => 'Acme — Demo request',
    'enableConditions' => false,
    'conditions' => [
        'applyRule' => 'apply',
        'conditionRule' => 'all',
        'conditions' => [],
    ],
]
```

When a submission succeeds, Formie resolves payload values server-side after a successful page submit. See [Tracking and analytics](/frontend/tracking-and-analytics) for how resolved events are pushed to `dataLayer` and returned in Ajax responses.

## Programmatic access

You can also read or materialize templates in PHP:

```php
$templates = Formie::$plugin->getClientEventTemplates()->getRegisteredTemplates();
$template = Formie::$plugin->getClientEventTemplates()->getTemplate('acme_demo_request');

$event = Formie::$plugin->getClientEventTemplates()->materializeTemplate('acme_demo_request', [
    'email' => '{field:a1b2c3}',
]);
```

`getBuilderConfig()` returns the author-safe subset exposed to the control panel form builder.

## Troubleshooting

### The template does not appear in the builder

- Confirm the event listener is registered during module `init()`.
- Ensure `handle` is unique and not empty.
- Hard-refresh the control panel after changing plugin code.

### A field cannot be mapped

- Check that `fieldTypes` includes the field's type handle.
- Mapping only includes fields on the current form layout.

### Suggested templates do not show for a page

- Check `pageContexts` against the current page position.
- The built-in `blank` template is excluded from suggestions because it applies everywhere and is always available from **Add event**.
