# Troubleshooting
Here are some common issues with Formie, and how to solve them.

### Email notifications are only sent when visiting the control panel
This is because Formie uses Craft's queue system to send emails. It's common for emails to take up to 10+ seconds to send, particularly for SMTP-based email providers. This is also compounded by any attachments for your email. 

This leads to a bad UX for users of your form, where they are waiting for a form to be submitted. They could get impatient and navigate away before the email is sent. It also put unnecessary load on your server for high-traffic sites, where multiple form submissions would be vying for server resources to send emails.

For this reason, Formie uses the Craft queue system to send email notifications in the background.

But Craft's queue processing by default is only setup to run when you visit the control panel. If you find emails are only being sent when you visit the control panel, this is likely the cause.

#### What to do
We **highly recommended** you implement a proper queue-processing method that doesn't rely on someone visiting the control panel of your site.

Andrew Welch explains plenty of options in his article [Robust queue job handling in Craft CMS](https://nystudio107.com/blog/robust-queue-job-handling-in-craft-cms) on all the different ways to setup queue processing depending on your needs.

:::tip
This is worth doing not just for Formie, but for all Craft installs in general. There's lots of things Craft does via the queue, from image transforms, to refreshing search indexes, to even sending Craft Commerce emails (if installed). As such, you'll get an overall better-performing site with a properly configured queue system.
:::

But you can also disable queue processing altogether, so long as you understand the implications of doing so, as per the above explanation of why this is the default behaviour for Formie. Toggle the `useQueueForNotifications` [config setting](docs:get-started/configuration), or toggle "Use Queue for Notifications" in the control panel via Formie → Settings → Submissions.

### Integrations are only run when visiting the control panel
For the same reason as above for email notifications, Integrations use the queue for sending information to third-parties.

Follow the same steps as above to fix this.

### My form submission was successful, but no submission in the control panel
The most common case of this happening is when your submission has been marked as spam. By default, Formie will act upon a spam submission like it was a successful form submission. Otherwise, sending a failed response to bots and attackers show that their attack didn't work, and they need to adapt and continue their attack.

You can view spam submissions in the control panel by navigating to Formie → Submissions, and selecting the "Spam" status from the status dropdown (next to the search field) at the top of the page.

### Twig in a Form Causes an Error
Some form settings accept Twig, such as an HTML field's **HTML Content**, email notification content, a File Upload field's **Upload Location**, a PDF template's **Filename Format** and webhook URLs. Anyone who can edit forms can change these settings, so Formie renders their Twig in a restricted sandbox that can't reach the rest of your site. Twig in your own templates, including email and PDF template files, isn't affected.

When Twig in one of these settings uses something the sandbox doesn't allow, Formie throws an error. For an HTML field, that error stops the page containing the form from rendering. The message names what was blocked, such as:

```
Variable "craft" does not exist in "__string_template__…" at line 1.
Unknown "url" function in "__string_template__…" at line 2.
Calling "limit" method on a "craft\elements\db\EntryQuery" object is not allowed in "__string_template__…" at line 1.
Tag "include" is not allowed in "__string_template__…" at line 1.
```

#### What Form Twig Can Use
Twig in an HTML field has the `currentSite`, `siteName` and `siteUrl` variables, along with any [custom variables](docs:theming/render-options#custom-variables) you pass when rendering the form. Craft's global variables such as `craft`, `now` and `currentUser` aren't available, so form Twig can't run its own element queries. Craft's functions such as `url()` and most of its filters such as `md` aren't available either.

The sandbox allows the `for`, `if` and `set` tags, and the `date`, `max`, `min` and `range` functions. It allows these filters:

`abs`, `camel`, `capitalize`, `currency`, `date`, `datetime`, `default`, `escape` (or `e`), `first`, `format`, `join`, `kebab`, `keys`, `last`, `length`, `lower`, `merge`, `money`, `nl2br`, `number_format`, `percentage`, `raw`, `replace`, `reverse`, `round`, `slice`, `split`, `striptags`, `t` (or `translate`), `time`, `timestamp`, `title`, `trim` and `upper`.

For elements, form Twig can read `id`, `uid`, `title`, `slug`, `uri`, `url`, `site`, `siteId`, `status`, `enabled`, `dateCreated`, `dateUpdated` and the element's custom fields. Entries also allow `postDate`, `expiryDate`, `section`, `type` and `author`. Element queries allow `one()`, `all()` and `count()`, but not parameters such as `section()` or `limit()`.

#### Pass Data From Your Template
When form Twig needs entries or other site data, query it in your own template and pass the results to the form as a custom variable. For example, say a contact form has an HTML field listing the latest news. In the template that renders the form, fetch the entries and pass them as `latestNews`:

```twig
{% set latestNews = craft.entries.section('news').limit(3).all() %}

{{ craft.formie.renderForm('contactForm', {
    latestNews: latestNews,
}) }}
```

The HTML field's **HTML Content** can then loop over `latestNews`:

```twig
<ul>
    {% for entry in latestNews ?? [] %}
        <li><a href="{{ entry.url }}">{{ entry.title }}</a></li>
    {% endfor %}
</ul>
```

Visitors see a link to each of the three latest news entries. The `?? []` keeps the field working wherever the form is rendered without `latestNews`, which would otherwise cause a `Variable "latestNews" does not exist` error.

#### Allow More in the Sandbox
If form Twig needs a tag, filter, function, method or property the sandbox blocks, a developer can allow it with the [modifyTwigEnvironment](docs:developers/events#the-modifytwigenvironment-event) event. Anything you allow is available to everyone who can edit forms, so only allow what you'd trust them with. The event can't add variables such as `craft`, or Craft's functions and filters the sandbox doesn't load, such as `url()` and `md`.
