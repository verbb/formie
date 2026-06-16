# Custom variable sources

Register your own server-resolved variables for Formie's variable picker. Custom sources appear in the control panel alongside built-in Form, Site, and System variables, and resolve anywhere Formie parses reference tokens — email notifications, integrations, PDF templates, and more.

## Register a source

Listen for `Variables::EVENT_REGISTER_VARIABLES` in your module's `init()` method and push one or more sources onto the event — the same pattern Formie uses for fields, integrations, and other registration events.

```php
use verbb\formie\events\RegisterVariablesEvent;
use verbb\formie\helpers\Variables;
use verbb\formie\variables\VariableSource;
use yii\base\Event;

Event::on(Variables::class, Variables::EVENT_REGISTER_VARIABLES, function(RegisterVariablesEvent $event) {
    $event->sources[] = VariableSource::create('acme_campaign', 'Campaign code')
        ->resolve(function($submission, $notification) {
            return Craft::$app->getRequest()->getCookies()->getValue('campaign') ?? '';
        });
});
```

That registers `{custom:acme_campaign}` in the variable picker under **General**.

## Token format

Custom sources use a single `custom` target and a unique handle you control:

```text
{custom:handle}
```

| Part | Rules |
| --- | --- |
| `custom` | Fixed target for all module-registered variables. |
| `handle` | Lowercase letters, numbers, and underscores; must start with a letter. Prefix with your project or plugin name to avoid clashes, e.g. `acme_campaign`. |

Built-in targets such as `site`, `user`, and `form` stay reserved for Formie. Your handle only needs to be unique among registered custom sources — `{custom:acme_campaign}` does not collide with `{site:name}` or other built-in tokens.

## Resolver callback

The `resolve()` callback runs on the server when Formie needs the value. It receives:

1. `Submission $submission` — the submission being processed.
2. `Notification|null $notification` — the notification context, when one is available.

Return a scalar or `Stringable` value. Objects are not stringified automatically.

```php
$event->sources[] = VariableSource::create('acme_owner_email', 'Account owner email')
    ->types([\verbb\formie\helpers\Variables::TYPE_EMAIL])
    ->resolve(function($submission) {
        $userId = $submission->getAuthorId();
        return $userId ? Craft::$app->getUsers()->getUserById($userId)?->email : null;
    });
```

Values are resolved lazily per submission and cached for the remainder of the request.

## Picker metadata

Configure each `VariableSource` before pushing it onto `$event->sources`:

| Method | Purpose |
| --- | --- |
| `VariableSource::create($handle, $label)` | Create a source with a unique handle and author-facing label. |
| `types([...])` | Hint compatible field types (`Variables::TYPE_TEXT`, `TYPE_EMAIL`, `TYPE_NUMBER`, `TYPE_URL`, `TYPE_DATE`, `TYPE_BOOLEAN`, `TYPE_ARRAY`). |
| `content($mode)` | `Variables::CONTENT_SINGLE_LINE` (default) or `Variables::CONTENT_ANY`. |
| `resolve(callable)` | Server resolver. Required for values to resolve. |

Custom sources appear anywhere **General** variables are offered, such as notification bodies and many integration mapping fields.

## Legacy beta API

Formie 4 betas briefly used `RegisterVariablesEvent::register($target, $handle, $label)` and tokens such as `{acme:campaign}`. That API is deprecated.

While [compatibility mode](/get-started/upgrading-from-v3#compatibility-mode) is enabled (the default), Formie still accepts the old registration helper and resolves legacy tokens against the combined handle (`acme_campaign`). Update to `$event->sources[]` and `{custom:handle}` when you can.

## Transforms

Custom variables support the same transform metadata as built-in variables when the returned value type matches.

```text
{custom:acme_score;transform=round}
```

## Troubleshooting

### The variable does not appear in the picker

- Confirm the event listener is registered before the form builder loads.
- Check that the handle uses only lowercase letters, numbers, and underscores.
- Reload the form builder after changing module code.

### The token appears but resolves empty

- Confirm `resolve()` is set on the source.
- Check the resolver return value is not `null` or an empty string.
- Use an inline default if needed: `{custom:acme_campaign|none}`.

### The variable works in notifications but not integrations

- Confirm the integration field accepts **General** variable groups.
- Some mapping UIs filter by value type; set `types()` to match the destination field.
