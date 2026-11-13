# Permissions

Formie's permissions let you give each person the access their role needs. A marketing team might build and edit the forms in their own form group, a sales team might only read and follow up the submissions for those forms, and a developer might look after integrations and spam protection on production. Set them in **Settings → Users → User Groups**, or on an individual user's **Permissions** tab.

**Access Formie** is listed under **General**, with Craft's other permissions for reaching each plugin's section. Everyone who uses Formie in the control panel needs it. The others are listed under a single **Formie** heading, so its **Select All** control selects every Formie permission at once. Its top-level permissions follow Formie's navigation, and each one contains the permissions for that part of the plugin.

Some permissions are generated from your content. Each form group adds its own form, submission and sent notification permissions, and so does each form using dedicated permissions. They're described in [Form Group and Per-Form Permissions](#form-group-and-per-form-permissions).

## Forms

| Permission | What It Allows |
| --- | --- |
| Access forms | Open **Formie → Forms**. The index only lists forms the person can view or manage. |
| Create forms | Create new forms, from scratch or from a stencil, and duplicate forms they can manage. |
| Access form appearance tab | Get the **Appearance** tab on forms they create. |
| Access form behaviour tab | Get the **Behaviour** tab on forms they create. |
| Access form email notifications tab | Add email notifications while setting up a new form, and get the **Email Notifications** tab on forms they create. |
| Access form integrations tab | Get the **Integrations** tab on forms they create. |
| Access form usage tab | Get the **Usage** tab on forms they create. |
| Access form settings tab | Get the **Settings** tab on forms they create. |
| Delete forms | Delete forms they can manage. |
| View all forms | See every form in the Forms index, without opening the form builder. |
| Manage all forms | Open every form in the form builder and save changes to its fields and pages, change its status and move it between groups. |
| Access form appearance tab | Show the **Appearance** tab in the form builder. |
| Access form behaviour tab | Show the **Behaviour** tab in the form builder. |
| Access form email notifications tab | Show the **Email Notifications** tab, and add, edit and remove the form's email notifications. |
| Access email notification advanced tab | Show the **Advanced** tab when editing an email notification. |
| Access email notification templates tab | Show the **Templates** tab when editing an email notification. |
| Access form integrations tab | Show the **Integrations** tab, and change the form's integration settings. |
| Access form usage tab | Show the **Usage** tab. |
| Access form settings tab | Show the **Settings** tab. |
| View “{group}” forms | See the forms in this form group in the Forms index. |
| Manage “{group}” forms | Manage the forms in this form group, with the same form builder tab permissions nested below it. |
| View ungrouped forms | See forms that aren't in a form group. |
| Manage ungrouped forms | Manage forms that aren't in a form group, with the same form builder tab permissions. |
| Manage “{form}” form | Manage one form that uses dedicated permissions, with the same form builder tab permissions. |

