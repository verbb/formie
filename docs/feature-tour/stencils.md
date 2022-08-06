# Stencils
Formie provides a useful mechanism for creating "starter" forms, called Stencils. Stencils are essentially saved forms that you can use to create new forms from. This is a massive time-saver, particularly for clients, so they don't need to create an entire form from scratch.

Stencils store everything a normal form does, from fields and field layout to email notifications and form settings. As such, they can be used as a base form when creating new forms, instead of starting with a completely blank form.

Formie comes with a single stencil called "Contact Form". This provides sane defaults for a general contact form.

You can also create your own stencils, or edit existing ones. Navigate to Formie → Settings → Stencils. Creating a stencil is identical to a form, and you'll have access to the form builder, email notification settings and form settings.

After creating your Stencil, they'll be available to select when creating a new form, as a quick-start to building your forms.

Under the hood, Stencils are stored in the [Project Config](https://craftcms.com/docs/4.x/project-config.html), and if your project is using the `project.yaml` schema, stencils can be created and used for multiple environments, or used across projects.