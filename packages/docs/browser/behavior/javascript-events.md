# JavaScript events

JavaScript events are the main extension surface for browser-managed forms. If Formie is already rendering and booting itself on the page, this is usually the first page to reach for: it covers lifecycle hooks, submit flow, field-module events, payment events, and the points where you can adjust behavior before modules initialize. For DOM replacement, manual re-initialization, or SPA-style transitions, pair this page with [Manual initialization](/browser/behavior/manual-initialization).

## Mounted forms

When Formie has mounted a form for you, listen for `formie:mount:after` and use the root element from the event target:

```js
document.addEventListener('formie:mount:after', (event) => {
  const root = event.target;

  if (!(root instanceof Element)) {
    return;
  }

  console.log('Mounted form root:', root);
});
```

If you already know the root element, you can query it directly and work against the DOM:

```js
const root = document.querySelector('[data-formie], [data-formie-form]');

if (root instanceof Element) {
  console.log('Known Formie root:', root);
}
```

If you need imperative instance access rather than DOM events, initialize the form through `formie()` or the [Custom client](/browser/behavior/custom-client) and keep the returned handle in your own application code.

Field and module events also give you a hook to adjust options before Formie initializes browser controls:

```js
document.addEventListener('formie:field:date-picker:before-init', (event) => {
  event.detail.options.altInput = true;
  event.detail.options.altFormat = 'F j, Y';
});
```

## Root Formie events

These are the main top-level events for mounted forms.

### Form lifecycle

#### The `formie:mount:after` event

The event that is triggered after a Formie root has mounted and is ready for browser-side interaction.

```js
document.addEventListener('formie:mount:after', (event) => {
  console.log('Mounted:', event.detail);
});
```

#### The `formie:unmount:before` event

The event that is triggered just before Formie tears down a mounted root and its browser state.

```js
document.addEventListener('formie:unmount:before', (event) => {
  console.log('Unmounting soon:', event.detail);
});
```

#### The `formie:unmount:after` event

The event that is triggered after Formie has fully torn down a mounted root.

```js
document.addEventListener('formie:unmount:after', (event) => {
  console.log('Unmounted:', event.detail);
});
```

#### The `formie:validator:ready` event

The event that is triggered when Formie's validator API is ready for a mounted form. The validator instance is available as `event.detail.validator`.

```js
document.addEventListener('formie:validator:ready', (event) => {
  const { validator } = event.detail;

  validator.addValidator('no-example-domain', ({ input, getRule }) => {
    if (!getRule('no-example-domain') || !input.value) {
      return true;
    }

    return !input.value.endsWith('@example.com');
  }, () => 'Example.com addresses are not allowed.');
});
```

#### The `formie:validator:show-error` event

The event that is triggered when Formie renders a validation error onto one input's field UI.

```js
document.addEventListener('formie:validator:show-error', (event) => {
  console.log('Validation error shown:', event.detail.validatorName, event.detail.errorMessage);
});
```

#### The `formie:validator:clear-error` event

The event that is triggered when Formie clears a previously rendered validation error from one input's field UI.

```js
document.addEventListener('formie:validator:clear-error', (event) => {
  console.log('Validation error cleared:', event.detail);
});
```

#### The `formie:validator:destroy` event

The event that is triggered when Formie tears down the validator for a mounted form.

```js
document.addEventListener('formie:validator:destroy', (event) => {
  console.log('Validator destroyed:', event.detail.validator);
});
```

#### The `formie:theme:applied` event

The event that is triggered after Formie has applied its theme classes and visual state.

```js
document.addEventListener('formie:theme:applied', (event) => {
  console.log('Theme applied:', event.detail);
});
```

### Submit lifecycle

These events cover the submission flow. On multi-page forms, `formie:submit:*` events can run on each page submit attempt, while `formie:submit:final:*` events are reserved for the final submit when the form is actually being completed.

#### The `formie:submit:before` event

The event that is triggered before Formie starts handling a submit attempt.

```js
document.addEventListener('formie:submit:before', (event) => {
  console.log('Submit started:', event.detail);
});
```

#### The `formie:submit:after` event

The event that is triggered after Formie has finished processing a submit attempt.

```js
document.addEventListener('formie:submit:after', (event) => {
  console.log('Submit finished:', event.detail);
});
```

#### The `formie:submit:final:before` event

