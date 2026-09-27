# Theme and Rendering Architecture

Formie resolves presentation per render. It does not save per-form theme config or mutate a shared `Form` element with render-specific state.

## Four Customisation Layers

Use the narrowest layer that solves the problem:

1. Select `formie` or `none` for the visual theme.
2. Use trusted server-rendered `themeConfig` for slot classes, attributes, CSS custom properties and small prepend/append content.
3. Use `--formie-*` CSS custom properties for visual tokens.
4. Override template partials or use trusted PHP slot events for structural markup.

Client-rendered forms own their markup; selecting them does not implicitly request server-rendered HTML.

## Immutable Render State

Each render creates a `ResolvedTheme` inside a `RenderFrame`. The frame contains the selected mode, validated config, canonical browser-state classes and a digest. Nested and concurrent renders use independent frames, including two renders of the same `Form` with different themes.

Compatibility getters on `Form` read the active frame. They do not attach mutable theme or field-theme storage to the element.

## Trust Boundary

Trusted server PHP/Twig can use tag changes and explicit raw `html` nodes. Text nodes are escaped by default. Browser-transported config is schema-, depth-, node- and size-bounded and rejects:

- raw HTML and tag changes;
- `on*` event-handler attributes;
- arbitrary Twig and method expressions;
- invalid slots, properties, types, condition paths and CSS custom properties.

Summary refresh requests use the issued access token to restore the theme used when the form was rendered. They cannot replace that configuration with browser input. Tokens expire after seven days; a request with missing or expired theme state is rejected.

Web nodes must share the database and Formie security key. Scheduled progress cleanup removes expired theme records. Re-render cached forms within the seven-day lifetime to issue fresh tokens and extend retained state. Signature image links use field access without theme storage and retain their existing lifetime.

## Attribute Merge Order

Theme and render-option attributes merge first. Formie’s required submission, JavaScript and accessibility attributes merge last, so theme config cannot remove them.

`Form::EVENT_MODIFY_SLOT_TAG`, `Field::EVENT_MODIFY_SLOT_TAG` and the equivalent integration event run after that merge. They are trusted expert escape hatches and may deliberately replace or remove core attributes.

## Browser State and Assets

Use the keys listed in [Browser Theme State](/reference/browser-theme-state) to customise classes that change during browser interaction, such as loading and error states.

`formie-base.css` contains functional and accessibility behaviour. `formie-theme.css` contains the visual theme, while `formie.css` combines both for compatibility. The `none` theme still emits accessible markup, runtime hooks, base CSS and JavaScript; it omits only visual theme CSS and classes.
