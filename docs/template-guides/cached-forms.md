# Cached Forms
When using caching mechanisms with Formie, it's worth taking note of some caveats to ensure things work correctly.

## Template Caching
If you are using the `{% cache %}` Twig tag in your templates, any JavaScript and CSS will be automatically cached alongside the HTML, and re-registered on subsequent page loads. There's nothing you need to do!

:::tip
This is new behaviour in Craft 4 and later. If you're on Craft 3 (and Formie v1), please refer to those [docs](https://verbb.io/craft-plugins/formie/docs/v1/template-guides/cached-forms).
:::

### Refreshing CSRF Token and Captchas
Whilst the form will now be cached, this will cause issues with Formie's CSRF token, which is also cached. This needs to be unique per-request, so we need a method of being able to update this. Similarly, some captchas that rely on the output for the form will fail. Notably the JavaScript captcha and the Duplicate captcha, as their content will be cached.

Continue reading the next section for a more detailed explanation and how to handle refreshing this information dynamically.

## Static Caching
It's quite commonplace to implement full-page static caching on sites. For Craft, we highly recommend the [Blitz](https://plugins.craftcms.com/blitz) plugin, but you can use any number of methods to statically cache your pages. 

However, caching the form for every visitor poses an issue for Formie's CSRF tokens and captchas used to verify the integrity of form submissions and spam submissions. Indeed, this problem will be the same for any form on your site. To get around this, you'll need to implement a way to refresh these tokens in your forms through JavaScript.

Fortunately, Formie makes it easy to refresh this content with JavaScript.

```twig
{% set form = craft.formie.forms.handle('contactForm').one() %}

{{ craft.formie.renderForm(form) }}

{% js %}
    // Wait until Formie has been loaded and initialized
    document.addEventListener('onFormieInit', (event) => {
        // Fetch the Form Factory once it's been loaded
        let Formie = event.detail.formie;

        // Refresh the necessary bits that are statically cached (CSRF inputs, captchas, etc)
        Formie.refreshForCache(event.detail.formId);
    });
{% endjs %}
```

:::warning
Every time you call `craft.formie.renderForm()` you should also include this script, so that it will work for rendering multiple forms on a page.
:::

Here, we've combined rendering the form as we normally would, with some extra JavaScript. While this entire code will be cached and served exactly the same to each visitor, the JavaScript will be executed when the page is loaded. The `Formie.refreshForCache()` function makes a `GET` call to our `actions/formie/forms/refresh-tokens` controller action, which returns a collection of useful token information.

In summary, this function will:
- Update the CSRF token input value
- Update the JavaScript and Duplicate captcha tokens (if in use).
- Update the unload form hash (the prompt that changes have been made to the form when the user navigates away)

With that in place, your forms are ready for static caching!

## Advanced Usage
While we recommend using our `Formie.refreshForCache` function, you're more than welcome to roll your own solution. The below is a guide for what you can do.

```twig
{% set form = craft.formie.forms.handle('contactForm').one() %}

{{ craft.formie.renderForm(form) }}

<script>
    // Wait until the DOM is ready
    document.addEventListener('DOMContentLoaded', (event) => {
        // Fetch the form we want to deal with
        let $form = document.querySelector('#{{ form.formId }}');

        // Find the CSRF token hidden input, so we can replace it
        let $csrfInput = $form.querySelector('input[name="CRAFT_CSRF_TOKEN"]');

        // Fetch the new token for the form and replace the CSRF input with our new one
        fetch('/actions/formie/forms/refresh-tokens?form={{ form.handle }}')
            .then(result => { return result.json(); })
            .then(result => {
                $csrfInput.outerHTML = result.csrf.input;

                // Find the JavaScript captcha hidden input, so we can update it
                if (result.captchas && result.captchas.javascript) {
                    // JavaScript captcha
                    let jsCaptcha = result.captchas.javascript;

                    $form.querySelector('input[name="' + jsCaptcha.sessionKey + '"]').value = jsCaptcha.value;
                }

                // Find the Duplicate captcha hidden input, so we can update it
                if (result.captchas && result.captchas.duplicate) {
                    // Duplicate captcha
                    let duplicateCaptcha = result.captchas.duplicate;

                    $form.querySelector('input[name="' + duplicateCaptcha.sessionKey + '"]').value = duplicateCaptcha.value;
                }

                // Update the form's hash (if using Formie's themed JS)
                if ($form.form && $form.form.formTheme) {
                    $form.form.formTheme.updateFormHash();
                }
            });
    });
</script>
```
