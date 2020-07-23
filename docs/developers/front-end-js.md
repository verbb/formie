# Front-end JS

The default [Form Template](docs:template-guides/form-templates) used by Formie uses custom JS when rendering the form. This is to provide out-of-the-box functionality for forms, so you don't need to worry about things like validation, multi-page setup and Ajax submissions.

The default JS is rendered alongside the form and placed before the `</body>` tag at the end of the page. It is split into two parts:

## Base JS
The base JS for a form is minimal, providing event hooks for the form and fields, along with easy translation handling for strings. It also provides these event hooks so that Captchas like ReCAPTCHA can work correctly.

The gzipped size of this file is roughly 7.4kb, and comes with all required polyfills to support [90% browser coverage](https://browserl.ist/?q=defaults).

## Theme JS 
The theme JS is a little more opinionated, and provides the following:

- Translatable client-side validation (via [Bouncer.js](https://github.com/cferdinandi/bouncer))
- Multi-page handling
- Success/error message handling
- Ajax submissions
- Loading indicator
- Disabling the submit button when clicked
- Prompting the user they have unsaved changes, if trying to navigate away

The gzipped size of this file is roughly 18.2kb, and comes with all required polyfills to support [90% browser coverage](https://browserl.ist/?q=defaults).

## Field-specific JS
Some fields also require additional JS to be rendered alongside the field. Fortunately, these are only included if the field is actually used in the form. These are:

### Checkboxes/Radios
This helper provides toggling of `aria-checked` and `aria-required` attributes for checkboxes and radios. In addition, there is currently no native HTML5 validation behaviour for validating a group of checkbox fields. This is if you want the user to select at least one option in a collection of checkboxes. This helper also provides this functionality.

### File Upload
This helper is provided to assist with limits set on the field, by the types of files allowed to upload and the file-size limit.

### Repeater
This helper provides functionality to the Repeater field, namely by handling adding or removing new rows of repeatable content.

### Table
This helper provides functionality to the Table field, namely by handling adding or removing new rows of repeatable content.

### Tags
This helper provides functionality to create tags for a Tag field. It uses [Tagify](https://github.com/yairEO/tagify).

### Text
For Single-line Text and Multi-line text fields, and if the `Limit` options are set, this will display a counter for either characters or words to limit the text for these fields. It will also prevent typing past these limits.

## Disabling JS
To disable the Theme JS from being output, create a new [Form Template](docs:template-guides/form-templates) and turn off `Output JavaScript`. Ensure your form uses this new template.

It is worth noting that the [Base JS](docs:developers/front-end-js) cannot be disabled for a form template, as it is critical to the functionality of a form. However, creating your own custom Twig templates to use in your [Form Template](docs:template-guides/form-templates) will not include this JS, and is completely up to you on how to handle all aspects of the form. 

Please be aware of other dependent features like Captchas (particularly ReCAPTCHA) will not work, and it will be also up to you to implement these.


## Base JS Events
The [Base JS](docs:developers/front-end-js) file provides a number of event hooks for the form and fields, which you can hook into in your own JS files.

Our JS hijacks the native submit handler of a form, and wraps it in a number of custom events that give you more fine-grained control over the flow of the form submission. This is used mostly for validation, and captcha support, but you can make use of these for your own needs.

### The `onBeforeFormieSubmit` event
The event that is triggered before a form is submitted, and before validation is triggered. You can cancel a submission by using `preventDefault()`.

```js
let $form = document.querySelector('#formie-form-1');
$form.addEventListener('onBeforeFormieSubmit', (e) => {
    e.preventDefault();

    // ...
});

// jQuery
$('#formie-form-1').on('onBeforeFormieSubmit', function(e) {
    e.preventDefault();

    // ...
});
```



### The `onFormieValidate` event
The event that is triggered before a form is submitted, but after validation is triggered. You can cancel a submission by using `preventDefault()`.

```js
let $form = document.querySelector('#formie-form-1');
$form.addEventListener('onFormieValidate', (e) => {
    e.preventDefault();

    let submitHandler = e.detail.submitHandler;
    // ...
});

// jQuery
$('#formie-form-1').on('onFormieValidate', function(e) {
    e.preventDefault();

    let submitHandler = e.detail.submitHandler;
    // ...
});
```



### The `onAfterFormieSubmit` event
The event that is triggered after a form is submitted.

```js
let $form = document.querySelector('#formie-form-1');
$form.addEventListener('onAfterFormieSubmit', (e) => {
    e.preventDefault();

    // ...
});

// jQuery
$('#formie-form-1').on('onAfterFormieSubmit', function(e) {
    e.preventDefault();

    // ...
});
```



### The `onFormieSubmitError` event
The event that is triggered if an error of submission is detected. This can also be called manually through `formSubmitError()`.

```js
let $form = document.querySelector('#formie-form-1');
$form.addEventListener('onFormieSubmitError', (e) => {
    e.preventDefault();

    // ...
});

// jQuery
$('#formie-form-1').on('onFormieSubmitError', function(e) {
    e.preventDefault();

    // ...
});
```


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
