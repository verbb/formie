# Create Email Notifications just using Twig

Formie exposes its services to Twig like any Craft plugin. You can create notifications programmatically — useful for front-end admin areas or one-off setup templates.

## Prerequisites

- [Email Notifications](/forms/email-notifications)
- A form handle you can load in Twig

## Example: create a notification in Twig

```twig
{% set form = craft.formie.forms.handle('contactForm').one() %}

{% if not form %}
    {% exit 404 %}
{% endif %}

{% set notification = create({
    class: 'verbb\\formie\\models\\Notification',
    formId: form.id,
    name: 'User Notification',
    subject: 'Thanks for your enquiry',
    to: '{field.emailAddress}',
    recipients: 'email',
    content: create('verbb\\formie\\models\\RichText', ['<p><variable-tag value="{allFields}" label="All Fields"></variable-tag></p>']).toJson(),
}) %}

{% do craft.formie.getPlugin().getNotifications().saveNotification(notification) %}
```

## How it works

`create()` instantiates a PHP class from Twig. Pass the class namespace and constructor properties.

For notifications:

- **`to`** supports variable tokens such as `{field.emailAddress}` (use your field handle)
- **`content`** must be stored in the ProseMirror JSON format the notification editor expects

The `Renderer` converts HTML (including `variable-tag` elements) into that schema.

Saving goes through `craft.formie.getPlugin().getNotifications().saveNotification()`.

## Be careful with re-runs

Every time this template renders, it creates a new notification. Guard the template — run it once during setup, or check whether the notification already exists before saving.

## Related

- [Email Notifications](/forms/email-notifications)
