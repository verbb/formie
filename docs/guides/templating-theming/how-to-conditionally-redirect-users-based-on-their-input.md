# How to conditionally-redirect users based on their input

Let's say you have a form that depending on the user input, redirects to different URLs. Right now, you can't manage this in Formie (but it's on [our roadmap](https://github.com/verbb/formie/issues/830)), but you can achieve this behaviour in a few ways — depending on what works best for your use-case.

### Intermediary template
One solution is to create an intermediary template — essentially a template that sits in between your form template, and the destination URL (whether that be external, or internal). This gives you an immense amount of flexibility.

For example, your form might have a redirect URL like: `/my-success-template?id={id}`. You could create a route in Craft to serve a template for that path. A quick reference, adding this to your `/config/routes.php` file:

```php
return [
    'my-success-template' => ['template' => '_forms/redirect'],
];
```

Here, we create a route that for the provided URL, we serve the `_forms/redirect` template. Go ahead and create that file, with the following content.

```twig
{# Get the `id` param we pass in from our redirect URL. This is the completed submission ID #}
{% set submissionId = craft.app.request.getParam('id') %}

{# Fetch the submission #}
{% set submission = craft.formie.submissions.id(submissionId).one() %}

{# Add your conditional logic! #}
{% if submission.myFieldToCheck == ’some-value' %}
    {% redirect ’some-url' %}
```

Here, we use the Submission ID passed to us from the redirect URL in the form settings. We fetch the submission, then perform our conditional logic (that part's up to you!), and finally, use the `{% redirect %}` Twig tag to redirect us to where we need to go.

One caveat — this doesn't cover Ajax-based forms and relies on another template.

### JavaScript
We can do a similar thing using JavaScript. The benefit of this is that it doesn't require an additional template, and it works with Ajax-based forms. The downside is you'll want to be comfy with JavaScript, and exposes your logic for redirection in the source code of the page, which might matter depending on your use case.

The main issue is that we're going to need to write event listener code that changes the redirect URL value whenever the field changes. So for example, if we have an Agree field, depending on whether it's checked or not, the redirect URL will change.

Additionally, we'll need to create another field to store the modified redirected URL. We can't modify the `redirectInput()` for the form, because that's hashed as a security measure.

So first, create two fields (with their respective handles)
- Agree (handle `verified`)
- Hidden (handle `redirectParam`)

Then, add the following JS code to the page where your form is rendered:

```js
{% js %}

// Get the `<form>` element to start with
const $form = document.querySelector('.fui-form');

// Find the field we want to watch changed for. In this case, a checkbox field with the handle `verified`. 
// Each different type of field will require different event listeners.
const $verifiedField = $form.querySelector('[data-field-handle="verified"]');
const $verifiedInput = $verifiedField.querySelector('input[type="checkbox"]');

// We also have a hidden field with the handle `redirectParam` which we use in the Redirect URL setting.
const $redirectParamInput = $form.querySelector('[data-field-handle="redirectParam"] input');

const updateRedirectParam = function(checked) {
    // Get whether the "Verified" input is checked, or conditionally hidden - or not
    const value = checked ? 'thanks' : 'sorry';

    // Update the "Redirect Param" field
    $redirectParamInput.value = value;
}

// Whenever the "Verified" field is conditionally hidden or shown, update the redirect input
$verifiedField.addEventListener('onAfterFormieEvaluateConditions', function(event) {
    // Is the target field conditionally hidden? Then it's always not-checked. The "Verified" field
    // could actually be checked, but hidden - we want it to evaluate as if they haven't opted-in.
    if (event.target.conditionallyHidden) {
        updateRedirectParam(false);
    } else {
        // Otherwise, best to check the state of the "Verified" field. Manually trigger the change event
        $verifiedInput.dispatchEvent(new Event('change', { bubbles: true }))
    }
});

// Whenever the "Verified" field is toggled on or off, update the redirect input
$verifiedInput.addEventListener('change', (event) => {
    updateRedirectParam(event.target.checked);
});

{% endjs %}
```

While the above code is commented well, there's some things to note:
- We listen to when the checkbox is clicked (`change` event)
- We listen to when Formie hides or shows the Agree field due to it being conditionally hidden or shown
- We set the value of our hidden `redirectParam` field to be either `thanks` or `sorry` if they do or don't check the field.
- Our form settings' Redirect URL value should be `{redirectParam}` to use the value from our Hidden field (`redirectParam`).

### Event
You can hook into an event in your PHP module that's fired when a submission is successful. Through this event, you can modify the redirect URL depending on your logic. The additional benefit of this is that this approach will work for Ajax-based forms, and won't require an additional template.

This does require knowledge of custom modules, so you'll need to get familiar with [creating a module](/blog/everything-you-need-to-know-about-modules). The code we will be writing will be in PHP, and added to our custom module. 

Place the below in your module's `init()` method.

```php
use verbb\formie\controllers\SubmissionsController;
use verbb\formie\events\SubmissionEvent;
use yii\base\Event;

public function init(): void
{
    // ...

    Event::on(SubmissionsController::class, SubmissionsController::EVENT_AFTER_SUBMISSION_REQUEST, function(SubmissionEvent $event) {
        // Important to check if this is the final page in a multi-page form
        if (!$event->form->isLastPage()) {
            return;
        }

        // Add your conditional logic here!
        if ($event->submission->getFieldValue('myFieldToCheck') == 'some-value') {
            // Modify the form settings' redirect URL to what we want
            $event->form->redirectUrl = '/some-url';
        }
    });

    // ...
}
```
