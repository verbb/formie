<span id="component-customization"></span>

# Component Customisation

## What You Can Customise

`<FormieClientForm />` supports three main customisation layers:

- `components` for top-level `Form`, `Page`, **`Field`**, and error-summary replacements
- `fieldComponents` for field-type-specific rendering (the control only)
- `slots` for smaller layout regions inside the default component tree

## Choose the Right Layer

- Use `components` for major layout ownership.
- Use `fieldComponents` for type-specific UI replacement.
- Use `slots` for small structural intercepts.
- Use composables when the component needs direct form state and actions.

## Combined Example

Real client-rendered forms often combine more than one layer. Custom `Field` components receive the **default slot** for the rendered control (same role as `children` in React).

```vue
<script setup lang="ts">
import { defineComponent, h } from 'vue';
import { FormieClientForm, type FormieFieldComponentProps } from '@verbb/formie-vue';

const Field = defineComponent({
  name: 'StarterField',
  props: {
    field: { type: Object, required: true },
    errors: { type: Array as () => string[], required: true },
    inputId: { type: String, default: '' },
  },
  setup(props, { slots }) {
    return () =>
      h('div', { class: 'starter-component-card' }, [
        props.field.label ? h('label', { for: props.inputId }, props.field.label) : null,
        slots.default?.(),
        props.errors.length
          ? h(
              'ul',
              null,
              (props.errors as string[]).map((error, index) =>
                h('li', { key: `${props.field.id}:${index}` }, error),
              ),
            )
          : null,
      ]);
  },
});

const TextField = defineComponent({
  name: 'StarterTextField',
  props: {
    field: { type: Object, required: true },
    value: { default: undefined },
    disabled: { type: Boolean, default: false },
    inputId: { type: String, default: '' },
    setValue: { type: Function as unknown as () => (v: unknown) => void, required: true },
  },
  setup(props) {
    return () =>
      h('input', {
        type: 'text',
        id: props.inputId,
        value: typeof props.value === 'string' ? props.value : '',
        disabled: props.disabled,
        placeholder:
          typeof props.field.input?.placeholder === 'string'
            ? props.field.input.placeholder
            : undefined,
        onInput: (event: Event) => {
          props.setValue((event.target as HTMLInputElement).value);
        },
      });
  },
});
</script>

<template>
  <FormieClientForm
    
    transport="rest"
    endpoint="https://formie.test"
    form-handle="contactForm"
    :components="{ Field }"
    :field-components="{ 'single-line-text': TextField }"
  />
</template>
```

## Labels and Accessibility

Each field gets IDs that link its label, instructions and errors to its control. Custom `Field` components and field components both receive them as props: `inputId` for the control, `labelId` for the label, `instructionsId` for the instructions and `errorId` for the error list. The default `Field` component gives its label `for="inputId"`, and the default controls use these IDs, so clicking a label focuses its control and screen readers announce the instructions with it.

In the example above, the custom label points at `inputId` and the custom text input uses it as its `id`. Radio, Checkboxes and Agree fields have several controls, so the default controls group them with `role="group"` (`radiogroup` for Radio) and name the group with `aria-labelledby` pointing at `labelId`. When you replace the `Field` component, the default controls also name themselves with `aria-label`, so they stay accessible even if your label doesn't use these IDs.

### Required Fields

A required field's wrapper has `data-formie-field-required="true"`, and its control has `aria-required="true"` so screen readers announce it. For a Radio field, `aria-required` goes on the radio group. A Checkboxes field has no `aria-required`, as ARIA doesn't support it on a group of checkboxes. Formie validates required fields itself, so controls don't get the native `required` attribute, which would show the browser's own validation messages first.

Use the wrapper attribute to add a required marker with CSS alone:

```css
.formie-vue-field[data-formie-field-required='true'] > .formie-vue-label::after {
  content: ' *';
  color: rgb(220 38 38);
}
```

## Top-Level Components

Use `components` when you want to replace the top-level `Form` layout rather than one field type:

```ts
import { defineComponent, h } from 'vue';
import type { FormieVueComponents } from '@verbb/formie-vue';

const components: FormieVueComponents = {
  Form: defineComponent({
    name: 'StarterFormShell',
    props: {
      definition: { type: Object, required: true },
      session: { type: Object, required: true },
      state: { type: Object, required: true },
      className: { type: String, default: undefined },
      onSubmit: { type: Function, required: true },
    },
    setup(props, { slots }) {
      return () =>
        h(
          'form',
          {
            class: props.className,
            onSubmit: (event: Event) => {
              event.preventDefault();
              (props.onSubmit as () => void)();
            },
          },
          slots.default?.(),
        );
    },
  }),
};
```

## Field Components

Use `fieldComponents` when you want a Vue component for one specific field type:

```vue
<script setup lang="ts">
import { defineComponent, h } from 'vue';
import { FormieClientForm, type FormieFieldComponentProps } from '@verbb/formie-vue';

const SingleLineTextField = defineComponent({
  name: 'SingleLineTextField',
  props: {
    field: { type: Object, required: true },
    value: { default: undefined },
    disabled: { type: Boolean, default: false },
    setValue: { type: Function as unknown as () => (v: unknown) => void, required: true },
  },
  setup(props: FormieFieldComponentProps) {
    return () =>
      h('input', {
        type: 'text',
        value: typeof props.value === 'string' ? props.value : '',
        disabled: props.disabled,
        placeholder:
          typeof props.field.input?.placeholder === 'string'
            ? props.field.input.placeholder
            : undefined,
        onInput: (event: Event) => {
          props.setValue((event.target as HTMLInputElement).value);
        },
      });
  },
});
</script>

<template>
  <FormieClientForm
    
    transport="rest"
    endpoint="https://formie.test"
    form-handle="contactForm"
    :field-components="{ 'single-line-text': SingleLineTextField }"
  />
</template>
```

Formie looks for a component under the field's type first, then under the control it renders. Fields without a control of their own, such as Recipients, Entries, Address Country and the parts of a Date field, render as `dropdown`, `radio` or `checkboxes`, so a component registered for `dropdown` renders them too. Element fields like Entries store a list of IDs even when only one can be chosen.

## Slots

Use `slots` when you want to intercept smaller layout regions without replacing the whole **`Field`** component (the wrapper around each control).

This is useful for wrapping labels, instructions, inputs, or error regions in app-specific markup. Each slot component receives `slotKey`, the `attributes` of the element it wraps, and the `field` it belongs to, so a label slot can check `field.required` to add its own required marker.

## Composables

These composables are available inside client-rendered form trees:

- `useFormie()`
- `useFormieField(fieldId)`
- `useFormiePage(pageId)`
- `useFormieInstance()`
- `useFormieSlot(key)`

Use them when your custom Vue components need form state instead of only render props.

In Vue, `useFormie()` returns `state` and `instance` as refs (`ShallowRef`). Use `.value` in script, or unwrap in templates.

## Custom Actions Example

```vue
<script setup lang="ts">
import { computed } from 'vue';
import { useFormie } from '@verbb/formie-vue';

const { state, instance } = useFormie();

const submitting = computed(() => state.value?.status === 'submitting');

function submit(action: 'save' | 'submit') {
  const formie = instance.value;
  if (formie) {
    void formie.submit(action);
  }
}
</script>

<template>
  <div class="flex gap-3">
    <button type="button" :disabled="submitting" @click="submit('save')">Save</button>

    <button type="button" :disabled="submitting" @click="submit('submit')">Submit</button>
  </div>
</template>
```
