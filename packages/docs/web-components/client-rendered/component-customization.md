# Component customization

## What you can customize

`<formie-core-form>` renders from the client definition. Overrides go on **`FormieRegistry`**:

- **`registerField(tagName)`** — one custom element wraps each question: label, instructions, the control (projected as child content or via a slot), and errors.
- **`registerFieldControl(fieldKey, tagName)`** — only the control for a given field type or renderer key (e.g. `single-line-text`).
- **`registerRegion(key, tagName)`** — replace named layout regions (`loading`, `pageActions`, `errorSummary`, `page`, `form`, …).

Define your custom elements with **`customElements.define`** before you call the `register*` methods, and run registry setup **before** the host loads (or assign **`el.registry`** and call **`el.reload()`**).

## Choose the right layer

- Use **`registerField`** when you want one custom element to own the full field layout around the control.
- Use **`registerFieldControl`** when the default field layout is fine but a specific field type needs a custom input.
- Use **`registerRegion`** when you want to replace loading UI, page actions, error summary, or other named regions.
- Listen on **`formie-core-form`** for composed **`formie:*`** events, or use **`getFormieInstance()`** after **`formie:client:ready`**, when you need imperative access to form state and actions from script.

## Combined example

This example registers a custom **field** host (shadow root + default **slot** for the control) and a custom **single-line text** control. You can use light DOM only for either piece; shadow + slot is one way to place the projected control.

```js
import {
  registerFormieWebComponents,
  getFormieRegistry,
  FORMIE_CONTROL_VALUE_EVENT,
} from '@verbb/formie-web-components';

class StarterField extends HTMLElement {
  constructor() {
    super();
    this._field = undefined;
    this._errors = [];
    const sr = this.attachShadow({ mode: 'open' });
    sr.innerHTML = `
      <style>
        .card { border-radius: 0.75rem; border: 1px solid rgb(226 232 240); padding: 1rem; display: block; }
        label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
        ul { margin: 0.5rem 0 0; padding-left: 1.25rem; color: rgb(220 38 38); font-size: 0.875rem; }
      </style>
      <div class="card">
        <label part="label"></label>
        <slot></slot>
        <ul part="errors"></ul>
      </div>
    `;
  }

  get field() {
    return this._field;
  }

  set field(v) {
    this._field = v;
    this.#sync();
  }

  get errors() {
    return this._errors;
  }

  set errors(v) {
    this._errors = Array.isArray(v) ? v : [];
    this.#sync();
  }

  #sync() {
    const root = this.shadowRoot;
    if (!root) return;
    const label = root.querySelector('[part="label"]');
    const ul = root.querySelector('[part="errors"]');
    const f = this._field;
    if (label) {
      label.textContent = f?.label ? String(f.label) : '';
      label.style.display = f?.label ? 'block' : 'none';
    }
    if (ul) {
      ul.innerHTML = (this._errors || [])
        .map((e) => `<li>${String(e).replace(/</g, '&lt;')}</li>`)
        .join('');
      ul.hidden = !this._errors?.length;
    }
  }

  connectedCallback() {
    this.#sync();
  }
}

class StarterTextField extends HTMLElement {
  connectedCallback() {
    if (this.querySelector('input')) return;
    const input = document.createElement('input');
    input.type = 'text';
    input.className = 'starter-component-control';
    input.addEventListener('input', () => {
      this.dispatchEvent(
        new CustomEvent(FORMIE_CONTROL_VALUE_EVENT, {
          detail: input.value,
          bubbles: true,
          composed: true,
        }),
      );
    });
    this.append(input);
    this.#push();
  }

  get value() {
    return this._value;
  }

  set value(v) {
    this._value = v;
    this.#push();
  }

  get field() {
    return this._field;
  }

  set field(f) {
    this._field = f;
    this.#push();
  }

  get errorKey() {
    return this._errorKey;
  }

  set errorKey(k) {
    this._errorKey = k;
  }

  get disabled() {
    return this._disabled ?? false;
  }

  set disabled(v) {
    this._disabled = Boolean(v);
    this.#push();
  }

  get hidden() {
    return this._hidden ?? false;
  }

  set hidden(v) {
    this._hidden = Boolean(v);
    this.style.display = v ? 'none' : '';
  }

  #push() {
    const input = this.querySelector('input');
    if (!input) return;
    input.value = typeof this._value === 'string' ? this._value : '';
    input.disabled = this.disabled;
    const ph = this._field?.input?.placeholder;
    input.placeholder = typeof ph === 'string' ? ph : '';
  }
}

customElements.define('starter-field', StarterField);
customElements.define('starter-text-field', StarterTextField);

registerFormieWebComponents();

getFormieRegistry().registerField('starter-field');
getFormieRegistry().registerFieldControl('single-line-text', 'starter-text-field');

const el = document.createElement('formie-core-form');
el.endpoint = 'https://formie.test';
el.formHandle = 'contactForm';
el.transport = 'rest';
document.body.append(el);
```

