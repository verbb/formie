# JavaScript API
In addition to being "hands-off", Formie's JavaScript can be interacted with for all manner of advanced scenarios, such as lazy-loading, async-loading, module importing in Vue.js, React.js, and more.

:::tip
Be sure to read up on the general [JavaScript docs](docs:developers/front-end-js) first before proceeding.
:::

## Form Config
All configuration needed to initialise a form is contained within the `data-fui-form` attribute of a `<form>` element. This also contains any per-form fields, captchas or other JavaScript it needs to function. This is a JSON-encoded string.

:::warning
If you're using custom templates, be sure to keep this attribute on the `<form>` element - otherwise your JavaScript will fail to work.
:::

```twig
<form data-fui-form='{"formId":1,"formHandle":"someForm","registeredJs":[],...'>
    // ...
```

## JavaScript Architecture
Let's start with an explanation of how Formie's JavaScript is put together. When the page is loaded, a single `formie.js` file is rendered:

```
<script src="/cpresources/b627be78/formie.js?v=1596945815" defer></script>
```

This contains everything Formie needs to get started, including the [Form Base](docs:developers/front-end-js) and [Form Theme](docs:developers/front-end-js). It's also only included once on a page, even if there are multiple forms on a single page.

:::tip
We're loading using `defer` to ensure loading doesn't block the page render. Be sure to look at the `onFormieInit` event if you want to wait until this file has been loaded.
:::

### Form Factory
The `formie.js` has a main entry point to hold a collection of forms. Commonly, this is for a single page, but if your project uses an SPA or similar architecture, this collection would be for the entire session, as the user navigates your site. We call this the Form Factory, where you can get instances of forms for a given page.

You can access this factory a number of ways:

```js
document.addEventListener('onFormieInit', (e) => {
    // Fetch the Form Factory once it's been loaded
    let Formie = e.detail.formie;

    // Get an already-initialised form by DOM node
    let $form = document.getElementById("formie-form-1");
    let form = Formie.getForm($form);

    // Get an already-initialised form by its ID
    let form = Formie.getFormById('1');

    // Get an already-initialised form by its handle
    let form = Formie.getFormByHandle('myForm');
});
```

:::tip
Note how we're using the `onFormieInit` event. This is because the `formie.js` is loaded with `defer` - so it may not be available to use straight away.
:::

When `formie.js` is fetched in the browser, all forms on a page are automatically initialised, as soon as the DOM is ready. As such, you can access them with the above methods of your choosing.

Property | Description
--- | ---
`$forms` | A collection of DOM nodes, for the current page.
`forms` | A collection of `FormieFormBase` classes.

Functions | Description
--- | ---
`initForms()` | Will initialise all forms on the page with the attribute `data-fui-form`. This is done automatically when the `formie.js` script is loaded.
`initForm($form)` | From a provided `<form>` DOM element, initialises the form.
`getForm($form)` | Returns a registered form, for a provided DOM element.
`getFormById(id)` | Returns a registered form, for a provided ID.
`getFormByHandle(handle)` | Returns a registered form, for a provided handle.
`destroyForm($form)` | Destroys a form, for a provided DOM element.

### Form Base
Once you have a form instance from the Form Factory, you're actually dealing with a `FormieFormBase` JS class. This contains all event-handlers for a form, as well as creating a `FormieFormTheme` JS class.

You can access this class via:

```js
let $form = document.getElementById("formie-form-1");

// This is the `FormieFormTheme` class.
let form = $form.form;
```