The event that is triggered before Formie starts the final submit attempt for the whole form.

```js
document.addEventListener('formie:submit:final:before', (event) => {
  console.log('Final submit starting:', event.detail);
});
```

#### The `formie:submit:final:after` event

The event that is triggered after Formie has completed the final submit attempt for the whole form.

```js
document.addEventListener('formie:submit:final:after', (event) => {
  console.log('Final submit finished:', event.detail);
});
```

#### The `formie:submit:result` event

The event that is triggered after Formie has normalized the submit result and applied the resulting state changes.

```js
document.addEventListener('formie:submit:result', (event) => {
  console.log('Submit result:', event.detail);
});
```

### Page lifecycle

These events are triggered around multi-page navigation when Formie is moving between pages.

#### The `formie:page:navigate` event

The event that is triggered before Formie persists state and applies a page change.

```js
document.addEventListener('formie:page:navigate', (event) => {
  console.log('Navigating page:', event.detail);
});
```

#### The `formie:page:navigate:after` event

The event that is triggered after Formie has completed a page change and updated the active page state.

```js
document.addEventListener('formie:page:navigate:after', (event) => {
  console.log('Page navigation complete:', event.detail);
});
```

#### The `formie:page:navigate:error` event

The event that is triggered when Formie cannot complete a page navigation request.

```js
document.addEventListener('formie:page:navigate:error', (event) => {
  console.error('Page navigation failed:', event.detail);
});
```

### Token lifecycle

These events are triggered when Formie refreshes the submission and security tokens it needs for later requests.

#### The `formie:refresh-tokens:after` event

The event that is triggered after Formie has requested refreshed submission and security tokens.

```js
document.addEventListener('formie:refresh-tokens:after', (event) => {
  console.log('Token refresh requested:', event.detail);
});
```

#### The `formie:refresh-tokens:refreshed` event

The event that is triggered after refreshed tokens have been written back into Formie's current form state.

```js
document.addEventListener('formie:refresh-tokens:refreshed', (event) => {
  console.log('Tokens refreshed:', event.detail);
});
```

### State lifecycle

These events are triggered when Formie resets the broader submission state around a form.

#### The `formie:state:reset` event

The event that is triggered after Formie performs a full submission-state reset. This is broader than a native form reset and also clears hidden continuity inputs, resume-token state, page UI, and live validation state.

```js
document.addEventListener('formie:state:reset', (event) => {
  console.log('Formie state reset:', event.detail);
});
```

### Stage lifecycle

These events are emitted for each submit pipeline stage, before and after Formie runs that stage.

#### The `formie:stage:prepare:before` event

The event that is triggered before Formie enters the prepare stage.

```js
document.addEventListener('formie:stage:prepare:before', (event) => {
  console.log('Before prepare:', event.detail);
});
```

#### The `formie:stage:prepare:after` event

The event that is triggered after Formie has completed the prepare stage.

```js
document.addEventListener('formie:stage:prepare:after', (event) => {
  console.log('After prepare:', event.detail);
});
```

#### The `formie:stage:normalize:before` event

The event that is triggered before Formie enters the normalize stage.

```js
document.addEventListener('formie:stage:normalize:before', (event) => {
  console.log('Before normalize:', event.detail);
});
```

#### The `formie:stage:normalize:after` event

The event that is triggered after Formie has completed the normalize stage.

```js
document.addEventListener('formie:stage:normalize:after', (event) => {
  console.log('After normalize:', event.detail);
});
```

#### The `formie:stage:validate:before` event

The event that is triggered before Formie enters the validate stage.

```js
document.addEventListener('formie:stage:validate:before', (event) => {
  console.log('Before validate:', event.detail);
});
```

#### The `formie:stage:validate:after` event

The event that is triggered after Formie has completed the validate stage.

```js
document.addEventListener('formie:stage:validate:after', (event) => {
  console.log('After validate:', event.detail);
});
```

#### The `formie:stage:screen:before` event

The event that is triggered before Formie enters the screening stage.

```js
document.addEventListener('formie:stage:screen:before', (event) => {
  console.log('Before screen:', event.detail);
});
```

#### The `formie:stage:screen:after` event

The event that is triggered after Formie has completed the screening stage.

```js
document.addEventListener('formie:stage:screen:after', (event) => {
  console.log('After screen:', event.detail);
});
```

#### The `formie:stage:authorize:before` event

