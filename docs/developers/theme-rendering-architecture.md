# Theme and Rendering Architecture

Formie resolves presentation per render. It does not save per-form theme config or mutate a shared `Form` element with render-specific state.

## Four Customisation Layers

Use the narrowest layer that solves the problem:

1. Select `formie` or `none` for the visual theme.
2. Use trusted server-rendered `themeConfig` for slot classes, attributes, CSS custom properties and small prepend/append content.
3. Use `--formie-*` CSS custom properties for visual tokens.
4. Override template partials or use trusted PHP slot events for structural markup.

The stable Formie 3 theme grammar remains compatible. Client-rendered forms own their markup; selecting them does not implicitly request server-rendered HTML.

## Immutable Render State

Each render creates a `ResolvedTheme` inside a `RenderFrame`. The frame contains the selected mode, validated config, canonical browser-state classes and a digest. Nested and concurrent renders use independent frames, including two renders of the same `Form` with different themes.

Compatibility getters on `Form` read the active frame. They do not attach mutable theme or field-theme storage to the element.

## Trust Boundary

Trusted server PHP/Twig can use tag changes and explicit raw `html` nodes. Text nodes are escaped by default. Browser-transported config is schema-, depth-, node- and size-bounded and rejects:

- raw HTML and tag changes;
- `on*` event-handler attributes;
- arbitrary Twig and method expressions;
- invalid slots, properties, types, condition paths and CSS custom properties.

Summary and fragment tokens contain the canonical theme state and digest. A request can present the token, but cannot replace its executable theme configuration.

## Attribute Merge Order

Theme attributes and render-instance attributes merge first. Required core attributes merge last and remain the single source of functional, identity and accessibility markup. Do not create a second protected-attribute registry.

`Form::EVENT_MODIFY_SLOT_TAG`, `Field::EVENT_MODIFY_SLOT_TAG` and the equivalent integration event run after that merge. They are trusted expert escape hatches and may deliberately replace or remove core attributes.

## Browser State and Assets

Canonical dynamic class names come from `src/config/browser-theme-state.json`. The generated TypeScript and [Browser Theme State reference](/reference/browser-theme-state) must be rebuilt with the browser package whenever the manifest changes.

`formie-base.css` contains functional and accessibility behaviour. `formie-theme.css` contains the visual theme, while `formie.css` combines both for compatibility. The `none` theme still emits accessible markup, runtime hooks, base CSS and JavaScript; it omits only visual theme CSS and classes.
