# Styling

If you are in client-rendered forms, React owns the visible UI and you should style your own components directly.

## Import the browser CSS

Server-rendered forms use the browser package theme:

```tsx
import '@verbb/formie-browser/css/formie.css';
```

Or split the layers if your app needs that separation:

```tsx
import '@verbb/formie-browser/css/formie-base.css';
import '@verbb/formie-browser/css/formie-theme.css';
```

## Use tokens first

The safest customization layer is the same `--formie-*` token surface used by the browser package.

Use [Browser → CSS variables](/browser/ui-reference/css-variables) for the full token reference.

Scope those tokens on a wrapper around the form:

```css
.contact-form-shell {
  --formie-color-primary: #0f766e;
  --formie-color-primary-hover: #115e59;
  --formie-button-border-radius: 999px;
}
```

```tsx
<div className="contact-form-shell">
  <FormieForm
    transport="rest"
    endpoint="https://formie.test"
    formHandle="contactForm"
  />
</div>
```

## Use `themeConfig` second

If tokens are not enough, `themeConfig` lets you inject classes, attributes, and reset behavior into the shipped server-rendered theme.

### Add classes and attributes

Use this when you want to keep Formie's HTML structure but attach app-owned classes or data attributes at known theme hooks:

```tsx
<FormieForm
  transport="rest"
  endpoint="https://formie.test"
  formHandle="contactForm"
  theme="formie"
  themeConfig={{
    field: {
      attributes: {
        class: ['starter-field', 'starter-field--spaced'],
        'data-scenario': 'custom-styling',
      },
    },
    fieldLabel: {
      attributes: {
        class: ['starter-label'],
      },
    },
    fieldControl: {
      attributes: {
        class: ['starter-control'],
      },
    },
  }}
/>
```

### Reset selected theme layers

Use this when you want Formie to keep the HTML structure, but you want to rebuild specific visual layers yourself:

```tsx
<FormieForm
  transport="rest"
  endpoint="https://formie.test"
  formHandle="contactForm"
  theme="formie"
  themeConfig={{
    field: {
      reset: true,
      attributes: {
        class: ['rounded-2xl', 'border', 'p-4'],
      },
    },
    fieldLabel: {
      reset: true,
      attributes: {
        class: ['text-xs', 'font-semibold', 'uppercase'],
      },
    },
  }}
/>
```

### Compose with tokens

`themeConfig` and CSS tokens work well together. Use `themeConfig` for structural hooks, then use CSS for the visual system:

```tsx
<div className="marketing-shell">
  <FormieForm
    transport="rest"
    endpoint="https://formie.test"
    formHandle="contactForm"
    theme="formie"
    themeConfig={{
      field: {
        attributes: {
          class: ['marketing-field'],
        },
      },
      fieldLabel: {
        attributes: {
          class: ['marketing-label'],
        },
      },
    }}
  />
</div>
```

```css
.marketing-shell {
  --formie-color-primary: #0f766e;
  --formie-button-border-radius: 999px;
}

.marketing-field {
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.8);
}
```

## Use CSS third

If tokens are not enough, add targeted CSS against the shipped server-rendered markup.

The safe rule is:

1. tokens first
2. `themeConfig` second
3. targeted CSS third
4. structural replacement only when you are leaving server-rendered forms behind

If you find yourself fighting the shipped HTML structure with heavier CSS, that is usually a sign the problem belongs in client-rendered forms instead.
