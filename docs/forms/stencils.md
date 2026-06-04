# Stencils

Stencils are reusable starter forms.

They are useful when you want a faster, more consistent starting point than building every new form from scratch. A stencil can carry the same kinds of structure and setup you would normally define on a form, including fields and layout, pages, notifications, and form settings.

That means a stencil is more than a visual starting point. It can carry real behavior with it as well.

## When to use stencils

Stencils are especially helpful when:

- a team creates the same kinds of forms repeatedly
- clients need a safe starting point
- you want to keep common patterns consistent across projects

Formie includes a default `Contact Form` stencil as a general starting point, and you can create your own when you need something more specific.

## Creating and using stencils

Create and manage stencils under **Formie → Stencils** (requires the **Access stencils** permission). Building a stencil works much like building a normal form, so you can define the layout, notifications, and form settings you want to reuse.

Choose **New stencil** to create one you can edit in the control panel. Once a stencil exists, it becomes an option when creating a new form. Choosing a stencil gives you a quick starting point, and from there the form becomes its own form that can be changed independently.

Saving a form as a stencil from the form builder creates a new stencil you can edit later.

## Shared starting points

Stencils are best treated as starting templates, not shared live forms. Changes made to a stencil affect future forms created from it, not forms that already exist.

## Project stencils (developers)

Some stencils are created as **project stencils** and stored in project config (`formie.stencils`). They are useful for starter kits and defaults that should be versioned with the site and shared across a team.

Handles must be unique across both regular and project stencils.

On sites where admin changes are disabled, project stencils are **view-only** in the control panel. You can still open them and use **Save a copy** to create an editable stencil of your own.

- **New stencil** — available to everyone with stencil access.
- **New project stencil** — only when admin changes are enabled (typically local or staging).