The event that is triggered before Formie enters the authorization stage.

```js
document.addEventListener('formie:stage:authorize:before', (event) => {
  console.log('Before authorize:', event.detail);
});
```

#### The `formie:stage:authorize:after` event

The event that is triggered after Formie has completed the authorization stage.

```js
document.addEventListener('formie:stage:authorize:after', (event) => {
  console.log('After authorize:', event.detail);
});
```

#### The `formie:stage:dispatch:before` event

The event that is triggered before Formie enters the dispatch stage.

```js
document.addEventListener('formie:stage:dispatch:before', (event) => {
  console.log('Before dispatch:', event.detail);
});
```

#### The `formie:stage:dispatch:after` event

The event that is triggered after Formie has completed the dispatch stage.

```js
document.addEventListener('formie:stage:dispatch:after', (event) => {
  console.log('After dispatch:', event.detail);
});
```

#### The `formie:stage:finalize:before` event

The event that is triggered before Formie enters the finalize stage.

```js
document.addEventListener('formie:stage:finalize:before', (event) => {
  console.log('Before finalize:', event.detail);
});
```

#### The `formie:stage:finalize:after` event

The event that is triggered after Formie has completed the finalize stage.

```js
document.addEventListener('formie:stage:finalize:after', (event) => {
  console.log('After finalize:', event.detail);
});
```

## Field module events

These events are triggered by built-in field modules as they initialize, update, and react to user input.

### `checkbox-radio`

#### The `formie:field:checkbox-radio:init` event

The event that is triggered after the checkbox-radio field module is ready to manage option state in the field.

```js
document.addEventListener('formie:field:checkbox-radio:init', (event) => {
  console.log('Checkbox-radio ready:', event.detail);
});
```

### `calculations`

#### The `formie:field:calculations:before-evaluate` event

The event that is triggered before a calculations field evaluates its formula.

```js
document.addEventListener('formie:field:calculations:before-evaluate', (event) => {
  console.log('Before calculations evaluate:', event.detail);
});
```

#### The `formie:field:calculations:after-evaluate` event

The event that is triggered after a calculations field has finished evaluating its formula.

```js
document.addEventListener('formie:field:calculations:after-evaluate', (event) => {
  console.log('After calculations evaluate:', event.detail);
});
```

### `date-picker`

#### The `formie:field:date-picker:before-init` event

The event that is triggered before the date picker module initializes. Use this to adjust options before the picker is created.

```js
document.addEventListener('formie:field:date-picker:before-init', (event) => {
  event.detail.options.altInput = true;
});
```

#### The `formie:field:date-picker:after-init` event

The event that is triggered after the date picker module has initialized and attached to the field input.

```js
document.addEventListener('formie:field:date-picker:after-init', (event) => {
  console.log('Date picker ready:', event.detail);
});
```

### `file-upload`

#### The `formie:field:file-upload:uploaded-assets-sync` event

The event that is triggered after uploaded assets have been synchronized back into the file upload field state.

```js
document.addEventListener('formie:field:file-upload:uploaded-assets-sync', (event) => {
  console.log('Uploaded assets synced:', event.detail);
});
```

### `phone-country`

#### The `formie:field:phone-country:before-init` event

The event that is triggered before the phone-country module initializes. Use this to adjust configuration before the library attaches.

```js
document.addEventListener('formie:field:phone-country:before-init', (event) => {
  console.log('Before phone-country init:', event.detail);
});
```

#### The `formie:field:phone-country:init` event

The event that is triggered after the phone-country module has initialized and enhanced the field input.

```js
document.addEventListener('formie:field:phone-country:init', (event) => {
  console.log('Phone-country ready:', event.detail);
});
```

### `repeater`

#### The `formie:field:repeater:init` event

The event that is triggered after the repeater module is ready to manage its rows.

```js
document.addEventListener('formie:field:repeater:init', (event) => {
  console.log('Repeater ready:', event.detail);
});
```

#### The `formie:field:repeater:append` event

The event that is triggered after the repeater has appended a new row.

```js
document.addEventListener('formie:field:repeater:append', (event) => {
  console.log('Repeater row appended:', event.detail);
});
```

#### The `formie:field:repeater:init-row` event

The event that is triggered after a newly appended repeater row has finished initializing its nested fields and modules.

```js
document.addEventListener('formie:field:repeater:init-row', (event) => {
  console.log('Repeater row initialized:', event.detail);
});
```

