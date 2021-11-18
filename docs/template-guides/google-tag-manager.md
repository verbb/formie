# Google Tag Manager
It's common to want to trigger data layer events through Google Tag Manager after a form has been submitted. You have options to configure this in the control panel in the Page settings, or in your templates.

:::tip
This is specifically for Google Tag Manager and triggering data layer events, but can be applied to any post-submission processes.
:::

## Page Settings
You can add event information to Page Settings, by clicking on a submit button on any page in the form builder in the control panel. This gives you the flexibility to control whether events are sent on every page submission (for multi-page forms) or just at the final submission.

Click **Enable JavaScript Events** to enable JavaScript events to be managed, and the **Google Tag Manager Event Data** settings table will appear. In this field, you define your Option/Value parameters to send to Google Tag Manager.

Formie provides a default payload information for you:

- Option: `event`, Value: `formPageSubmission`
- Option: `formId`, Value: `myFormHandle`
- Option: `pageId`, Value: `3074`
- Option: `pageIndex`, Value: `0`

Which would equate to the payload in JavaScript:

```js
dataLayer.push({
    event: 'formPageSubmission',
    formId: 'myFormHandle',
    pageId: '3074',
    pageIndex: '0',
});
```

Of course, you can modify these options to suit your needs by removing or adding an options and values.

## Templates
You can also have more fine-grained control over the event in your templates, using JavaScript. How you'll go about this will depend on what type of form submission you have, and what type of Action on Submit behaviour you have set.

### Page Reload
If your form is set to Page Reload, you can use the following to detect whether the form has been submitted. This will take into account multi-page forms, and will only be true when a form is completed.

```twig
{% set form = craft.formie.forms({ handle: 'contactForm' }).one() %}

{{ craft.formie.renderForm(form) }}

{% set submitted = craft.formie.plugin.service.getFlash(form.id, 'submitted') %}

{% if submitted %}
    {% js %}
        window.dataLayer = window.dataLayer || [];
        
        dataLayer.push({
            event: 'formSubmission',
            formType: '{{ form.title }}',
        });
    {% endjs %}
{% endif %}
```

:::tip
The content of `dataLayer.push()` is totally up to you and your requirements. The above is purely a common example.
:::

If you set your Action on Submit behaviour to a redirect, you should ensure that the `dataLayer` code is on that page. As such, you wouldn't require the `submitted` check.

### Ajax
For Ajax enabled forms, you'll need to employ JavaScript in a further capacity to listen on the form submission event, then push the same code to the `dataLayer`.

```twig
{% set form = craft.formie.forms({ handle: 'contactForm' }).one() %}

{{ craft.formie.renderForm(form) }}

{% js %}
    let $form = document.querySelector('#{{ form.getFormId() }}');

    $form.addEventListener('onAfterFormieSubmit', (e) => {
        e.preventDefault();

        if (e.detail.nextPageId) {
            return;
        }

        window.dataLayer = window.dataLayer || [];
    
        dataLayer.push({
            event: 'formSubmission',
            formType: '{{ form.title }}',
        });
    });
{% endjs %}
```

Here, we're listening to the `onAfterFormieSubmit` event in JavaScript, which is fired every time the form is submitted and saved with Formie. You'll notice the `if (e.detail.nextPageId)` check, which is for multi-page forms. As stated, this event is fired on every page submission (as each page's content is saved), but we only care about when the form submitted successfully on the last page. This check will ensure that.
