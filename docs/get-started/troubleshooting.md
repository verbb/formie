# Troubleshooting
Here are some common issues with Formie, and how to solve them.

### Email notifications are only sent when visiting the control panel
This is because Formie uses Craft's queue system to send emails. It's common for emails to take up to 10+ seconds to send, particularly for SMTP-based email providers. This is also compounded by any attachments for your email. 

This leads to a bad UX for users of your form, where they are waiting for a form to be submitted. They could get impatient and navigate away before the email is sent. It also put unnecessary load on your server for high-traffic sites, where multiple form submissions would be vying for server resources to send emails. 

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
