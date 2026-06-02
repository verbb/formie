# Component customization

## What you can customize

`<FormieClientForm />` supports three main customization layers:

- `components` for top-level `Form`, `Page`, **`Field`**, and error-summary replacements
- `fieldComponents` for field-type-specific rendering (the control only)
- `slots` for smaller layout regions inside the default component tree

## Choose the right layer

- Use `components` for major layout ownership.
- Use `fieldComponents` for type-specific UI replacement.
- Use `slots` for small structural intercepts.
- Use hooks when the component needs direct form state and actions.

## Combined example

Real client-rendered forms often combine more than one layer:

```tsx
import {
  FormieClientForm,
  type FormieFieldProps,
  type FormieFieldComponentProps,
} from '@verbb/formie-react';

function Field({ field, errors, children }: FormieFieldProps) {
  return (
    <div className="starter-component-card">
      {field.label ? <label>{field.label}</label> : null}
      {children}
      {errors.length ? (
        <ul>
          {errors.map((error, index) => <li key={`${field.id}:${index}`}>{error}</li>)}
        </ul>
      ) : null}
    </div>
  );
}

function TextField({ field, value, disabled, setValue }: FormieFieldComponentProps) {
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

## Top-level components

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

## Field components

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

This is useful for wrapping labels, instructions, inputs, or error regions in app-specific markup.

## Hooks

These hooks are available inside client-rendered form trees:

- `useFormie()`
- `useFormieField(fieldId)`
- `useFormiePage(pageId)`
- `useFormieInstance()`
- `useFormieSlot(key)`

Use them when your custom React components need form state instead of only render props.

## Custom actions example

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
