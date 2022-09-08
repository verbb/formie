# Data Retention
An important part in any system that deals with user-provided content is privacy and data retention. Formie provides a handful of settings for you to manage user submissions. Each setting can be managed per-form (see [Forms](docs:feature-tour/forms)), where you can opt to retain submission data for a set number of minutes, hours, days, weeks, months or years.

## Disable Saving Submissions
It's currently not possible to completely disable storing user submissions to the database. There are a few reasons for this:

### Multi-page Forms
Whilst Ajax-forms are not an issue, Page Reload forms are, in that content needs to be stored from one page request to another. Not to mention when a user refreshes the page, or navigates across multiple pages, their content from previous pages would be gone.

### Queue
Email Notifications and Integrations can (and should) be run from a queue to prevent delays when users submit the form. However, because the queue system is designed to run in the background, and potentially at some future point in time, we need to store the submission information _somewhere_ in order for the queue job to use that information.

In addition, because _both_ Email Notifications and Integrations can have multiple instances, it becomes difficult to know when all these tasks have been completed. Otherwise, we could make use of knowing when the last task was performed.

### Recommended Approach
As the above explains, there are some caveats to disabling storing of submissions. However, we do have a recommended approach that will store submission data, but for the least amount of time necessary.

Firstly, it's recommended you disable the "Use Queue for Notifications" and "Use Queue for Integrations" plugin settings. Otherwise, you run the risk of pruning your submissions before Email Notifications or Integrations have run within the queue.

Secondly, ensure your forms have been set up with an appropriate data retention value. This could be 2 weeks, 24 hours or even 1 minute.

Finally, you'll want to set up an ongoing process that ensures submissions are pruned. Whilst we make use of [Craft's Garbage Collection](https://craftcms.com/docs/3.x/gc.html), to automatically prune submissions, there's no guarantee these are run in a timely fashion. It is instead much safer to run this cleanup on-demand.

The easiest method is to set up a cron job on your server to ensure submissions are pruned:

```shell
*/1 * * * * ./craft formie/gc/prune-data-retention-submissions
```

The above command would run every minute around the clock, ensuring submissions are pruned according to your data retention settings, for all forms on your site.

You may also want to disable viewing the Submissions' information in the control panel for your users. You can use the Permissions available to all users to control this by editing their user account.

## Encrypt Submission Content
For an added layer of privacy, you can set any field to have their content encrypted. This means that their content cannot be viewed in its raw form in the database, however the form submission data can still be viewed in the control panel, for authenticated users.
