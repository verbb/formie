# Overview
While Formie aims to provide a very simple and hands-off approach to rendering your forms, it's also vitally important to be able to customise it, make it your own, and fit within the overall style of the rest of your site.

Formie provides several mechanisms for you to customise the rendering output of forms, pages and fields. This overarching term is something we call "theming" your form, and there are several ways to approach this.


## Theme Config (Recommended)
A theme config refers to an object where you define what HTML tag and attributes each component of the field should use. Formie has it's own provided, but allows you to manipulate this config as you see fit. This provides you with a lot of power by setting attributes on every aspect of the form and fields, from adding or removing classes, data attributes to changing what HTML tag to use. You can even exclude HTML elements altogether, should you wish to alter the structure of components.

This is also an excellent way to have your forms work with popular front-end frameworks that rely on classes for HTML elements, like Tailwind or Bootstrap. Because you're given total control over the HTML attributes each HTML element uses, you can add utility classes to elements, and even exclude Formie's default attributes.

The main benefit to this approach that there's zero extra Twig templating involved, so you can rest easy knowing that changes to Formie's own templating wont affect your theme config, lowering your technical debt.

### When to use it
- You want to add Tailwind or Bootstrap support via utility classes.
- You want to remove all of Formie's classes on HTML elements.
- You want to BYO CSS and manipulate classes, instead of overriding Formie's CSS.
- You just want to add or remove HTML attributes on elements, changing the structure.
- You don't want to have to maintain templates as Formie evolves.
- You want to create a consistent set of attributes for HTML elements that can be copied across from project to project.

### When not to use it
- You want full control over every templating aspect of the form.
- You prefer to use Twig and enjoy the level of control versus setting config options.

Continue reading about [theme config](docs:theming/theme-config).


## Template Overrides
Template overrides are the next-best thing when setting a theme config isn't enough. This is where you get back into Twig templates and have total control over how individual components of a form or field are rendered.

The beauty of template overrides is that it doesn't have to be all-or-nothing. If you want to override just a component of the form, like the submit buttons, form title, alerts, field label, field inputs, and more - you can. All while falling back on the default Formie templates for things you don't want to override.

As these template overrides are heavily tied to [Form Templates](docs:feature-tour/form-templates) you'll want to gain an understanding of how those work before diving in.

### When to use it
- You want to customise _just_ the button(s) on a form.
- You want to override one or two fields with your own templates, and fall back to Formie's defaults for others.
- You want to change the template a single field in your form.
- You want full control over every templating aspect of the form, but in an opt-in manner (only override what you need to).
- You are comfortable using [Form Templates](docs:feature-tour/form-templates).
- You acknowledge that you'll need to maintain these custom templates when Formie updates.

### When not to use it
- You just want to add or remove a class from the submit button. (While this is technically okay, we recommend theme config).
- You just want to change the primary colour for elements.
- You aren't comfortable using [Form Templates](docs:feature-tour/form-templates), and you don't want to worry about having more templates to keep up to date.
- You want to completely roll your own templates for everything!

Continue reading about [template overrides](docs:theming/template-overrides).


## Custom Rendering
Custom Rendering is the most involved and heavy-handed approach to rendering your forms, as the entire form, page, field, etc process is entirely up to you. Compared to template overrides which acts more on the basis of tweaking a few things and overriding what you need, custom rendering is all about DIY. As such, you've got the keys to the car and you're on your own!

This can be both invigorating and daunting. We only really recommend this approach if you have very specific needs for your form, and are willing to keep it up to date as Formie changes and grows. As new features, functionality and architecture is added to Formie, there'll likely be changes that flow on to templating.

### When to use it
- You have a form that's very simple or unique, and unlike any other form for your entire site. Think a newsletter signup form.
- You use GraphQL in a headless scenario, where the rest of your site is BYO front-end.
- You prefer to have total control over every aspect of the rendering process.
- You don't need Formie's JS or CSS. If you do, you're aware that your HTML structure has certain limitations to adhere to.
- You acknowledge that you'll need to maintain these custom templates when Formie updates.

### When not to use it
- You just want to add or remove a class from the submit button.
- You just want to override one or two fields with your own templates, and fall back to Formie's defaults for others.
- You just want to insert some content alongside a field, or in the `<form>` body.
- You aren't prepared to maintain template functionality as Formie evolves.

Continue reading about [custom rendering](docs:theming/custom-rendering).


## CSS Variables
While using CSS Variables won't allow you to alter the structure of HTML elements or attributes for a rendered form, you have quite a lot of flexibility with styling a form.

You can either retain Formie's CSS and override the styles we provide, or disable the CSS and go right ahead and style `fui-*` classes yourself, with your framework of choice.

### When to use it
- You want to BYO CSS.
- You want to add Tailwind or Bootstrap support (without adding the classes in HTML - just CSS).
- You want to change the colour of the submit button.
- You want to change the primary colour for elements (submit button, focus rings) leaving everything else be.
- You want to adjust the style of all field inputs.

### When not to use it
- You want to use Tailwind or Bootstrap utility classes on HTML elements.
- You want to modify the structure of the form or fields.
- You need a radically different HTML structure.

Continue reading about [CSS variables](docs:developers/front-end-css).


## Hooks
Hooks are somewhat limiting compared to other approaches, but are another handy method to consider. Hooks allow you to insert Twig code at various points in the rendering process. What's limiting is you can only insert content at the locations we've placed hooks, and you are unable to remove HTML elements or attributes the default templates.

### When to use it
- You want to insert an alert, or banner text at the top of the form.
- You want to insert some extra text or HTML near a field of a particular type.
- You can write PHP module code.

### When not to use it
- Being able to manage (add or remove) HTML attributes on HTML elements.
- Removing HTML elements from the default templates.
- You can't write PHP module code.

Continue reading about [hooks](docs:developers/hooks).