#### The `formie:field:repeater:remove` event

The event that is triggered after the repeater has removed a row.

```js
document.addEventListener('formie:field:repeater:remove', (event) => {
  console.log('Repeater row removed:', event.detail);
});
```

### `rich-text`

#### The `formie:field:rich-text:before-init` event

The event that is triggered before the rich-text module initializes. Use this to adjust editor options before setup.

```js
document.addEventListener('formie:field:rich-text:before-init', (event) => {
  console.log('Before rich-text init:', event.detail);
});
```

#### The `formie:field:rich-text:after-init` event

The event that is triggered after the rich-text module has initialized and attached its editor instance.

```js
document.addEventListener('formie:field:rich-text:after-init', (event) => {
  console.log('Rich-text ready:', event.detail);
});
```

#### The `formie:field:rich-text:populate` event

The event that is triggered when the rich-text module populates or re-populates its editor content.

```js
document.addEventListener('formie:field:rich-text:populate', (event) => {
  console.log('Rich-text populated:', event.detail);
});
```

### `signature`

#### The `formie:field:signature:init` event

The event that is triggered after the signature module has initialized its drawing surface.

```js
document.addEventListener('formie:field:signature:init', (event) => {
  console.log('Signature ready:', event.detail);
});
```

### `summary`

#### The `formie:field:summary:fetch-summary` event

The event that is triggered when the summary field is about to collect the values it will display.

```js
document.addEventListener('formie:field:summary:fetch-summary', (event) => {
  console.log('Fetching summary:', event.detail);
});
```

#### The `formie:field:summary:field-visible` event

The event that is triggered when a field is considered visible enough to be included in summary output.

```js
document.addEventListener('formie:field:summary:field-visible', (event) => {
  console.log('Summary field visible:', event.detail);
});
```

### `table`

#### The `formie:field:table:init` event

The event that is triggered after the table module is ready to manage its rows and controls.

```js
document.addEventListener('formie:field:table:init', (event) => {
  console.log('Table ready:', event.detail);
});
```

#### The `formie:field:table:append` event

The event that is triggered after the table field has appended a new row.

```js
document.addEventListener('formie:field:table:append', (event) => {
  console.log('Table row appended:', event.detail);
});
```

#### The `formie:field:table:remove` event

The event that is triggered after the table field has removed a row.

```js
document.addEventListener('formie:field:table:remove', (event) => {
  console.log('Table row removed:', event.detail);
});
```

## Address provider events

These events are triggered by the active address provider integration for a field.

### `place-kit`

#### The `formie:address:place-kit:before-init` event

The event that is triggered before the PlaceKit address provider initializes. Use this to adjust provider options before setup.

```js
document.addEventListener('formie:address:place-kit:before-init', (event) => {
  console.log('Before PlaceKit init:', event.detail);
});
```

#### The `formie:address:place-kit:populate` event

The event that is triggered when PlaceKit writes selected address data into the field's sub-fields.

```js
document.addEventListener('formie:address:place-kit:populate', (event) => {
  console.log('PlaceKit populated address:', event.detail);
});
```

### `address-finder`

#### The `formie:address:address-finder:populate` event

The event that is triggered when Address Finder writes selected address data into the field's sub-fields.

```js
document.addEventListener('formie:address:address-finder:populate', (event) => {
  console.log('Address Finder populated address:', event.detail);
});
```

### `google`

#### The `formie:address:google:populate` event

The event that is triggered when Google writes selected address data into the field's sub-fields.

```js
document.addEventListener('formie:address:google:populate', (event) => {
  console.log('Google populated address:', event.detail);
});
```

## Payment events

These events cover generic payment authorization flow as well as provider-specific follow-up actions.

### Generic payment authorization events

#### The `formie:payment:authorize:before` event

The event that is triggered before Formie starts the payment authorization flow.

```js
document.addEventListener('formie:payment:authorize:before', (event) => {
  console.log('Before payment authorization:', event.detail);
});
```

#### The `formie:payment:authorize:after` event

The event that is triggered after Formie finishes the payment authorization flow.

```js
document.addEventListener('formie:payment:authorize:after', (event) => {
  console.log('After payment authorization:', event.detail);
});
```

#### The `formie:payment:authorize:error` event

The event that is triggered when the payment authorization flow fails.

