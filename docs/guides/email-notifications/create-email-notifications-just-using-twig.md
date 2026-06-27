# Create Email Notifications just using Twig

The beauty of using Formie (and Craft!) is that all the plugin methods are available to you in your Twig templates. This certainly makes for some powerful templates!

One such thing we'll cover today is creating email notifications right within Twig. The use-case for this might be you're providing your clients with a front-end administration area to manage forms — as opposed to using the control panel. Your reasons for this might be varied, or if nothing more an excuse to show you the sorts of things you _could_ do.

```twig
{% set form = craft.formie.forms.handle('contactForm').one() %}

{% set renderer = create({
    class: 'verbb\\formie\\prosemirror\\toprosemirror\\Renderer',
}) %}

{% set notification = create({
    class: 'verbb\\formie\\models\\Notification',
    formId: form.id,
    name: 'User Notification',
    subject: 'Thanks for your enquiry',
    to: '{field.emailAddress}',
    recipients: 'email',
    content: renderer.render('<p><variable-tag value="{allFields}" label="All Fields"></variable-tag></p>').content | json_encode,
}) %}

{% do craft.formie.getPlugin().getNotifications().saveNotification(notification) %}
```

We use the `create()` Twig method to create an instance of a PHP class. You can use this for any of the classes that Formie offers (most commonly models), and you can of course use this for any Craft core classes. For this function, we pass the namespace of the class we want to create, along with some options. For a Notification, we'll want to add a few obvious things, such as a name, recipients, and content.

The more advanced items here are that `to` can contain variable tokens like `{field.emailAddress}` (where there's a field with the handle `emailAddress`), and the `content` needs to be formatted to be compatible with the WYSIWYG editor (ProseMirror). We create a `verbb\\formie\\prosemirror\\toprosemirror\\Renderer` instance that acts as a helper to convert HTML into ProseMirror schema, which is saved in the database. You can add whatever HTML you like (that the WYSIWYG editor supports) along with noting the syntax for the `variable-tag` element.

After that, it's just a matter of saving the notification. We call `formie.getPlugin()` to get the main plugin class, then `getNotifications()` to return the Notifications service which houses all of our CRUD logic, and finally the `saveNotification()` function with our created notification.

As soon as this template loads, a new notification will be created. You'll want to be careful, as refreshing this template will create a new notification every single time!

Hopefully, that gives you some food for thought on just what's possible with Formie. You could build reporting, submission management, and a whole lot more right in Twig, with your own opinionated styles, functionality and workflow. All you need is some time!