Consult the [JS Class](https://github.com/verbb/formie/blob/craft-4/src/web/assets/frontend/src/js/formie-form-base.js) for more on what you have access to.

### Form Theme
Accessible from the `FormieFormBase` class, this contains the bulk "business logic" of Formie's JS, including validation, mutli-page handling and more.

```js
let $form = document.getElementById("formie-form-1");

// This is the `FormieFormBase` class.
let form = $form.form.formTheme;
```

Consult the [JS Class](https://github.com/verbb/formie/blob/craft-4/src/web/assets/frontend/src/js/formie-form-theme.js) for more on what you have access to.

### Additional JS
For some forms, they might contain additional fields, like a Repeater, or a captcha like reCAPTCHA. These have critical JavaScript associated with them in order for them to function. Rather than bundle this JS into the `formie.js` code for every form, they are lazy-loaded only if the form contains this module. This can be seen in the `registeredJs` property in the Form Config.

For this example, you would see an initial network request to fetch `formie.js`. Once loaded, it will load `repeater.js` and `recaptcha-v3.js` (also via `defer`) to initialise these extra items in your form. 

:::tip
If you're developing a custom field, check out the "JavaScript for Custom Fields" section for how to integrate your own JS for a field's front-end.
:::


## Importing via Modules
You may also wish to bundle Formie's JavaScript as part of your own application code. This can have numerous benefits, from controlling the overall payload size, to custom initialisation. Let's look at an example in Vue.js

```js
import Vue from 'vue';
import { Formie } from '../../../vendor/verbb/formie/src/web/assets/frontend/src/js/formie-lib';

new Vue({
    el: '#app',

    data() {
        return {
            FormieInstance: null,
        }
    },

    mounted() {
        var $form = document.getElementById("formie-form-1");

        // Create an instance of our Formie JS library - the "factory"
        this.FormieInstance = new Formie();

        // Initialise a form from its DOM element
        this.FormieInstance.initForm($form);
    },

    methods: {
        destroyForm() {
            var $form = document.getElementById("formie-form-1010");

            this.FormieInstance.destroyForm($form)
        }
    },
});
```

Stepping through this code, we are first importing Formie from the Verbb vendor folder. The exact path to this will depend on your project's file setup.

Next, we create a pretty standard Vue component, and mount it to the `#app` element on our page. In the `mounted()` function (when the DOM is ready), we look up the form we want, via its id `formie-form-1`. You can use whatever selector you like to fetch the form element, but it must return the `<form>` element.

Then, we create a new instance of the Formie "factory", which is used to hold all instances of all forms for your app. As such, it's a good idea to store this for re-use, like we've done as a `data` variable. We then want to initialise the form, by passing it in the DOM node for the form. Once initialised, Formie's JS will kick-in, and load any necessary additional JS files, as the form may require depending on its fields.

You'll also see that we can destroy a form. This will destroy all lazy-loaded JavaScript, and any event-listeners the form has used.

## Async Modules
It's common to have your project's JavaScript loaded asynchronously, particularly if you have large libraries. We'll take a look at a caveat when using Vue.js, but is also applicable to other frameworks, such as React.js

Here's an example Vue component:

```js
const main = async () => {
    // Async load the vue module
    const { default: Vue } = await import(/* webpackChunkName: "vue" */ 'vue');

    // Create our vue instance
    const vm = new Vue({
        el: "#app",
    });

    return vm;
};

// Execute async function
main().then((vm) => {});
```

In the above, we're creating a Vue component, and binding it to the `#app` DOM element for our page. The trick with this solution is that we're async-loading modules, like Vue (and potentially others), for performance benefits. This is great for overall page speed, however, it leads to a bottleneck with Formie's JS.

Formie's JS will very likely initialise all forms on a page, before this code runs. Even if it happens to load the forms, it's not really guaranteed that a race condition won't appear later on in development. Under the hood, Formie initialised all forms on page just fine, but then Vue will kick in to create its virtual DOM. Formie will have the submit event bound to a DOM element that is completely separate to the virtual DOM Vue has built.

To get around this, we need to initialise the forms on a page _after_ Vue has mounted. To do this, we can include `initForms()` once Vue has been mounted:

```js
const vm = new Vue({
    el: "#app",

    mounted() {
        if (window.Formie) {
            window.Formie.initForms();
        }
    },
});
```

This means Formie will initialise forms once Vue has been loaded, and the DOM is ready.


## JavaScript Events
Formie's JavaScript provides a number of event hooks for the form and fields, which you can hook into in your own JS files.

### The `onFormieInit` event

Because Formie's JavaScript is loaded with `defer`, this means that regardless of its placement on a page, it won't block rendering, which is great for performance. However, this proves an issue when you want to interact with Formie's JS, as your code needs to ensure Formie's JS has loaded. 

In this scenario, you should listen to the `onFormieInit` event, which is fired when Formie's JS has been loaded.

:::code
```js JavaScript
document.addEventListener('onFormieInit', (e) => {
    let Formie = e.detail.formie;

    // ...
});
```

```js jQuery
$(document).on('onFormieInit', function(e) {
    let Formie = e.detail.formie;

    // ...
});
```
:::



Our JS hijacks the native submit handler of a form, and wraps it in a number of custom events that give you more fine-grained control over the flow of the form submission. This is used mostly for validation, and captcha support, but you can make use of these for your own needs.

### The `onBeforeFormieSubmit` event
The event that is triggered before a form is submitted, and before validation is triggered. You can cancel a submission by using `preventDefault()`.

:::code
```js JavaScript
let $form = document.querySelector('#formie-form-1');
$form.addEventListener('onBeforeFormieSubmit', (e) => {
    e.preventDefault();

    // ...
});
```

```js jQuery
$('#formie-form-1').on('onBeforeFormieSubmit', function(e) {
    e.preventDefault();

    // ...
});
```
:::



### The `onFormieCaptchaValidate` event
The event that is triggered before a form is submitted, and before the validation is triggered. This event is specifically for captchas, triggered before client-side validation runs. You can cancel a submission by using `preventDefault()`.

:::code
```js JavaScript
let $form = document.querySelector('#formie-form-1');
$form.addEventListener('onFormieCaptchaValidate', (e) => {
    e.preventDefault();

    let submitHandler = e.detail.submitHandler;
    // ...
});
```

```js jQuery
$('#formie-form-1').on('onFormieCaptchaValidate', function(e) {
    e.preventDefault();

    let submitHandler = e.detail.submitHandler;
    // ...
});
```
:::



### The `onFormieValidate` event
The event that is triggered before a form is submitted, but after validation is triggered. You can use this event to handle custom validation. You can cancel a submission by using `preventDefault()`.

:::code
```js JavaScript
let $form = document.querySelector('#formie-form-1');
$form.addEventListener('onFormieValidate', (e) => {
    e.preventDefault();

    let submitHandler = e.detail.submitHandler;
    // ...
});
```

```js jQuery
$('#formie-form-1').on('onFormieValidate', function(e) {
    e.preventDefault();

    let submitHandler = e.detail.submitHandler;
    // ...
});
```
:::



### The `onAfterFormieValidate` event
The event that is triggered before a form is submitted, after validation is triggered and after `onFormieValidate`. Like the `onFormieValidate` event, you can also use this to handle custom validation, if for some reason you prefer it to happen after all other validation events have been triggered. You can cancel a submission by using `preventDefault()`.

:::code
```js JavaScript
let $form = document.querySelector('#formie-form-1');
$form.addEventListener('onAfterFormieValidate', (e) => {
    e.preventDefault();

    let submitHandler = e.detail.submitHandler;
    // ...
});
```

```js jQuery
$('#formie-form-1').on('onAfterFormieValidate', function(e) {
    e.preventDefault();

    let submitHandler = e.detail.submitHandler;
    // ...
});
```
:::



### The `onAfterFormieSubmit` event
The event that is triggered after a form is submitted.

:::code
```js JavaScript
let $form = document.querySelector('#formie-form-1');
$form.addEventListener('onAfterFormieSubmit', (e) => {
    e.preventDefault();

    let data = e.detail;
    // ...
});
```

```js jQuery
$('#formie-form-1').on('onAfterFormieSubmit', function(e) {
    e.preventDefault();

    let data = e.detail;
    // ...
});
```
:::



### The `onFormieSubmitError` event
The event that is triggered if an error on submission is detected. This can also be called manually through `formSubmitError()`.

:::code
```js JavaScript
let $form = document.querySelector('#formie-form-1');
$form.addEventListener('onFormieSubmitError', (e) => {
    e.preventDefault();

    // ...
});
```

```js jQuery
$('#formie-form-1').on('onFormieSubmitError', function(e) {
    e.preventDefault();

    // ...
});
```
:::


## Submit Handling
You may notice the above event's use `e.detail.submitHandler`. This contains a reference to the `FormieBaseForm` JS class we use to house this functionality. Through this, you can call a number of methods on a form to trigger different actions.

## Methods

Method | Description
--- | ---
`submitForm()` | Submits the form, and fires the `onFormieSubmit` event.
`formAfterSubmit()` | Fires the `onAfterFormieSubmit` event.
`formSubmitError()` | Fires the `onFormieSubmitError` event.

In practice, what these events allow you to do is stop form submission, handle your business logic, then either manually trigger the form's submission, or throw an error. For example:

```js
let $form = document.querySelector('#formie-form-1');
let submitHandler = null;

// Setup our event listeners
$form.addEventListener('onBeforeFormieSubmit', onBeforeSubmit);
$form.addEventListener('onFormieValidate', onValidate);

function onBeforeSubmit(e) {
    // Save for later to trigger real submit
    submitHandler = e.detail.submitHandler;
}

function onValidate(e) {
    // Prevent the form from submitting while we check some things
    e.preventDefault();

    // Some custom validation logic...
    if (invalid) {
        // Show that the form is invalid
        submitHandler.formSubmitError();
    } else {
        // Otherwise, tell Formie to submit the form. Because we have stopped the process,
        // we need to manually start it back up again.
        submitHandler.submitForm();
    }
}
```

## Theme JS Events
The [Theme JS](docs:developers/front-end-js) file provides some event hooks for the form.

### The `registerFormieValidation` event
The event that is triggered to register or modify validation messages and rules.

```js
function customRule() {
    return {
        myRule(field) {
            return someLogic;
        },
    };
}

function customMessage() {
    return {
        myRule(field) {
            return t('This is a custom rule {value}.', {
                value: field.getAttribute('data-value'),
            });
        },
    };
}

let $form = document.querySelector('#formie-form-1');
$form.addEventListener('registerFormieValidation', (e) => {
    e.preventDefault();

    // Add our custom validations logic and methods
    e.detail.validatorSettings.customValidations = {
        ...e.detail.validatorSettings.customValidations,
        ...this.customRule(),
    };

    // Add our custom messages
    e.detail.validatorSettings.messages = {
        ...e.detail.validatorSettings.messages,
        ...this.customMessage(),
    };

    // ...
});

// jQuery
$('#formie-form-1').on('registerFormieValidation', function(e) {
    e.preventDefault();

    // ...
});
```


## Conditions
You can also hook into events that are triggered before and after conditional logic has been triggered for a field. This is useful in particular to be notified when a field has been conditionally hidden or shown, or to add additional handling before evaulating conditions.

### The `onFormieEvaluateConditions` event
The event that is triggered when a field with conditions is about to be evaluated. This will also fire on page load, as conditions need to be evaluated immediately, to determine if a field should be shown or hidden.

:::code
```js JavaScript
const $form = document.querySelector('#formie-form-1');
const $myField = $form.querySelector('[data-field-handle="myFieldHandle"]');

$myField.addEventListener('onFormieEvaluateConditions', (e) => {
    const isInit = e.detail.init;

    // ...
});
```

```js jQuery
$('#formie-form-1 [data-field-handle="myFieldHandle"]').on('onFormieEvaluateConditions', function(e) {
    const isInit = e.detail.init;

    // ...
});
```
:::

### The `onAfterFormieEvaluateConditions` event
The event that is triggered when a field with conditions has been evaluated. This will also fire on page load, as conditions need to be evaluated immediately, to determine if a field should be shown or hidden.

:::code
```js JavaScript
const $form = document.querySelector('#formie-form-1');
const $myField = $form.querySelector('[data-field-handle="myFieldHandle"]');

$myField.addEventListener('onAfterFormieEvaluateConditions', (e) => {
    const isInit = e.detail.init;
    const conditionallyHidden = e.target.conditionallyHidden;

    // ...
});
```

```js jQuery
$('#formie-form-1 [data-field-handle="myFieldHandle"]').on('onAfterFormieEvaluateConditions', function(e) {
    const isInit = e.detail.init;
    const conditionallyHidden = e.target.conditionallyHidden;

    // ...
});
```
:::


## JavaScript for Custom Fields
If your custom field, or integration requires JavaScript on the front-end, you'll need to provide it in a specific way. This is so Formie can correctly place and initialise it, for the variety of different scenarios and use-cases required.

For example, let's look at the Repeater Field, which has the following function:

```php
public function getFrontEndJs(Form $form)
{
    $src = Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/frontend/dist/js/fields/repeater.js', true);
    $onload = 'new FormieRepeater(' . Json::encode(['formId' => $form->id]) . ');';

    return [
        'src' => $src,
        'onload' => $onload,
    ];
}
```

The `getFrontEndJs()` should return an array. The `src` key should provide the full URL to the JS asset containing the main code for your field - if required. The `onload` key should provide JavaScript code that is executed once the JS file has been loaded.

The above shows the `repeater.js` file needs to be loaded. This contains a `FormieRepeater` JS class that contains all functionality required to make the Repeater field work. We also need to provide a means to actually initialise this class, through the `onload function`. We're also passing some options to the constructor of this JS class.

This content is then lazy-loaded once the `formie.js` factory has loaded, and the form is initialised.

Similarly, for an integration like a Captcha, it looks much the same:

```php
public function getFrontEndJs(Form $form)
{
    $settings = [
        'siteKey' => $this->settings['siteKey'],
        ...
    ];
    
    $src = Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/captchas/dist/js/recaptcha-v3.js', true);
    $onload = 'new FormieRecaptchaV3(' . Json::encode($settings) . ');';

    return [
        'src' => $src,
        'onload' => $onload,
    ];
}
```

### Calculations Fields

### The `beforeEvaluate` event
The event that is triggered before evaluating the formula.

```js
// Fetch the Calculations field we want to format values for, a field with the handle `result`
const $field = document.querySelector('[data-field-handle="result"]');

// Listen to every time the formula is evaluated
$field.addEventListener('beforeEvaluate', function(e) {
    const formula = e.detail.formula;
    const variables = e.detail.variables;

    // Modify the variables before they're evaluation
    e.detail.variables.field_myField = 1234;
});
```

### The `afterEvaluate` event
The event that is triggered after evaluating the formula.

```js
// Fetch the Calculations field we want to format values for, a field with the handle `result`
const $field = document.querySelector('[data-field-handle="result"]');

// Listen to every time the formula is evaluated
$field.addEventListener('afterEvaluate', function(e) {
    const formatter = new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    });
    
    // Modify the result shown in the field
    e.detail.result = formatter.format(e.detail.result);
});
```



### Date Fields
When using the Date Picker option for date fields, you can access [Flatpickr](https://flatpickr.js.org/) settings, and modify them through JavaScript.

### The `beforeInit` event
The event that is triggered before the Flatpickr date picker is initialized.

```js
// Fetch all Date fields - specifically the input. Events are bound on the input element
let $fields = document.querySelectorAll('.fui-type-date-time input');

// For each field, bind on the `beforeInit` event
$fields.forEach($field => {
    $field.addEventListener('beforeInit', (e) => {
        let datePickerField = e.detail.datepicker;
        let options = e.detail.options;

        // Modify any Flatpickr options
        e.detail.options.minDate = '2021-06-03';
    });
});
```

The above example uses the `beforeInit` event to modify the config for Flatpickr. There's event data in the event's `detail` attribute, which you can modify.

### The `afterInit` event
The event that is triggered after the Flatpickr date picker is initialized.

```js
// Fetch all Date fields - specifically the input. Events are bound on the input element
let $fields = document.querySelectorAll('.fui-type-date-time input');

// For each field, bind on the `afterInit` event
$fields.forEach($field => {
    $field.addEventListener('afterInit', (e) => {
        let datePickerField = e.detail.datepicker;
        let options = e.detail.options;
    });
});
```


### Phone Fields

### The `init` event
The event that is triggered before the phone number library is initialized.

```js
// Fetch all Phone fields - specifically the input. Events are bound on the input element
let $fields = document.querySelectorAll('.fui-type-phone input');

// For each field, bind on the `init` event
$fields.forEach($field => {
    $field.addEventListener('init', (e) => {
        let phoneCountryField = e.detail.phoneCountry;
        let validator = e.detail.validator;
        let validatorOptions = e.detail.validatorOptions;
    });
});
```


### Repeater Fields

### The `init` event
The event that is triggered before the repeater field is initialized.

```js
// Fetch all Repeater fields
let $fields = document.querySelectorAll('.fui-type-repeater');

// For each field, bind on the `init` event
$fields.forEach($field => {
    $field.addEventListener('init', (e) => {
        let repeaterField = e.detail.repeater;
    });
});
```

### The `append` event
The event that is triggered after a new row has been appended to the repeater.

```js
// Fetch all Repeater fields
let $fields = document.querySelectorAll('.fui-type-repeater');

// For each field, bind on the `append` event
$fields.forEach($field => {
    $field.addEventListener('append', (e) => {
        let $row = e.detail.row;
        let $form = e.detail.form;
    });
});
```


### Table Fields

### The `init` event
The event that is triggered before the table field is initialized.

```js
// Fetch all Table fields
let $fields = document.querySelectorAll('.fui-type-table');

// For each field, bind on the `init` event
$fields.forEach($field => {
    $field.addEventListener('init', (e) => {
        let tableField = e.detail.table;
    });
});
```

### The `append` event
The event that is triggered after a new row has been appended to the table.

```js
// Fetch all Table fields
let $fields = document.querySelectorAll('.fui-type-table');

// For each field, bind on the `append` event
$fields.forEach($field => {
    $field.addEventListener('append', (e) => {
        let $row = e.detail.row;
        let $form = e.detail.form;
    });
});
```


### Tag Fields
For Tags element fields, you can access [tagify](https://github.com/yairEO/tagify) settings, and modify them through JavaScript.

### The `beforeInit` event
The event that is triggered before tagify is initialized.

```js
// Fetch all Tags fields - specifically the input. Events are bound on the input element
let $fields = document.querySelectorAll('.fui-type-tags input');

// For each field, bind on the `beforeInit` event
$fields.forEach($field => {
    $field.addEventListener('beforeInit', (e) => {
        let tagField = e.detail.tagField;
        let options = e.detail.options;

        // Modify any tagify options
        e.detail.options.duplicates = true;
        e.detail.options.dropdown.maxItems = 20;
    });
});
```

The above example uses the `beforeInit` event to modify the config for tagify. There's event data in the event's `detail` attribute, which you can modify.

### The `afterInit` event
The event that is triggered after tagify is initialized.

```js
// Fetch all Tags fields - specifically the input. Events are bound on the input element
let $fields = document.querySelectorAll('.fui-type-tags input');

// For each field, bind on the `afterInit` event
$fields.forEach($field => {
    $field.addEventListener('afterInit', (e) => {
        let tagField = e.detail.tagField;
        let tagify = e.detail.tagify;
        let options = e.detail.options;
    });
});
```