```js
document.addEventListener('formie:payment:authorize:error', (event) => {
  console.error('Payment authorization failed:', event.detail);
});
```

#### The `formie:payment:provider-authorize:before` event

The event that is triggered before the active payment provider performs its own authorization step.

```js
document.addEventListener('formie:payment:provider-authorize:before', (event) => {
  console.log('Before provider authorization:', event.detail);
});
```

#### The `formie:payment:provider-authorize:after` event

The event that is triggered after the active payment provider completes its own authorization step.

```js
document.addEventListener('formie:payment:provider-authorize:after', (event) => {
  console.log('After provider authorization:', event.detail);
});
```

#### The `formie:payment:provider-authorize:error` event

The event that is triggered when the active payment provider cannot complete its authorization step.

```js
document.addEventListener('formie:payment:provider-authorize:error', (event) => {
  console.error('Provider authorization failed:', event.detail);
});
```

### Provider action events

#### The `formie:payment:stripe:confirm` event

The event that is triggered when Stripe requires a follow-up confirmation step before the submission can continue.

```js
document.addEventListener('formie:payment:stripe:confirm', (event) => {
  console.log('Stripe confirm required:', event.detail);
});
```

#### The `formie:payment:opayo:challenge` event

The event that is triggered when Opayo requires a challenge step before the submission can continue.

```js
document.addEventListener('formie:payment:opayo:challenge', (event) => {
  console.log('Opayo challenge required:', event.detail);
});
```

#### The `formie:payment:mollie:redirect` event

The event that is triggered when Mollie requires a redirect before the submission can continue.

```js
document.addEventListener('formie:payment:mollie:redirect', (event) => {
  console.log('Mollie redirect required:', event.detail);
});
```

#### The `formie:payment:go-cardless:redirect` event

The event that is triggered when GoCardless requires a redirect before the submission can continue.

```js
document.addEventListener('formie:payment:go-cardless:redirect', (event) => {
  console.log('GoCardless redirect required:', event.detail);
});
```

#### The `formie:payment:paddle:initialize` event

The event that is triggered when Paddle requires an initialization step before the submission can continue.

```js
document.addEventListener('formie:payment:paddle:initialize', (event) => {
  console.log('Paddle initialize required:', event.detail);
});
```

## Module lifecycle events

These events are triggered around module setup, teardown, and built-in module availability.

### Generic lifecycle events

#### The `formie:module:before-setup` event

The event that is triggered before Formie sets up any module.

```js
document.addEventListener('formie:module:before-setup', (event) => {
  console.log('Before module setup:', event.detail);
});
```

#### The `formie:module:after-setup` event

The event that is triggered after Formie has finished setting up a module.

```js
document.addEventListener('formie:module:after-setup', (event) => {
  console.log('After module setup:', event.detail);
});
```

#### The `formie:module:before-destroy` event

The event that is triggered before Formie starts tearing down a module.

```js
document.addEventListener('formie:module:before-destroy', (event) => {
  console.log('Before module destroy:', event.detail);
});
```

#### The `formie:module:after-destroy` event

The event that is triggered after Formie has finished tearing down a module.

```js
document.addEventListener('formie:module:after-destroy', (event) => {
  console.log('After module destroy:', event.detail);
});
```

### Scoped lifecycle events

Scoped lifecycle events are available for any module id. The examples below use the `date-picker` module to show the event shape.

#### The `formie:module:date-picker:before-setup` event

The event that is triggered before the date-picker module is set up. Use this pattern with any module id you want to target.

```js
document.addEventListener('formie:module:date-picker:before-setup', (event) => {
  console.log('Before date-picker setup:', event.detail);
});
```

#### The `formie:module:date-picker:after-setup` event

The event that is triggered after the date-picker module has been set up and made available on the field.

```js
document.addEventListener('formie:module:date-picker:after-setup', (event) => {
  console.log('After date-picker setup:', event.detail);
});
```

#### The `formie:module:date-picker:before-destroy` event

The event that is triggered before the date-picker module is destroyed and detached from the field.

```js
document.addEventListener('formie:module:date-picker:before-destroy', (event) => {
  console.log('Before date-picker destroy:', event.detail);
});
```

#### The `formie:module:date-picker:after-destroy` event

The event that is triggered after the date-picker module has been destroyed and cleaned up.

```js
document.addEventListener('formie:module:date-picker:after-destroy', (event) => {
  console.log('After date-picker destroy:', event.detail);
});
```

