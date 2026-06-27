# How to manually set the page for a form

Setting the page for a form can be a useful feature, like being able to skip ahead. Let's say you have a 4-page form for users to register their interest in your product. The first two pages apply to "residential" enquiries, while the 3rd page applies to "commercial" enquires. The 4 page applies to both.

You could achieve something fancy with page conditionals (and you indeed can do that!), but that requires a "are you a residential or commercial customer?" type field. Instead, let's imagine we want to send residential enquiries to the start of the form, but commercial enquiries to the 3rd page. To do that, we can manually set the page.

Fortunately, this is pretty straightforward to do in Formie!

Let's say we have two URLs to navigate users to our form:
- https://formie.test/enquire
- https://formie.test/enquire?commercial=true

With these URLs, we can control how we render the form.

```twig
{% set form = craft.formie.forms.handle('enquiry').one() %}
{% set isCommercial = craft.app.request.getParam('commercial') %}

{# Set the form to start at page 3 if commercial #}
{% if isCommercial %}
    {# Get all the pages for the form #}
    {% set pages = form.getPages() %}

    {# Set the current page to the 3rd one (indexed by 0) #}
    {% do form.setCurrentPage(pages[2]) %}
{% endif %}

{{ craft.formie.renderForm(form) }}
```

### Using a button
So we've demonstrated how to set the page from the URL, but let's say you _do_ in fact want a button to say "I'm a commercial customer" and "I'm a residential customer". Maybe this is placed on the first page of the form, or even above the form itself, and when clicking on the button, the user is skipped ahead in the form.

We can use a POST request to achieve much of the same thing.

```twig
{% set form = craft.formie.forms.handle('enquiry').one() %}

<form>
    {{ actionInput('formie/submissions/set-page') }}
    {{ hiddenInput('handle', form.handle) }}
    {{ csrfInput() }}

    {# Get all the pages for the form #}
    {% set pages = form.getPages() %}

    {# Send the ID of the 3rd page (indexed by 0) #}
    {{ hiddenInput('pageId', pages[2].id) }}

    <button type="submit">Skip to Commercial</button>
</form>
```

The only real difference here is that we're using the ID of a page, instead of the page object.
