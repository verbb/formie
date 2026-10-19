# Stencils

::: tip
For team rollout and project-stencil patterns, see [Stencils for repeatable form types](/guides/control-panel-admin/stencils-for-repeatable-form-types).
:::

Stencils are reusable starter forms.

They are useful when you want a faster, more consistent starting point than building every new form from scratch. A stencil can carry the same kinds of structure and setup you would normally define on a form, including fields and layout, pages, notifications, and form settings.

That means a stencil is more than a visual starting point. It can carry real behaviour with it as well.

## When to Use Stencils

Stencils are especially helpful when:

- a team creates the same kinds of forms repeatedly
- clients need a safe starting point
- you want to keep common patterns consistent across projects

Formie includes a default `Contact Form` stencil as a general starting point, and you can create your own when you need something more specific.

## Creating and Using Stencils

Create and manage stencils under **Formie → Stencils** (requires the **Access stencils** permission). Building a stencil works much like building a normal form, so you can define the layout, notifications, and form settings you want to reuse.

Choose **New Stencil**, or open the arrow beside it and choose **New project stencil**, using the [storage and deployment guidance below](#project-stencils-developers). Both use the same builder. Once saved, the stencil becomes an option when creating a new form.

Saving a form as a stencil from the form builder creates a new stencil you can edit later.

### Synced Fields

Stencils can include **synced fields**—the same shared field definitions you use across forms.

When you add an existing field as a synced field while building a stencil, Formie remembers that link in the stencil snapshot. Creating a new form from that stencil adds a new placement that points at the same shared definition, so your site-wide email, consent, or other shared fields stay consistent.

If a shared definition cannot be found on the current environment (for example, a project stencil references a handle that does not exist yet), Formie falls back to creating an independent field copy from the stencil settings.

## Shared Starting Points

Stencils are best treated as starting templates, not shared live forms. Changes made to a stencil affect future forms created from it, not forms that already exist.

## Project Stencils (Developers)

The choice between an instance-level stencil and a project stencil controls storage, deployment and editing permissions. An **instance** is a Craft installation, such as development, staging or production, rather than an individual site in a multisite installation.

**New Stencil** creates an instance-level stencil stored in that installation’s database. It is not included in project config, and users with **Access stencils** can create, edit and delete it even when Craft’s `allowAdminChanges` is `false`. Choose this option for starter forms a client maintains on the live site, such as an enquiry template they adjust as their requirements change.

**New project stencil** stores the stencil under `formie.stencils` in project config, ready to commit and deploy with your Craft project. This option is available when admin changes are enabled; creating, editing and deleting project stencils also requires **Access stencils**. When admin changes are disabled, they are view-only. Choose this option for an application template your development team maintains in version control and deploys across environments.

Project stencils display **Project** in the list’s **Scope** column; instance-level stencils leave it blank. Handles must be unique across both types.

Project config sync leaves instance-level stencils unchanged, including those in the trash. Updates and removal in project config apply only to project stencils.

When a project stencil is view-only, users with **Access stencils** can still open it and use **Save a copy** to create an editable, database-only stencil. The original stays managed by project config, and later changes to it do not update the copy.

Both kinds can create forms when admin changes are disabled, subject to form-creation permissions. The resulting forms are database content; deploying a project stencil does not deploy forms previously created from it.