The **Fields** tab is always available to anyone who can manage a form. The tab permissions nested under **Create forms** decide which tabs a person receives on the forms they create, described in [Permissions Given to Form Creators](#permissions-given-to-form-creators). The tab permissions nested under **Manage all forms** and each **Manage** permission work on the forms that permission covers.

**Delete forms** only works together with permission to manage the form being deleted. On Craft Team, creating forms also needs **Manage all forms**.

## Submissions

| Permission | What It Allows |
| --- | --- |
| Access submissions | Open **Formie → Submissions**, and add the **Recent Submissions** dashboard widget. The index only lists forms whose submissions the person can view. |
| Export submissions | Use **Export…** on the Submissions index. The export only includes forms whose submissions the person can view, and doesn't need access to Reports. |
| View queue delivery diagnostics | Use the **Diagnostics** button on failed Formie jobs in **Utilities → Queue Manager**, and export the support bundle, for forms whose submissions they can view. |
| View all submissions | Open the submissions of every form, and see the **Results** tab in the form builder for forms with questionnaire fields. |
| Create submissions | Create new submissions in the control panel. |
| Save submissions | Edit and save existing submissions, upload files to them, re-run their integrations, and manage their payments from the control panel. |
| Delete submissions | Delete submissions. |
| View “{group}” submissions | Open the submissions of forms in this form group, with **Create submissions**, **Save submissions** and **Delete submissions** nested below it. |
| View ungrouped submissions | Open the submissions of forms that aren't in a form group, with the same three permissions nested below it. |
| View “{form}” submissions | Open the submissions of one form that uses dedicated permissions, with the same three permissions nested below it. |

**View queue delivery diagnostics** also needs Craft's permission to use the Queue Manager utility. The support bundle has credentials removed, but it can still contain submission values and provider responses, so treat it like the submissions themselves. See [Queue Diagnostics](/guides/integrations/integration-dispatch-and-policies#queue-diagnostics).

## Reports

| Permission | What It Allows |
| --- | --- |
| Access reports | Open **Formie → Reports** and run saved reports. A report only includes forms whose submissions the person can view. |
| Manage reports | Create, edit and delete reports, and export their results. |
| Export submissions | Export a saved report's results without **Manage reports**. It also allows **Export…** on the Submissions index. |

Reports are stored in each installation's database, so **Manage reports** works on production with admin changes turned off. Only admins can change or delete a report that has scheduled deliveries, because a schedule emails the report's data to its recipients whoever can view those forms. See [Reports](/reports/reports).

## Sent Notifications

| Permission | What It Allows |
| --- | --- |
| Access sent notifications | Open **Formie → Sent Notifications**. It only appears when **Enable Sent Notifications** is turned on in Formie's settings. |
| View all sent notifications | See the sent notifications of every form. |
| Resend sent notifications | Resend sent notifications. |
| Delete sent notifications | Delete sent notifications. |
| View “{group}” sent notifications | See the sent notifications of forms in this form group, with **Resend sent notifications** and **Delete sent notifications** nested below it. |
| View ungrouped sent notifications | See the sent notifications of forms that aren't in a form group, with the same two permissions nested below it. |
| View “{form}” sent notifications | See the sent notifications of one form that uses dedicated permissions, with the same two permissions nested below it. |

## Stencils, Integrations and Spam Protection

| Permission | What It Allows |
| --- | --- |
| Access stencils | Nothing on its own. It holds **Manage stencils**. |
| Manage stencils | Open **Formie → Stencils**, and create, edit and delete stencils. |
| Access integrations | Nothing on its own. It holds **Manage integrations**. |
| Manage integrations | Open **Formie → Integrations**, and create, edit, reorder and delete integrations, check their connections, and connect or disconnect provider accounts. |
| Access spam protection | Nothing on its own. It holds the two permissions below. |
| Manage spam rules | Open **Formie → Spam Protection** and change the spam rules, such as content rules, submission throttling and submission guards. |
| Manage CAPTCHAs and spam filtering services | Change the settings and credentials of CAPTCHA providers and spam filtering services under **Formie → Spam Protection**. |

Integrations, stencils, spam rules and provider settings created in the control panel are stored in each installation's database, so these permissions work on production with admin changes turned off. Project integrations and project stencils are stored in project config, so changing them also needs admin changes turned on. Using a configured integration or CAPTCHA on a form doesn't need any of these permissions. See [Integrations](/integrations/overview), [Stencils](/forms/stencils) and [Spam Protection](/forms/spam-protection).

## Settings

| Permission | What It Allows |
| --- | --- |
| Access settings | Nothing on its own. It holds the settings permissions below. |
| View encryption status | Open **Settings → Encryption** to see the encryption status, without changing it. |
| Access import/export | Open **Settings → Import/Export**. |
| Import forms | Import forms from a file, and update existing forms they can manage. |
| Export forms | Export forms they can view or manage to a file. |
| View synced field usage | Open **Settings → Synced Fields** to see where synced fields are used. |
| View scheduled reports | Open **Settings → Scheduled Reports** to see saved deliveries, without changing them. |
| Send scheduled report test emails | Send a test delivery of a scheduled report to their own email address. |
| Access support and diagnostics | Open **Settings → Get Support** to send a support request with Formie's diagnostics. |
| Migrate forms from Freeform 4 | Migrate forms from Freeform 4. The page only appears while Freeform is installed. |
| Migrate forms from Freeform 5 | Migrate forms from Freeform 5. The page only appears while Freeform is installed. |
| Migrate forms from Sprout Forms | Migrate forms from Sprout Forms. The page only appears while Sprout Forms is installed. |

These are the production tools and read-only pages. Everything else under Settings is configuration for admins, covered in [Admins and Riskier Permissions](#admins-and-riskier-permissions). See [Import & Export](/forms/import-export), [Synced Fields](/forms/synced-fields), [Scheduled Reports](/reports/scheduled-reports) and [Get Form Support](/get-started/get-form-support).

## How Permissions Are Nested

Craft only lets you select a nested permission once its parent is selected. **Access stencils**, **Access integrations**, **Access spam protection** and **Access settings** are there to group the permissions below them. Nobody gets a page from them alone, so select the permissions inside them as well. **Formie → Spam Protection** appears for anyone with either spam permission.

The other top-level permissions do open their section. Someone with only **Access forms** can reach the Forms index, but it's empty until they're also given permission to view or manage some forms. **Access submissions** and **Access sent notifications** work the same way.

## Form Group and Per-Form Permissions

Formie generates permissions for each form group you've created in **Settings → Form Groups**, plus a set for ungrouped forms. A form uses its group's permissions, so giving someone **Manage “Marketing” forms** lets them manage every form in the Marketing group, including forms added to it later. A new form group adds new permissions that nobody but admins holds until you grant them.

To control access to a single form, turn on **Use Dedicated Permissions** in the **Permissions** section of the form's **Settings** tab. On Craft Pro, the form then gets its own **Manage “{form}” form**, **View “{form}” submissions** and **View “{form}” sent notifications** permissions. Dedicated permissions are added on top of the group's permissions. Someone with the group's permissions can still reach the form, so use dedicated permissions to give extra people access to one form, rather than to keep a form from its group.

The form group and per-form permissions sit beside the **View all** and **Manage all** permissions they narrow down. Someone with **Manage all forms** or **View all submissions** doesn't need them.

## Permissions Given to Form Creators

On Craft Pro, someone who creates or duplicates a form they couldn't otherwise manage is given permission to manage it straight away, so they can keep working on it. Formie adds these to their own user permissions when the form is first saved.

- **Manage “{group}” forms** for the new form's group, or **Manage ungrouped forms**. When the form uses dedicated permissions, it's **Manage “{form}” form** instead.
- The form builder tab permissions they hold under **Create forms** or **Manage all forms**, for the same group or form.
- If they can view submissions for all forms or for that group or form, permission to view, create, save and delete its submissions.

Because these are group permissions, creating a form in a group gives its creator access to the other forms in that group too. If that's not what you want, give the person **Manage all forms** or a group's **Manage** permission directly, so they already manage the forms they create.

## Admins and Riskier Permissions

Admins can always do all of this. Formie's configuration settings are for admins only, and only where Craft's `allowAdminChanges` setting is on. That covers **General Settings**, **Forms**, **Form Groups**, **Form Builder Defaults**, **Field Types and Restrictions**, **Email Notifications**, **Sent Notifications**, **Submission Statuses**, **Form Statuses**, **Submissions**, **Integrations**, **Form Templates**, **Email Templates** and **PDF Templates**. Creating, editing and deleting scheduled report deliveries is for admins on any environment.

**Delete forms** and **Delete submissions** remove data that customers or your team rely on, so keep them for the people who own those forms. **Manage integrations** and **Manage CAPTCHAs and spam filtering services** include API keys and provider credentials, so give them to developers or whoever looks after those accounts. **Export submissions**, **View queue delivery diagnostics** and **Import forms** move form data in and out of the site, so treat them as carefully as access to the submissions themselves.
