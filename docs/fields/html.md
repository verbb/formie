# HTML

Use HTML when the form needs custom markup or Twig between fields.

HTML is a cosmetic field for developers and agencies who need custom markup, sandboxed Twig expressions, and layout fragments. If the content is just formatted copy for non-technical editors, use [Rich Text](/fields/rich-text) instead.

## Key Settings

- **HTML content** - Define the markup rendered in the form, edited with a syntax-highlighted code editor in the form builder.
- **Allow Twig** - When enabled, Twig in the HTML content is parsed in Formie’s sandbox when the form is rendered. The sandbox restricts tags, functions and object access. Site-template includes require a separate site-wide opt-in.
- **Purify content** - When enabled, output is passed through HTML Purifier before rendering.
- **Visibility conditions** - Show or hide the content based on other form values when needed.

## Site-Wide Controls

Administrators control these options in **Settings → Fields**. New installs have **Allow Twig in HTML Fields** and **Allow Template Includes in HTML Fields** off, and **Require HTML Purification** on. Upgrades preserve existing availability and per-field purification choices.

When Twig is disabled site-wide, HTML fields already using Twig produce no output and retain their source. Turn off the field’s **Allow Twig** setting before saving an affected form, or ask an administrator to restore access. When purification is required, the field’s Purify Content toggle is hidden and all HTML output is purified regardless of its stored preference.

## Including a Template

Enable the site-wide **Allow Twig in HTML Fields** and **Allow Template Includes in HTML Fields** controls, then enable the field’s **Allow Twig** setting to reuse a template fragment in an HTML field. For example, create `templates/_formie/notice.twig` in your Craft project:

```twig
<p>{{ message }}</p>
```

Then enter this in the field's **HTML content**:

```twig
{% include '_formie/notice' with {message: 'Please check your details before submitting.'} only %}
```

The include resolves through Craft's site-template locations, including site-specific templates and roots registered by modules or plugins. This also works in control-panel previews. Twig's `include()` function is supported too. Absolute filesystem paths and paths outside these configured roots are rejected.

Included templates run in the same sandbox as the HTML field. They receive the permitted context and supplied variables, and output is purified when **Require HTML Purification** or the field’s **Purify Content** setting is enabled. Including a file does not grant access to unrestricted Craft globals, PHP calls or the `source()` function. Use a developer-owned template override if your fragment needs the full Craft template environment.

This include support applies specifically to HTML fields with **Allow Twig** enabled. Hidden Template defaults and PDF filename templates keep their existing sandbox without includes.

## Submitted Value

HTML is cosmetic and does not save a normal submitted value. Content is rendered at display time from the field settings.

## Theme Config

The HTML field can be targeted with the `html` theme config key.

```twig
{{ craft.formie.renderForm('contactForm', {
    themeConfig: {
        html: {
            field: {
                attributes: {
                    class: 'my-html-block',
                },
            },
        },
    },
}) }}
```

Use theme config for wrapper attributes. Use a template override only when the cosmetic field output needs to change globally.

For full Tailwind, Bootstrap and other framework examples, see [Formie theme configs](https://github.com/verbb/formie-theme-configs).

## Related Fields

- Use [Rich Text](/fields/rich-text) for WYSIWYG content editable by non-technical users.
- Use [Heading](/fields/heading) for simple section titles.
- Use [Section](/fields/section) for visual dividers.
- Use [Group](/fields/group) when fields should be structurally grouped.
