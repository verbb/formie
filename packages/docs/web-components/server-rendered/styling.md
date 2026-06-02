# Styling

If you are using **client-rendered forms** (`<formie-core-form>`), the element owns the visible structure, so style it with your own CSS; you do not need the browser theme unless you want shared tokens. See [Client-rendered forms](/web-components/client-rendered/overview).

Web Components **server-rendered forms** (`<formie-form>`) mount the browser-owned Formie UI, so the styling surface is the same browser theme surface used by `@verbb/formie-browser`.

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

Scope those tokens on a wrapper around the custom element:

```css
.contact-form-shell {
  --formie-color-primary: #0f766e;
  --formie-color-primary-hover: #115e59;
  --formie-button-border-radius: 999px;
}
```

```html
<div class="contact-form-shell">
  <formie-form
    form-handle="contactForm"
    endpoint="https://formie.test"
  ></formie-form>
</div>
```

## Use `themeConfig` second

There is no `theme-config` **attribute** on `<formie-form>` (objects do not map cleanly to attributes). Set the **`themeConfig` property** in JavaScript, or use `createFormieClient()` and pass `themeConfig` in the mount options.

### On `<formie-form>` (property)

```html
<formie-form
  id="contact"
  transport="rest"
  form-handle="contactForm"
  endpoint="https://formie.test"
  theme="formie"
></formie-form>

<script type="module">
  import { registerFormieWebComponents } from '@verbb/formie-web-components';

  registerFormieWebComponents();
  await customElements.whenDefined('formie-form');

  const el = document.getElementById('contact');

  if (el) {
    el.themeConfig = {
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
    };
  }
</script>
```

### Add classes and attributes (client mount)

Same `themeConfig` shape when mounting with `createFormieClient()`:

```html
<script type="module">
  import { createFormieClient } from '@verbb/formie-web-components';

  const client = createFormieClient();
  const root = document.querySelector('#contact-form');

  if (root) {
    await client.mount(root, {
      mode: 'server-rendered',
      transport: 'rest',
      endpoint: 'https://formie.test',
      formHandle: 'contactForm',
      theme: 'formie',
      themeConfig: {
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
      },
    });
  }
</script>

<div id="contact-form"></div>
```

### Reset selected theme layers

```html
<script type="module">
  import { createFormieClient } from '@verbb/formie-web-components';

  const client = createFormieClient();
  const root = document.querySelector('#contact-form');

  if (root) {
    await client.mount(root, {
      mode: 'server-rendered',
      transport: 'rest',
      endpoint: 'https://formie.test',
      formHandle: 'contactForm',
      theme: 'formie',
      themeConfig: {
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
      },
    });
  }
</script>

<div id="contact-form"></div>
```

### Compose with tokens

`themeConfig` and CSS tokens work well together. Use `themeConfig` for structure (wrappers, regions), then use CSS for the visual system:

```html
<div class="marketing-shell">
  <formie-form
    form-handle="contactForm"
    endpoint="https://formie.test"
    theme="formie"
  ></formie-form>
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
4. structural replacement only when you are leaving server-rendered forms behind (for example moving to `<formie-core-form>` or `createFormieClient()` with a different strategy)

If you find yourself fighting the shipped HTML structure with heavier CSS, that is usually a sign you want **client-rendered forms** or a lower-level client mount instead.
