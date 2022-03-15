# Synced Fields
Synced fields are a special field mechanism in Formie that allows you to link fields together, and ensure that their settings and functionality is the same. This is significantly beneficial on large sites where you find you're duplicating or re-creating the same fields.

:::tip
Currently, Repeater fields cannot be added as a synced field.
:::

What a synced field does is synchronise its settings with every other instance of the field. This means that any changes made on **any** instance of a synced field will be synchronised to every other instance of that field.

For example, you might have 5 forms on your site, and each has an Email Address field. Each field has identical settings, but you want to update the placeholder for the field. The traditional approach would be to edit each of the 5 forms, and update each Email Address field with the placeholder - quite frankly, time-consuming and error-prone.

Instead, you could create the Email Address field once, then on subsequent forms, add it as a synced field. You could then edit any of your 5 forms - or any form that includes this field - and make changes to the field. The changes would then propagate to all other instances of that field.

Each synced field will have a yellow marker next to its label, denoting that it's a synced field. In addition, when editing the field, a warning banner will show that editing this field will be editing it across multiple fields - so to be careful.

<img src="https://verbb.io/uploads/plugins/formie/formie-synced-field.png" />

## Adding a Synced Field
To add a synced field, navigate to the form builder by editing a form. You can use the [Existing Fields](docs:feature-tour/existing-fields) modal window to add a synced field to your form.

You are unable to un-sync a synced field. Instead, you'll need to create a new field.

