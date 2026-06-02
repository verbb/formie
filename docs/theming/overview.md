# Overview

While Formie aims to make rendering forms simple, it is just as important to be able to customize that output so it fits the rest of your site.

Formie gives you a few different ways to do that. Start with the lightest option that gives you enough control.

## Theme Config

Theme config is usually the best place to start, and for most projects it is enough.

It lets you define the HTML tags and attributes used for parts of the form and its fields. That means you can add or remove classes, add data attributes, change wrappers, or even remove some elements entirely.

This works especially well when you want forms to fit a front-end system like Tailwind or Bootstrap, because you can control the HTML attributes directly without having to override Twig templates.

### When to use it

- you want to add Tailwind or Bootstrap classes
- you want to remove or replace Formie's default classes
- you want to adjust HTML attributes or wrappers without rewriting templates
- you want to keep maintenance lower as Formie evolves

### When not to use it

- you want full control over every template
- you prefer to work directly in Twig templates

Read more in [Theme Config](/theming/theme-config).

## Template Overrides

Template overrides are the next step when theme config is not enough.

They let you override individual Twig templates for forms, fields, buttons, labels, alerts, and other parts of the rendered output. The main advantage is that this does not have to be all-or-nothing. You can override just the parts you need and leave the rest using Formie's defaults.

### When to use it

- you want to customize one part of the form, such as the buttons or a field template
- you want more control than theme config provides
- you are comfortable maintaining Twig overrides over time

### When not to use it

- you only need to add or remove classes or attributes
- you do not want the maintenance that comes with template overrides
- you want to take over the entire rendering process yourself

Read more in [Template Overrides](/theming/template-overrides).

## Custom Rendering

Custom rendering is the most involved approach. Instead of letting Formie render the form for you, you take responsibility for rendering the form, pages, and fields yourself.

This gives you the most control, but it also means you take on the most maintenance. As Formie changes, your custom templates may need to change with it. In most cases, this should be the last option you reach for, not the first.

### When to use it

- you have a very specific or unusual rendering requirement
- you are building a highly custom front end
- you want complete control over the markup

### When not to use it

- you only need to tweak one or two parts of the form
- theme config or template overrides would already solve the problem
- you do not want to maintain custom rendering over time

Read more in [Custom Rendering](/theming/custom-rendering).
