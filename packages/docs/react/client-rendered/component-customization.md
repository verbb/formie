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
- Use hooks when the component needs direct form state and actions.

## Combined Example

Real client-rendered forms often combine more than one layer:

```tsx
import {
  FormieClientForm,
  type FormieFieldProps,
  type FormieFieldComponentProps,
} from '@verbb/formie-react';

function Field({ field, errors, inputId, children }: FormieFieldProps) {
  return (
    <div className="starter-component-card">
      {field.label ? <label htmlFor={inputId}>{field.label}</label> : null}
      {children}
      {errors.length ? (
        <ul>
          {errors.map((error, index) => <li key={`${field.id}:${index}`}>{error}</li>)}
        </ul>
      ) : null}
    </div>
  );
}

function TextField({ field, value, disabled, inputId, setValue }: FormieFieldComponentProps) {
  return (
    <input
      type="text"
      id={inputId}
      value={typeof value === 'string' ? value : ''}
      disabled={disabled}
      placeholder={typeof field.input.placeholder === 'string' ? field.input.placeholder : undefined}
      onChange={(event) => setValue(event.target.value)}
    />
  );
}

export function ContactForm() {
  return (
    <FormieClientForm
      
      transport="rest"
      endpoint="https://formie.test"
      formHandle="contactForm"
      components={{
        Field,
      }}
      fieldComponents={{
        'single-line-text': TextField,
      }}
    />
  );
}
```

## Labels and Accessibility

Each field gets IDs that link its label, instructions and errors to its control. Custom `Field` components and field components both receive them as props: `inputId` for the control, `labelId` for the label, `instructionsId` for the instructions and `errorId` for the error list. The default `Field` component gives its label `htmlFor={inputId}`, and the default controls use these IDs, so clicking a label focuses its control and screen readers announce the instructions with it.

In the example above, the custom label points at `inputId` and the custom text input uses it as its `id`. Radio, Checkboxes and Agree fields have several controls, so the default controls group them with `role="group"` (`radiogroup` for Radio) and name the group with `aria-labelledby` pointing at `labelId`. When you replace the `Field` component, the default controls also name themselves with `aria-label`, so they stay accessible even if your label doesn't use these IDs.

### Required Fields

A required field's wrapper has `data-formie-field-required="true"`, and its control has `aria-required="true"` so screen readers announce it. For a Radio field, `aria-required` goes on the radio group. A Checkboxes field has no `aria-required`, as ARIA doesn't support it on a group of checkboxes. Formie validates required fields itself, so controls don't get the native `required` attribute, which would show the browser's own validation messages first.

Use the wrapper attribute to add a required marker with CSS alone:

```css
.formie-react-field[data-formie-field-required='true'] > .formie-react-label::after {
  content: ' *';
  color: rgb(220 38 38);
}
```

## Top-Level Components

Use `components` when you want to replace the top-level `Form` layout rather than one field type:

```tsx
import type { FormieReactComponents } from '@verbb/formie-react';

const components: FormieReactComponents = {
  Form({ children, className, onSubmit }) {
    return (
      <form className={className} onSubmit={(event) => {
        event.preventDefault();
        onSubmit();
      }}>
        {children}
      </form>
    );
  },
};
```

## Field Components

Use `fieldComponents` when you want a React component for one specific field type:

```tsx
import { FormieClientForm, type FormieFieldComponentProps } from '@verbb/formie-react';

function SingleLineTextField({ field, value, disabled, setValue }: FormieFieldComponentProps) {
  return (
    <input
      type="text"
      value={typeof value === 'string' ? value : ''}
      disabled={disabled}
      placeholder={typeof field.input.placeholder === 'string' ? field.input.placeholder : undefined}
      onChange={(event) => setValue(event.target.value)}
    />
  );
}

export function ContactForm() {
  return (
    <FormieClientForm
      
      transport="rest"
      endpoint="https://formie.test"
      formHandle="contactForm"
      fieldComponents={{
        'single-line-text': SingleLineTextField,
      }}
    />
  );
}
```

## Slots

Use `slots` when you want to intercept smaller layout regions without replacing the whole **`Field`** component (the wrapper around each control).

This is useful for wrapping labels, instructions, inputs, or error regions in app-specific markup. Each slot component receives `slotKey`, the `attributes` of the element it wraps, and the `field` it belongs to, so a label slot can check `field.required` to add its own required marker.

## Hooks

These hooks are available inside client-rendered form trees:

- `useFormie()`
- `useFormieField(fieldId)`
- `useFormiePage(pageId)`
- `useFormieInstance()`
- `useFormieSlot(key)`

Use them when your custom React components need form state instead of only render props.

## Custom Actions Example

```tsx
import { useFormie } from '@verbb/formie-react';

export function FormActions() {
  const { state, instance } = useFormie();

  return (
    <div className="flex gap-3">
      <button
        type="button"
        disabled={state.status === 'submitting'}
        onClick={() => {
          void instance.submit('save');
        }}
      >
        Save
      </button>

      <button
        type="button"
        disabled={state.status === 'submitting'}
        onClick={() => {
          void instance.submit('submit');
        }}
      >
        Submit
      </button>
    </div>
  );
}
```
