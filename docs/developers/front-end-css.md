# Front-end CSS

The default [Form Template](docs:template-guides/form-templates) used by Formie uses custom CSS when rendering the form. This is to provide out-of-the-box functionality and style for forms, so you don't need to worry about how to style a form. 

Styles are somewhat opinionated however, and as developers, you may very well want to personalize them to the particular site, or edit them.

Most, if not all classes are prefixed by `fui-` (Formie UI) to allow easy namespacing. The default CSS is rendered alongside the form in the `<head>` of the page, and is split into two parts:

## Base CSS
The base CSS for a form is extremely minimal, with just a few rules. These are mostly for easy layout using flexbox. The gzipped size of this file is roughly 0.2kb.

## Theme CSS 
The theme CSS is a set of opinionated styles meant to provide sane defaults for forms. The goal of this theme CSS is to be hands-off with the styling of the form, but still output an attractive, accessible and functional form. For some cases this will be suitable as a default. The gzipped size of this file is roughly 2.6kb.

## Disabling CSS
To disable all CSS from being output, create a new [Form Template](docs:template-guides/form-templates) and turn off `Output CSS`, `Output Theme`, or both. Ensure your form uses this new template.

## Render Location
By default, Formie will render a single CSS file in the `<head>` tag. You can choose to either render it `Inside Form`, or `Manual` - Manual essentially disabling rendering.

### Manual Render
You can select exactly where you'd like the CSS for forms to be output on the page, using `craft.formie.renderFormCss()`

```twig
{{ craft.formie.renderFormCss('myForm') }}

<div class="form-wrap">
    {{ craft.formie.renderForm('myForm') }}
</div>
```

You could also use a Form object instead of the handle.

```twig
{% set form = craft.formie.forms({ handle: 'myForm' }).one() %}

{{ craft.formie.renderFormCss(form) }}

<div class="form-wrap">
    {{ craft.formie.renderForm(form) }}
</div>
```

Here, we have split the rendering of the form to firstly the CSS, then the HTML for the form.