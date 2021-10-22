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

## CSS Variables
Rather than ditching the entire theme CSS, or overriding through your own CSS definitions, you can use [CSS Variables](https://developer.mozilla.org/en-US/docs/Web/CSS/Using_CSS_custom_properties) to override just what you require. For almost every opinionated style, Formie has a CSS Variable for you to use.

:::tip
For a full list of available variables, refer to the [CSS Variables](https://github.com/verbb/formie/blob/craft-3/src/web/assets/frontend/src/scss/_variables.scss).
:::

A classic example would be to use the Formie Theme CSS, but change the submit button colour, and some input styles. You could achieve this with the following CSS (either inline or in your own stylesheets).

```css
.fui-btn {
    --fui-btn-font-size: 1rem;
    --fui-btn-padding: 0.375rem 0.75rem;
    --fui-btn-border-radius: 0.25rem;
}

.fui-submit {
    --fui-submit-btn-bg-color: #dc3545;
    --fui-submit-btn-border-color: #dc3545;
    --fui-submit-btn-bg-color-hover: #bb2d3b;
    --fui-submit-btn-border-color-hover: #b02a37;
    --fui-submit-btn-box-shadow-focus: 0 0 0 0.25rem rgba(225, 83, 97, 0.5);
}

.fui-input,
.fui-select {
    --fui-input-font-size: 16px;
    --fui-input-padding: 0.75rem 1rem;
    --fui-input-border-radius: 6px;
}
```

This gives the benefit of relying on the Theme CSS, but giving you full control over theme settings and variables, without needing to override everything with `!important` to get around specificity rules.
