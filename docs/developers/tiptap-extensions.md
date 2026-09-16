# TipTap Extensions

Formie exposes two extension lanes for the rich-text editors used throughout the form builder. Both keep editing, read-only previews, frontend output, and email rendering on the same document schema.

Formie does not register any project-specific extensions itself. The controls and TextStyle capabilities listed below are provided by Plugin Kit; your module or plugin can add to that shared schema through Formie's registration event.

- Use a declarative TextStyle definition for a constrained visual attribute.
- Use matching PHP and JavaScript extensions for custom nodes, semantic marks, node views, ProseMirror plugins, or other advanced behavior.

Registrations must be installed during application bootstrap, before the form builder creates an editor. Existing editor instances are not rebuilt dynamically.

## Enable Built-in Text Styles

Font family, font size, text color, background color, line height, and small caps already use TipTap's `textStyle` mark. Enable their controls for a particular Formie rich-text field in `config/formie/rich-text.json`:

```json
{
    "fields": {
        "content": {
            "buttons": [
                "font-family",
                "font-size",
                "bold",
                "italic",
                "small-caps",
                "text-color",
                "line-height",
                "link"
            ]
        }
    }
}
```

These controls use the official TipTap TextStyle extensions. Formie sends the same attributes to `verbb/tiptap` for server-side HTML rendering, so no custom registration is required for the built-in styles.

## Enable Small Caps

Small caps is built in but is not added to existing toolbars automatically. Add `small-caps` to any relevant entry in `config/formie/rich-text.json`:

```json
{
    "fields": {
        "content": {
            "buttons": ["bold", "italic", "small-caps", "link"]
        },
        "instructions": {
            "buttons": ["bold", "italic", "small-caps", "link"]
        }
    },
    "notifications": {
        "content": {
            "buttons": ["bold", "italic", "small-caps", "variableTag"]
        }
    }
}
```

The stored representation is a `textStyle` mark with `fontVariantCaps: "small-caps"`. Formie and `verbb/tiptap` render it as `font-variant-caps: small-caps` in frontend and notification HTML.

Small caps is owned by Plugin Kit, but it is not a separate TipTap mark. It adds the constrained `fontVariantCaps` attribute to `textStyle`, allowing it to coexist with a font family, size, color, background color, and line height on the same text.

## Declare a Safe Text Style Once

Listen for `TiptapExtensions::EVENT_REGISTER_EXTENSIONS` in your module or plugin `init()` method. A TextStyle definition is sent to the form builder as validated metadata and installed automatically on the client:

```php
use verbb\formie\events\RegisterTiptapExtensionsEvent;
use verbb\formie\services\TiptapExtensions;
use verbb\formie\tiptap\TextStyleDefinition;
use yii\base\Event;

Event::on(
    TiptapExtensions::class,
    TiptapExtensions::EVENT_REGISTER_EXTENSIONS,
    function(RegisterTiptapExtensionsEvent $event): void {
        $event->registerTextStyle(
            new TextStyleDefinition(
                id: 'acme-uppercase',
                label: 'Uppercase',
                attribute: 'textTransform',
                cssProperty: 'text-transform',
                allowedValues: ['uppercase'],
                toolbarValue: 'uppercase',
            ),
        );
    },
);
```

Add `acme-uppercase` to the desired `buttons` configuration. Declarative styles are limited to Formie's allowlisted CSS properties and values. This prevents a project configuration or CP bootstrap value from becoming arbitrary CSS.

## Register a Full Extension

Persisted custom nodes and marks require matching server and client extensions. Register the PHP half with the same stable ID used by the JavaScript half:

```php
Event::on(
    TiptapExtensions::class,
    TiptapExtensions::EVENT_REGISTER_EXTENSIONS,
    function(RegisterTiptapExtensionsEvent $event): void {
        $event->registerExtension('acme/abbreviation', new AbbreviationMark());
    },
);
```

The JavaScript half must use Formie's bundled TipTap instance. Do not bundle a second copy of TipTap or ProseMirror. Formie exposes `core` and the ProseMirror `model`, `state`, and `view` modules on `Craft.Formie.tiptap`:

```js
const register = ({ detail }) => {
    const { Mark } = detail.core;

    detail.registerTiptapExtension('acme/abbreviation', () => Mark.create({
        name: 'abbreviation',
        parseHTML: () => [{ tag: 'abbr' }],
        renderHTML: ({ HTMLAttributes }) => ['abbr', HTMLAttributes, 0],
    }));

    detail.registerTiptapControl('abbreviation', {
        label: 'Abbreviation',
        run: (editor) => editor.chain().focus().toggleMark('abbreviation').run(),
        isActive: (editor) => editor.isActive('abbreviation'),
    });
};

if (Craft.Formie?.tiptap) {
    register({ detail: Craft.Formie.tiptap });
} else {
    document.addEventListener('formie:tiptap:register', register, { once: true });
}
```

Add `abbreviation` to the toolbar configuration. The server registration ID is checked before the form builder mounts; a missing client half throws a clear error rather than allowing an editor with an incompatible schema to save.

## Asset Loading

Ship the JavaScript registration in a Craft AssetBundle and depend on `verbb\formie\web\assets\cp\TiptapAsset`. This guarantees that the bridge and its bundled modules exist before your script runs. Register your bundle on the Formie form-builder CP route during your plugin or module bootstrap.

Client-only behavior extensions may omit the PHP registration when they cannot change stored content. Any node, mark, or attribute that can be persisted must be registered on both sides and should target both Plugin Kit document surfaces.

Formie's bridge delegates to Plugin Kit's application registry. It does not use Vizy's product-specific manifest system; Vizy remains free to provide its larger editor experience while sharing the same underlying stored TipTap schema.

For the underlying APIs and schema rules, see Plugin Kit's [Extending TipTap](https://docs.verbb.io/plugin-kit/web/guides/tiptap-extensibility) guide, the [`verbb/tiptap` PHP package](https://github.com/verbb/tiptap), and TipTap's [TextStyleKit documentation](https://tiptap.dev/docs/editor/extensions/functionality/text-style-kit).
