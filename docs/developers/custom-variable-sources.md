# Custom Variable Sources

Use a custom source when editors need a value from your module in the **Variable Picker**. For example, a campaign service can supply a campaign code to a notification or integration mapping. Register a definition and a resolver in your module's `init()` method. The definition describes the value without fetching it; the resolver receives the explicit submission context when the value is needed.

```php
use verbb\formie\events\RegisterReferencesEvent;
use verbb\formie\fields\definitions\FieldValueType;
use verbb\formie\references\ReferenceCatalogue;
use verbb\formie\references\ReferenceContext;
use verbb\formie\references\ReferenceDefinition;
use verbb\formie\references\ReferenceShape;
use verbb\formie\references\ReferenceSource;
use verbb\formie\references\ReferenceType;
use verbb\formie\references\ReferenceUsage;
use yii\base\Event;

Event::on(ReferenceCatalogue::class, ReferenceCatalogue::EVENT_REGISTER, function(RegisterReferencesEvent $event) {
    $event->sources[] = new ReferenceSource(
        new ReferenceDefinition(
            id: 'acme/campaign',
            label: 'Campaign Code',
            category: 'custom',
            valueType: FieldValueType::string(),
            transforms: ['acme/shout'],
            server: true,
            browser: false,
            types: [ReferenceType::Text],
            shape: ReferenceShape::Inline,
            usages: [ReferenceUsage::RichText, ReferenceUsage::Integration],
        ),
        static fn(ReferenceContext $context): string => 'spring-sale',
    );
});
```

Editors can insert `{custom:acme/campaign}`. The picker receives its label, semantic `types`, inline or block `shape` and availability, never the resolved value or resolver. IDs use `vendor/name`; duplicate IDs and invalid registrations fail explicitly. Use translations for labels when your project supports multiple languages.

`usages` optionally restricts a source to `Text`, `RichText`, `EmailHeader`, `Integration`, `Url` or `Condition`. Omitting it allows any usage compatible with its shape; an empty list allows none. The picker filters this metadata and the resolver checks it before calling your source. A block-shaped source is only available to rich-text consumers, never a subject, address, mapping or redirect. Output encoding remains separate: an integration text template still produces text, while an exact integration reference retains its native value. Custom callers should provide `usage` on `ReferenceContext`; interpolation derives text, rich-text, header or URL usage from the selected output context when no explicit usage was supplied.

## Context and Return Types

`ReferenceContext` carries the form, submission, site, user, row selections, permissions and output context. The submission factory captures its own site and submitting user; resolution does not switch Craft's current site or borrow the currently logged-in CP operator. Read these context properties instead of mutable global request state. A source can check the context's permissions before returning sensitive application data.

Return the declared `FieldValueType`. Numeric domain values use decimal strings through `FieldValueType::string()` and declare `types: [ReferenceType::Number]` on their reference definition. Runtime PHP types and semantic picker types are separate. Rich objects must declare their class and provide deliberate string/data behaviour at their owning field boundary. A mismatched return value produces `invalidType`. Missing registrations produce `unknownSource`; denied availability produces `forbiddenSource`.

`browser: false` is the default. A declaration of browser availability does not copy server values or PHP callbacks to the browser. Browser code must supply an explicit browser implementation and its permitted values. Never register credentials as picker values.

## Custom Transforms

A transform takes a resolved value and returns a declared output type. Add it to the same registration event:

```php
use verbb\formie\references\ReferenceTransform;

$event->transforms[] = new ReferenceTransform(
    id: 'acme/shout',
    inputType: FieldValueType::string(),
    outputType: FieldValueType::string(),
    transform: static fn(string $value): string => strtoupper($value),
    server: true,
    browser: false,
);
```

This snippet belongs inside the listener above. List its ID in the source definition’s `transforms` array to allow it for that source. `{custom:acme/campaign;transform=acme%2Fshout}` returns `SPRING-SALE`. The picker groups the transform by its input type. Declare supported parameter names with `parameters: ['suffix']`; the callback receives the value, parameter map and context. Parameter names outside that declaration are rejected. Input/output type failures remain typed diagnostics and do not silently preserve the original value.

See [Reference Tokens](/developers/reference-tokens) for exact resolution, output contexts and handling diagnostics in a consumer.
