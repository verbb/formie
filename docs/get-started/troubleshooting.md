# Troubleshooting
Here are some common issues with Formie, and how to solve them.

### Email notifications are only sent when visiting the control panel
This is because Formie uses Craft's queue system to send emails. It's common for emails to take up to 10+ seconds to send, particularly for SMTP-based email providers. This is also compounded by any attachments for your email. 

This leads to a bad UX for users of your form, where they are waiting for a form to be submitted. They could get impatient and navigate away before the email is sent. It also put unnecessary load on your server for high-traffic sites, where multiple form submissions would be vying for server resources to send emails. 

But Craft's queue processing by default is only setup to run when you visit the control panel. If you find emails are only being sent when you visit the control panel, this is likely the cause.

#### What to do
We highly recommend you read the following guides on ensuring your Craft install is [properly configured for email delivery](https://craftcms.com/guides/why-doesnt-craft-send-emails#setting-up-email), and your [runQueueAutomatically](https://docs.craftcms.com/v4/config/config-settings.html#runqueueautomatically) config setting.

Andrew Welch explains it best in his article [Robust queue job handling in Craft CMS](https://nystudio107.com/blog/robust-queue-job-handling-in-craft-cms) on all the different ways to setup queue processing depending on your needs.

:::tip
This is not only worth doing just for Formie, but for Craft installs in general. There's lots of things Craft does via the queue, from image transforms, to refreshing search indexes, to even sending Craft Commerce emails (if installed). As such, you'll get an overall better-performing site with a properly configured queue system.
:::

### Integrations are only run when visiting the control panel
For the same reason as above for email notifications, Integrations use the queue for sending information to third-parties.

Follow the same steps as above to fix this.