### Module init and destroy events currently emitted by built-in modules

These events are currently emitted by built-in modules. The examples start with `checkbox-radio`, but the same pattern applies across the built-in module ids listed in this section.

#### The `formie:module:checkbox-radio:init` event

The event that is triggered after the checkbox-radio module has initialized and is ready to manage the field.

```js
document.addEventListener('formie:module:checkbox-radio:init', (event) => {
  console.log('Checkbox-radio module init:', event.detail);
});
```

#### The `formie:module:checkbox-radio:destroy` event

The event that is triggered after the checkbox-radio module has been destroyed and cleaned up.

```js
document.addEventListener('formie:module:checkbox-radio:destroy', (event) => {
  console.log('Checkbox-radio module destroy:', event.detail);
});
```

#### The `formie:module:calculations:init` event

The event that is triggered after the calculations module has initialized and is ready to evaluate values.

```js
document.addEventListener('formie:module:calculations:init', (event) => {
  console.log('Calculations module init:', event.detail);
});
```

#### The `formie:module:calculations:destroy` event

The event that is triggered after the calculations module has been destroyed and cleaned up.

```js
document.addEventListener('formie:module:calculations:destroy', (event) => {
  console.log('Calculations module destroy:', event.detail);
});
```

#### The `formie:module:conditions:init` event

The event that is triggered after the conditions module has initialized and is ready to evaluate visibility rules.

```js
document.addEventListener('formie:module:conditions:init', (event) => {
  console.log('Conditions module init:', event.detail);
});
```

#### The `formie:module:conditions:destroy` event

The event that is triggered after the conditions module has been destroyed and cleaned up.

```js
document.addEventListener('formie:module:conditions:destroy', (event) => {
  console.log('Conditions module destroy:', event.detail);
});
```

#### The `formie:module:date-picker:init` event

The event that is triggered after the date-picker module has initialized and attached its picker instance.

```js
document.addEventListener('formie:module:date-picker:init', (event) => {
  console.log('Date-picker module init:', event.detail);
});
```

#### The `formie:module:date-picker:destroy` event

The event that is triggered after the date-picker module has been destroyed and cleaned up.

```js
document.addEventListener('formie:module:date-picker:destroy', (event) => {
  console.log('Date-picker module destroy:', event.detail);
});
```

#### The `formie:module:file-upload:init` event

The event that is triggered after the file-upload module has initialized and is ready to manage uploads.

```js
document.addEventListener('formie:module:file-upload:init', (event) => {
  console.log('File-upload module init:', event.detail);
});
```

#### The `formie:module:file-upload:destroy` event

The event that is triggered after the file-upload module has been destroyed and cleaned up.

```js
document.addEventListener('formie:module:file-upload:destroy', (event) => {
  console.log('File-upload module destroy:', event.detail);
});
```

#### The `formie:module:hidden:init` event

The event that is triggered after the hidden module has initialized and applied its field behavior.

```js
document.addEventListener('formie:module:hidden:init', (event) => {
  console.log('Hidden module init:', event.detail);
});
```

#### The `formie:module:hidden:destroy` event

The event that is triggered after the hidden module has been destroyed and cleaned up.

```js
document.addEventListener('formie:module:hidden:destroy', (event) => {
  console.log('Hidden module destroy:', event.detail);
});
```

#### The `formie:module:payment:destroy` event

The event that is triggered after the payment module has been destroyed and its provider state has been cleaned up.

```js
document.addEventListener('formie:module:payment:destroy', (event) => {
  console.log('Payment module destroy:', event.detail);
});
```

#### The `formie:module:phone-country:init` event

The event that is triggered after the phone-country module has initialized and enhanced the field input.

```js
document.addEventListener('formie:module:phone-country:init', (event) => {
  console.log('Phone-country module init:', event.detail);
});
```

#### The `formie:module:phone-country:destroy` event

The event that is triggered after the phone-country module has been destroyed and cleaned up.

```js
document.addEventListener('formie:module:phone-country:destroy', (event) => {
  console.log('Phone-country module destroy:', event.detail);
});
```

#### The `formie:module:repeater:init` event

The event that is triggered after the repeater module has initialized and is ready to manage its rows.

```js
document.addEventListener('formie:module:repeater:init', (event) => {
  console.log('Repeater module init:', event.detail);
});
```

