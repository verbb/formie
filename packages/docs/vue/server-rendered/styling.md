# Styling

If you are in client-rendered forms, Vue owns the visible UI and you should style your own components directly.

## Import the browser CSS

Server-rendered forms use the browser package theme:

```ts
import '@verbb/formie-browser/css/formie.css';
```

Or split the layers if your app needs that separation:

```ts
import '@verbb/formie-browser/css/formie-base.css';
import '@verbb/formie-browser/css/formie-theme.css';
```

## Use tokens first

The safest customization layer is the same `--formie-*` token surface used by the browser package.

Use [Browser > CSS variables](/browser/ui-reference/css-variables) for the full token reference.

Scope those tokens on a wrapper around the form:

```css
.contact-form-shell {
  --formie-color-primary: #0f766e;
  --formie-color-primary-hover: #115e59;
  --formie-button-border-radius: 999px;
}
```

```vue
<template>
  <div class="contact-form-shell">
    <FormieForm
      transport="rest"
      endpoint="https://formie.test"
      form-handle="contactForm"
    />
  </div>
</template>
```

When you use `useFormieHtml()`, wrap the element bound to `rootRef` the same way.

## Use `themeConfig` second

If tokens are not enough, `themeConfig` lets you inject classes, attributes, and reset behavior into the shipped server-rendered theme.

### Add classes and attributes

Use this when you want to keep Formie's HTML structure but attach app-owned classes or data attributes at known theme hooks:

```vue
<script setup lang="ts">
import { FormieForm } from '@verbb/formie-vue';
</script>

<template>
  <FormieForm
    transport="rest"
    endpoint="https://formie.test"
    form-handle="contactForm"
    theme="formie"
    :theme-config="{
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
    }"
  />
</template>
```

### Reset selected theme layers

Use this when you want Formie to keep the HTML structure, but you want to rebuild specific visual layers yourself:

```vue
<script setup lang="ts">
import { FormieForm } from '@verbb/formie-vue';
</script>

<template>
  <FormieForm
    transport="rest"
    endpoint="https://formie.test"
    form-handle="contactForm"
    theme="formie"
    :theme-config="{
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
    }"
  />
</template>
```

### Compose with tokens

`themeConfig` and CSS tokens work well together. Use `themeConfig` for structural hooks, then use CSS for the visual system:

```vue
<script setup lang="ts">
import { FormieForm } from '@verbb/formie-vue';
</script>

<template>
  <div class="marketing-shell">
    <FormieForm
      transport="rest"
      endpoint="https://formie.test"
      form-handle="contactForm"
      theme="formie"
      :theme-config="{
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
      }"
    />
  </div>
</template>
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
