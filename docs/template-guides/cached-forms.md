# Cached Forms
When using caching mechanisms with Formie, it's worth taking note of some caveats to ensure things work correctly.

## Template Caching
If you are using the `{% cache %}` Twig tag in your templates, any JavaScript and CSS will be automatically cached alongside the HTML, and re-registered on subsequent page loads.

### Refreshing CSRF Token and Captchas
Whilst the form will now be cached, this will cause issues with Formie's CSRF token, which is also cached. This needs to be unique per-request, so we need a method of being able to update this. Similarly, some captchas that rely on the output for the form will fail. Notably the JavaScript captcha and the Duplicate captcha, as their content will be cached.

Continue reading the next section for a more detailed explanation and how to handle refreshing this information dynamically.

## Static Caching
It's quite commonplace to implement full-page static caching on sites. For Craft, we highly recommend the [Blitz](https://plugins.craftcms.com/blitz) plugin, but you can use any number of methods to statically cache your pages. 

However, caching the form for every visitor poses an issue for Formie's CSRF tokens and captchas used to verify the integrity of form submissions and spam submissions. Indeed, this problem will be the same for any form on your site. To get around this, you'll need to implement a way to refresh these tokens in your forms through JavaScript.

Let's take a look at some examples in action.

### CSRF Token

```twig
{% set form = craft.formie.forms.handle('contactForm').one() %}

{{ craft.formie.renderForm(form) }}

{# Ensure we load polyfills for older browsers that don't support `fetch()` #}
<script src="https://cdn.polyfill.io/v2/polyfill.js?features=fetch,Promise"></script>

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
            .then(result => { $csrfInput.outerHTML = result.csrf.input; });
    });
</script>
```

Here, we've combined rendering the form as we normally would, with some extra JavaScript. While this entire code will be cached and served exactly the same to each visitor, the JavaScript will be executed when the page is loaded. The above script makes a `GET` call to our `actions/formie/forms/refresh-tokens` controller action, which returns a collection of useful token information - part of which is a fresh CSRF token. 

We use this to inject and replace the cached CSRF token (which is completely invalid now), after which the form will submit as expected. It's a little extra work to get things working with a static cached page, but it's worth it for significant performance gains!

The response from the `formie/forms/refresh-tokens` action would look something like:

```json
{
    "csrf": {
        "param": "CRAFT_CSRF_TOKEN",
        "token": "MVHMpS1zZXotiEYY...",
        "input": "<input type=\"hidden\" name=\"CRAFT_CSRF_TOKEN\" value=\"MVHMpS1zZXotiEYY...\">"
    },
    "captchas": {
        "duplicate": {
            "sessionKey": "__DUP_91804410",
            "value": "617138283f857"
        },
        "javascript": {
            "sessionKey": "__JSCHK_91804410",
            "value": "617138283f878"
        }
    }
}
```

We'll cover the `captchas` portion of this shortly, but you'll notice the `csrf.param`, `csrf.token` and `csrf.input` are available, which we've used above in our `fetch()` callback. Our example shows using `csrf.input` for convenience, but use whatever you prefer.

```js
fetch('/actions/formie/forms/refresh-tokens?form={{ form.handle }}')
    .then(result => { return result.json(); })
    .then(result => {
        // Use `csrf.input` for convenience
        $csrfInput.outerHTML = result.csrf.input;

        // Use `csrf.param` and `csrf.token`
        $form.querySelector('input[name="' + result.csrf.param + '"]').value = result.csrf.token;
    });
```

### Captchas
As shown above, the `formie/forms/refresh-tokens` action also contains information about captchas. Because some captchas rely on the page content being unique, we must update them dynamically now that the page is statically cached.

The response from the `formie/forms/refresh-tokens` contains information on captcha tokens:

```json
{
    "captchas": {
        "duplicate": {
            "sessionKey": "__DUP_91804410",
            "value": "617138283f857"
        },
        "javascript": {
            "sessionKey": "__JSCHK_91804410",
            "value": "617138283f878"
        }
    }
}
```

Which we can use in our callback to find the hidden `<input>` elements, and update their `value` attributes.

```twig
{% set form = craft.formie.forms.handle('contactForm').one() %}

{{ craft.formie.renderForm(form) }}

{# Ensure we load polyfills for older browsers that don't support `fetch()` #}
<script src="https://cdn.polyfill.io/v2/polyfill.js?features=fetch,Promise"></script>

<script>
    // Wait until the DOM is ready
    document.addEventListener('DOMContentLoaded', (event) => {
        // Fetch the form we want to deal with
        let $form = document.querySelector('#{{ form.formId }}');

        // Fetch the new tokens for the form and replace the captcha inputs
        fetch('/actions/formie/forms/refresh-tokens?form={{ form.handle }}')
            .then(result => { return result.json(); })
            .then(result => {
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
            });
    });
</script>
```

Here we're implementing the same approach as the CSRF token, by getting fresh information for each captcha, querying for the hidden `<input>` elements in the form, and updating those values. The `result.captchas` will only contain token information for the captchas you have enabled, so if you aren't using all of them, you need not include the respective captchas - if you're not using the JavaScript or Duplicate captcha, this can be omitted altogether.

### Unload Event
As a nice UX, Formie provides a prompt for when a form's content has changed when users try to navigate away from a form. This prevents users from filling out a form, but not submitting, when accidentally (or on purpose) navigating away.

However, this detection will cause some issues when dynamically modifying the DOM of the form. Formie will think the content of a form has changed, therefore will prompt when navigating away, when the user hasn't touched the form.

To get around this, you can refresh the state of the form after you've updated the DOM. Formie maintains a hash of content, which you can refresh.

```js
<script>
    // Wait until the DOM is ready
    document.addEventListener('DOMContentLoaded', (event) => {
        // Fetch the form we want to deal with
        let $form = document.querySelector('#{{ form.formId }}');

        // Fetch the new tokens for the form and replace the captcha inputs
        fetch('/actions/formie/forms/refresh-tokens?form={{ form.handle }}')
            .then(result => { return result.json(); })
            .then(result => {
                // Update the CSRF token or captchas.
                // ...

                // Update the form's hash (if using Formie's themed JS)
                if ($form.form && $form.form.formTheme) {
                    $form.form.formTheme.updateFormHash();
                }
            });
    });
</script>
```