#### The `formie:module:repeater:destroy` event

The event that is triggered after the repeater module has been destroyed and cleaned up.

```js
document.addEventListener('formie:module:repeater:destroy', (event) => {
  console.log('Repeater module destroy:', event.detail);
});
```

#### The `formie:module:rich-text:init` event

The event that is triggered after the rich-text module has initialized and attached its editor instance.

```js
document.addEventListener('formie:module:rich-text:init', (event) => {
  console.log('Rich-text module init:', event.detail);
});
```

#### The `formie:module:rich-text:destroy` event

The event that is triggered after the rich-text module has been destroyed and cleaned up.

```js
document.addEventListener('formie:module:rich-text:destroy', (event) => {
  console.log('Rich-text module destroy:', event.detail);
});
```

#### The `formie:module:signature:init` event

The event that is triggered after the signature module has initialized and attached its drawing surface.

```js
document.addEventListener('formie:module:signature:init', (event) => {
  console.log('Signature module init:', event.detail);
});
```

#### The `formie:module:signature:destroy` event

The event that is triggered after the signature module has been destroyed and cleaned up.

```js
document.addEventListener('formie:module:signature:destroy', (event) => {
  console.log('Signature module destroy:', event.detail);
});
```

#### The `formie:module:summary:init` event

The event that is triggered after the summary module has initialized and is ready to collect summary values.

```js
document.addEventListener('formie:module:summary:init', (event) => {
  console.log('Summary module init:', event.detail);
});
```

#### The `formie:module:summary:destroy` event

The event that is triggered after the summary module has been destroyed and cleaned up.

```js
document.addEventListener('formie:module:summary:destroy', (event) => {
  console.log('Summary module destroy:', event.detail);
});
```

#### The `formie:module:table:init` event

The event that is triggered after the table module has initialized and is ready to manage its rows.

```js
document.addEventListener('formie:module:table:init', (event) => {
  console.log('Table module init:', event.detail);
});
```

#### The `formie:module:table:destroy` event

The event that is triggered after the table module has been destroyed and cleaned up.

```js
document.addEventListener('formie:module:table:destroy', (event) => {
  console.log('Table module destroy:', event.detail);
});
```

#### The `formie:module:text-limit:init` event

The event that is triggered after the text-limit module has initialized and is ready to track remaining characters.

```js
document.addEventListener('formie:module:text-limit:init', (event) => {
  console.log('Text-limit module init:', event.detail);
});
```

#### The `formie:module:text-limit:destroy` event

The event that is triggered after the text-limit module has been destroyed and cleaned up.

```js
document.addEventListener('formie:module:text-limit:destroy', (event) => {
  console.log('Text-limit module destroy:', event.detail);
});
```

Additional payment provider modules also emit provider-specific module init and destroy events when present in the manifest.

#### The `formie:module:stripe:init` event

The event that is triggered after the Stripe module has initialized and is ready to manage provider-specific UI.

```js
document.addEventListener('formie:module:stripe:init', (event) => {
  console.log('Stripe module init:', event.detail);
});
```

#### The `formie:module:stripe:destroy` event

The event that is triggered after the Stripe module has been destroyed and cleaned up.

```js
document.addEventListener('formie:module:stripe:destroy', (event) => {
  console.log('Stripe module destroy:', event.detail);
});
```

#### The `formie:module:paypal:init` event

The event that is triggered after the PayPal module has initialized and is ready to manage provider-specific UI.

```js
document.addEventListener('formie:module:paypal:init', (event) => {
  console.log('PayPal module init:', event.detail);
});
```

#### The `formie:module:paypal:destroy` event

The event that is triggered after the PayPal module has been destroyed and cleaned up.

```js
document.addEventListener('formie:module:paypal:destroy', (event) => {
  console.log('PayPal module destroy:', event.detail);
});
```

## Conditions event

#### The `formie:conditions:evaluated` event

The event that is triggered after Formie has evaluated conditional logic for a field or form-state change.

```js
document.addEventListener('formie:conditions:evaluated', (event) => {
  console.log('Conditions evaluated:', event.detail);
});
```

## Related pages

- [Validation](/browser/validation/)
- [Modules](/browser/modules/)
- [JavaScript API](/browser/)
- [Manual initialization](/browser/behavior/manual-initialization)
- [Submission handling](/browser/behavior/submission-handling)
