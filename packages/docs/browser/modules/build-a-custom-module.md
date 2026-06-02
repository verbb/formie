# Build a custom module

Build a custom module when your field or browser enhancement needs to hook into Formie's lifecycle instead of living as an unrelated page script.

This is the right fit when you need:

- setup when a form mounts
- teardown when a form unmounts
- lazy loading from the form manifest
- per-field options without hard-coding page logic

This example shows a custom `project-rating` module for a custom field.

For custom modules, the public loading path is always `src`. Manifest entries without `src` are for Formie's own built-in modules, not for consumer-authored modules.

## Example field markup

The field still uses a normal Formie transport input for submission, but adds a couple of custom selectors for the module:

```html
<div class="formie-field" data-formie-field-handle="projectRating">
  <label class="formie-label" for="projectRating">Project rating</label>

  <div data-project-rating-field>
    <input
      id="projectRating"
      name="fields[projectRating]"
      type="range"
      min="1"
      max="10"
      value="5"
      data-formie-input
      data-formie-input-id="projectRating"
      data-project-rating-input
    />

    <output data-project-rating-value>5 / 10</output>
  </div>
</div>
```

## 1. Write the module definition

```ts
import type { FormieModuleDefinition } from '@verbb/formie-browser';

const INPUT_SELECTOR = '[data-project-rating-input]';
const OUTPUT_SELECTOR = '[data-project-rating-value]';

const projectRatingModule: FormieModuleDefinition = {
  id: 'project-rating',
  kind: 'field',

  match({ scope, target }) {
    return scope === 'field'
      && target instanceof HTMLElement
      && !!target.querySelector(INPUT_SELECTOR)
      && !!target.querySelector(OUTPUT_SELECTOR);
  },

  async setup({ target, options }) {
    if (!(target instanceof HTMLElement)) {
      return;
    }

    const input = target.querySelector(INPUT_SELECTOR);
    const output = target.querySelector(OUTPUT_SELECTOR);

    if (!(input instanceof HTMLInputElement) || !(output instanceof HTMLOutputElement)) {
      return;
    }

    const max = String(options?.max || input.max || '10');

    const sync = () => {
      const label = `${input.value} / ${max}`;

      output.value = label;
      output.textContent = label;
    };

    input.addEventListener('input', sync);
    sync();

    return {
      destroy() {
        input.removeEventListener('input', sync);
      },
    };
  },
};

export default projectRatingModule;
```

What this does:

- `id` gives the module a stable manifest key
- `kind: 'field'` marks it as a field-scoped module
- `match()` confirms the target contains the selectors the module needs
- `setup()` attaches behavior and returns `destroy()` cleanup

## Choose the right module shape

For custom field modules and core workflow modules, author a `FormieModuleDefinition` directly.

For provider modules, use the helper that matches the provider family:

- `defineCaptchaModule()` or `definePassiveCaptchaModule()`
- `definePaymentModule()`
- `defineAddressModule()`

## Core workflow modules

`kind: 'core'` modules attach to the form as a whole instead of one field or provider target.

Use them when you need to participate in submit stages such as screening, pre-submit checks, or post-stage follow-up work:

```ts
import type { FormieModuleDefinition } from '@verbb/formie-browser';

const acmeCoreModule: FormieModuleDefinition = {
  id: 'acme.core-module',
  kind: 'core',
  match: () => true,

  async setup() {
    return {
      destroy() {},

      async onBeforeStage(stageCtx) {
        if (stageCtx.stage !== 'screen' || stageCtx.action !== 'submit') {
          return;
        }

        const allowed = true;

        if (!allowed) {
          stageCtx.abort('Screening failed.');
        }
      },
    };
  },
};
```

Core modules are the right place for form-level behavior that does not belong to one specific field, captcha, payment, or address target.

## 2. Reference it from the form manifest

Point the manifest item at the module file with `src` so Formie can import it lazily.

### Direct markup

```html
<form
  class="formie-form"
  data-formie
  data-formie-form
  data-formie-modules='[
    {
      "id": "project-rating",
      "type": "field",
      "targets": [
        {
          "targetType": "field",
          "targetId": "projectRating"
        }
      ],
      "src": "/assets/formie/project-rating-module.js",
      "config": {
        "max": 10
      }
    }
  ]'
>
  ...
</form>
```

The important bit is `targetId: "projectRating"`, which matches the field's `data-formie-field-handle`.

### Custom PHP field

If you are building a custom field in PHP, add the client module from `defineClientModules()`. That is what feeds the form's `data-formie-modules` manifest.

```php
protected function defineClientModules(): array
{
    $modules = parent::defineClientModules();
    $modules[] = $this->makeClientModule('project-rating', [
        'max' => 10,
    ], '/assets/formie/project-rating-module.js');

    return $modules;
}
```

This is the same PHP hook Formie uses for its own field modules, but for consumer-authored modules the important difference is the third argument: your public module file path in `src`.

## 3. Listen to module lifecycle events while developing

```js
document.addEventListener('formie:module:project-rating:after-setup', (event) => {
  // Confirm the module attached to the field you expected.
  console.log('Project rating ready:', event.detail.target);
});

document.addEventListener('formie:module:project-rating:after-destroy', (event) => {
  // Helpful when debugging modal closes or page replacement.
  console.log('Project rating destroyed:', event.detail.target);
});
```

Use [JavaScript events](/browser/behavior/javascript-events) for the full lifecycle event reference.

## Tips

- Use unique ids. `vendor.feature` is a good pattern for third-party modules.
- Keep `match()` cheap and deterministic.
- Prefer `target` as the module's ownership boundary. Reach up to `root` only when you genuinely need cross-form coordination.
- Keep your module selectors scoped inside the field or target it owns.
- Always remove listeners, observers, and timers in `destroy()`.
- Inside modules, prefer `on('formie:...')` and `emit('formie:...')`. Outside modules, use DOM events from [JavaScript events](/browser/behavior/javascript-events).
- Use `config` for per-form configuration instead of hard-coding values.
- For custom modules, always provide `src`.
- Keep submitted values in normal Formie inputs when the field still posts data.

## Related pages

- [Modules](/browser/modules/)
- [JavaScript API](/browser/)
- [JavaScript events](/browser/behavior/javascript-events)
- [Multi Line Text](/browser/ui-reference/fields/multi-line-text)