## Field host

Use **`registerField(tagName)`** when you want one custom element to wrap every field’s control. Formie sets **`field`** and **`errors`** and projects the built-in or registry control as **light DOM children**; use a **shadow root + `<slot>`** (as above) or your own projection so the control appears where you want.

## Field controls

Use **`registerFieldControl(fieldKey, tagName)`** when you only replace the control for a field type. Formie sets **`field`**, **`value`**, **`errorKey`**, **`disabled`**, and **`hidden`**, and listens for **`FORMIE_CONTROL_VALUE_EVENT`** with **`detail`** = the next value.

```js
import {
  registerFormieWebComponents,
  getFormieRegistry,
  FORMIE_CONTROL_VALUE_EVENT,
} from '@verbb/formie-web-components';

class SingleLineTextField extends HTMLElement {
  connectedCallback() {
    if (this.querySelector('input')) return;
    const input = document.createElement('input');
    input.type = 'text';
    input.addEventListener('input', () => {
      this.dispatchEvent(
        new CustomEvent(FORMIE_CONTROL_VALUE_EVENT, {
          detail: input.value,
          bubbles: true,
          composed: true,
        }),
      );
    });
    this.append(input);
  }

  set value(v) {
    this._value = v;
    const input = this.querySelector('input');
    if (input) input.value = typeof v === 'string' ? v : '';
  }

  get value() {
    return this._value;
  }

  set field(f) {
    this._field = f;
    const input = this.querySelector('input');
    const ph = f?.input?.placeholder;
    if (input && typeof ph === 'string') input.placeholder = ph;
  }

  set disabled(v) {
    const input = this.querySelector('input');
    if (input) input.disabled = Boolean(v);
  }

  set hidden(v) {
    this.style.display = v ? 'none' : '';
  }
}

customElements.define('my-single-line-text', SingleLineTextField);
registerFormieWebComponents();
getFormieRegistry().registerFieldControl('single-line-text', 'my-single-line-text');

const el = document.createElement('formie-core-form');
el.endpoint = 'https://formie.test';
el.formHandle = 'contactForm';
el.transport = 'rest';
document.body.append(el);
```

## Layout regions

`registerRegion(key, tagName)` keys: **`form`**, **`page`**, **`errorSummary`**, **`loading`**, **`pageActions`**. The host passes the same props the built-in views use (see `@verbb/formie-web-components` source types if you need exact shapes).

## Form instance and events outside the tree

The host does not expose a hook-style API. Use either:

- **Composed events** on **`formie-core-form`** (e.g. **`formie:submit:result`**, **`formie:client:ready`** — full list on the [overview](/web-components/client-rendered/overview) page), or
- **`el.getFormieInstance()`** once the form client is ready, for **`subscribe`**, **`submit`**, and related APIs.

```js
import { registerFormieWebComponents } from '@verbb/formie-web-components';

registerFormieWebComponents();

const el = document.createElement('formie-core-form');
el.endpoint = 'https://formie.test';
el.formHandle = 'contactForm';
el.transport = 'rest';

el.addEventListener('formie:client:ready', () => {
  const formie = el.getFormieInstance();
  console.log(formie);
});

el.addEventListener('formie:submit:result', (e) => {
  console.log(e.detail);
});

document.body.append(el);
```

## Deeper rendering

For a host other than **`formie-core-form`**, or a fully custom DOM tree, use **`renderFormView`** and **`@verbb/formie-core`** directly. Prefer **`FormieRegistry`** + **`formie-core-form`** until you need that escape hatch.
