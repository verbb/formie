# Forms
Forms are the core feature of Formie, and likely where you'll head first! They are a collection of fields, where you can define the layout, behaviour, appearance and even email notifications.

:::tip
Looking to make the switch to Formie? Read our [blog post](https://verbb.io/blog/introducing-formie) on why we built Formie.
:::

## Form Builder
The form builder is a powerful and intuitive drag-and-drop interface to allow you to quickly build a form. To get started, drag a field from the right-hand sidebar onto the left-hand pane. A large dropzone will appear for new forms as a prompt to where to drop the field. For forms with existing fields, light blue dropzones will appear suggesting where to place a field.

<img src="https://verbb.io/uploads/plugins/formie/formie-form-builder.png" />

Fields can be laid out in a series of columns and rows. For more complex forms, you can create additional pages, splitting fields over multiple pages. Fields can also be dragged-and-dropped to other pages.

To ease the use of having lots of fields, you can also select [Existing Fields](docs:feature-tour/existing-fields) to add to your form. From these existing fields, you can choose whether to add the field as a copy of the original field, or maintain a link to the original field as a [Synced Field](docs:feature-tour/synced-fields).

Hovering over any field will show a settings icon in the top-right of a field. Clicking this provides quick options for a field, such as editing, cloning, requiring, or deleting the field. Cloning a field will create a new field directly below it.

You can also click anywhere on the field's preview to open up the field editor modal. This modal is where all settings for the field can be defined. Be sure to save any changes!

In the same way, you can edit the buttons of a form by clicking on the red button. For multi-page forms, you'll have the option of two buttons for going back to a previous page.

## Form Settings
There are a number of settings for forms. Each tab has an associated permission, which we highly encourage to use for clients using forms, so as not to overwhelm with options.

### Appearance
General appearance attributes can be managed here, including:

- Whether the title of this form should be included on the page when rendering the form.
- Whether the title of the current page should be included when rendering the form.
- Whether tabs of all pages should be included on the page when rendering the form. This is only applicable for forms with more than one page.
- Whether to show a progress bar of the page completion. This is only applicable for forms with more than one page.
- Select the templates this form should use.
- Set a default label position that fields will by default have their label position set to.
- Set a default instructions position that fields will by default have their instructions position set to.

### Behaviour
The behaviour section provides options on how the form and submission behaves to users. This includes:

- How the form should be submitted, via Page Reload, or Ajax
- Configure the on-submit action between:
    - Displaying a message
    - Redirecting to an entry
    - Redirecting to a URL
    - Reloading the page
    - Clearing the form's values
- Submission message
- Loading indicator
- Validation
    - Enabling on-submit
    - Enabling on-blur (when typing)

### Email Notifications
Email notifications are an important part of forms, to both notify the user their submission has been received, and to notify admins of their submission, so they can action.

Each notification is form-specific, and you can create as many notifications as required. Refer to [Email Notifications](docs:feature-tour/email-notifications)

### Integrations
All available integration will be listed for a form. Each integration can be enabled or disabled for a specific form. By default, these will use their globally enabled or disabled state, set at the plugin level. Any additional settings for your integrations for a form can be found here.

### Settings
Additional (advanced) settings can be managed on a form, including:

- The name for the form
- The handle for the form
- The default status submissions should receive
- The title format new submissions receive
- Whether to collect IP Addresses from users
- Whether to associate a form submission with a Craft user (if logged in)
- Data retention - How long should submission data be stored?
- Whether to retain file uploads when a submission is deleted
