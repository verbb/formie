# Changelog

## 1.3.27 - 2021-04-11

### Added
- Added extra error-catching to send notification queue job.
- Added `resave/formie-submissions` and `resave/formie-forms` console commands.

### Changed
- Update translation strings.

### Fixed
- Fixed `completeSubmission` buttons not working correctly with client-side validation enabled.
- Fixed an error when un-registering fieldtypes.
- Fixed lack of redirect support for Page Reload forms, when `completeSubmission` is used.
- Fixed auto-handle generation for forms and fields producing incorrect values when starting with a number.
- Fixed missing `type` attribute on form fields.
- Fixed form builder fields having their IDs stripped from requests, when validation fails, causing sync issues.
- Fixed Group or Repeater nested fields not getting unqiue handles.
- Fixed Rich rich text link editing not working.
- Fixed CC and BCC showing emails incorrectly for email notification previews.
- Fixed heading showing field label in edit submissions in control panel.
- Fixed Heading, HTML and Section fields appearing in exports as columns.
- Fixed being unable to select site-specific entries for “Redirect Entry”.
- Fixed Sendinblue email marketing integration throwing an error when only emaila address is mapped.

## 1.3.26 - 2021-04-02

### Added
- Added `autocomplete` option to Address field, for use in GraphQL.
- Added payload info to integration logging.
- Added `FormIntegrationsInterface` for GraphQL, to return information of integrations for a form.
- Added `notification` and `submission` properties to `Emails::EVENT_AFTER_SEND_MAIL`.
- Added `siteId` as a mappable attributes for entry element integrations.
- Added support for entry element integrations to have the entry `siteId` attribute set to the same site the submission is made on, by default.

### Changed
- Improve element integration error logging.
- Disable Section and HTML fields from being able to be used in integration mapping (they do nothing).

### Fixed
- Fixed incorrect validation message for Time field in Date field, complaining about 24-hour values.
- Fixed Time field for Date fields incorrectly converting time values to site timezone.
- Fixed multi-line fields with rich text set, not rendering raw HTML in email notifications.
- Fixed Phone field values potentially returning an invalid value, when no value provided.
- Fixed an error (not firing) for a failed submission through GraphQL mutations.
- Fixed GraphQL mutations permissions for submissions.
- Fixed entry element integration not working correctly for entry types with dynamic title.
- Fixed an error when trying to parse Checkboxes and Multi-Dropdown fields for spam-checks.
- Fixed error when trying to output Checkboxes and Multi-Dropdown fields in email notifications.

## 1.3.25 - 2021-03-22

### Added
- Added “Ajax Submission Timeout” form setting to control the timeout for the XHR request, for Ajax-based forms, using the Theme JS.

### Fixed
- Fixed Agile CRM integration not updating existing contacts correctly.
- Fixed Sendinblue integration not subscribing users to the chosen list.
- Fixed an error when saving a submission in the control panel with a user with restricted permissions.

## 1.3.24 - 2021-03-18

### Changed
- Refactor JavaScript captcha for more error-handling, support for cached forms and removal of inline `<script>` tags.

### Fixed
- Fixed Date fields throwing an error when the default date set to "None" and rendering inputs or a dropdown.
- Fixed Date fields always selecting the first option in the list, when set to dropdown.
- Fixed JavaScript captcha outputting inline `<script>` tags.
- Fixed JavaScript captcha throwing a "modified form" browser warning.
- Fixed form settings not updating when using Blitz caching.
- Fixed element fields restricting values to only the default value.

## 1.3.23 - 2021-03-16

### Added
- Added `modifyPrefixOptions` event for name fields.
- Added `Mx.` to name field prefixes.
- Allow the `<form>` element to defined the `action` and `method` settings of Ajax requests, and fix POST-ing to site roots with redirects configured.

### Fixed
- Fixed `redirectUrl` not working correctly when using `form.setSettings()` in your templates for Ajax forms.
- Fixed the default template in the control panel (when clicking on “Formie” in the CP nav) throwing an error when trying to load a page the user doesn’t have access to.
- Fixed Sent Notification preview column throwing an error in the control panel.
- Fixed being unable to save a submission in the control panel, when specific form permissions were set.
- Fixed an error when rendering elements fields as checkboxes.
- Cleanup uninstall, fix an error during uninstall, fix not deleting submissions and forms on uninstall.
- Fixed Categories fields not populating their value correctly when `limit` was also set.
- Fixed Entries fields not populating their value correctly when `limit` was also set.
- Fixed Products fields not populating their value correctly when `limit` was also set.
- Fixed Tags fields not populating their value correctly when `limit` was also set.
- Fixed Users fields not populating their value correctly when `limit` was also set.
- Fixed Variants fields not populating their value correctly when `limit` was also set.

## 1.3.22 - 2021-03-09

### Added
- Added “ID” to available submissions index columns.
- Added “ID” to available forms index columns.
- Added “Submission ID” to the edit page for submissions.
- Added “Form Name” to integration mapping and email notification condition variable pickers.
- Added setting to Hidden field to include or exclude their content in email notifications.
- Added “All Visible Fields” options to email notifications, outputting field content only for fields that are visible.
- Added `EVENT_AFTER_SUBMISSION_REQUEST`.
- Added support for querying and mutating Group and Repeater fields for GraphQL.
- Added support for updating entries for Entry integration.
- Added support for updating users for User integration.
- Added support for creating a new draft for Entry element integration.

### Changed
- Update default submission titles to `D, d M Y H:i:s` (eg, “Thu, 04 Mar 2021 10:50:16”).
- Minor performance improvement when submitting submissions, when no custom title format is set.

### Fixed
- Fixed Number field not having the correct type for GraphQL queries.
- Fixed an error with HubSpot CRM, when mapping a field to the Tracking ID for forms.
- Fixed Date field not having the correct type for GraphQL queries.
- Fixed reCAPTCHA placeholders not being found for custom-templated forms that have no pages containers.
- Fixed custom submission titles not working correctly when using submission attributes (namely submission ID).
- Fixed Sent Notifications index not ordering by descending by default.
- Fixed multi-line text fields not having their content passed through `nl2br` in email notifications.
- Fixed address fields not showing the correct preview in the control panel when “Auto-complete” was enabled.
- Fixed element integrations incorrectly mapping fields and attributes when no value supplied.
- Fixed fields not having their `formId` attribute set correctly.
- Fixed GQL errors when querying subfields inside group/repeater fields.
- Fixed “Reply To” setting for email notifications not being properly parsed for environment variables.
- Fixed email parsing error for email notifications in rare circumstances (where an env variable contained spaces).

## 1.3.21 - 2021-03-01

### Fixed
- Updated front-end JS to catch ajax-based forms network errors and timeouts.
- Fixed toggling checkboxes triggering required states, when the field wasn’t required at all.
- Removed `siteRootUrl` for included JS, causing issues with JS form submissions on some sites, where cross-domain issues arise. Rely on `siteId` param to determine current site..
- Fixed an error with HTML field when “HTML Content” was empty.
- Fixed Name, Address and Date fields not applying an `id` attribute of legends for accessibility.
- Ensure all front-end field legends output, even when hidden (using `fui-sr-only`) for accessibility.

## 1.3.20 - 2021-02-26

### Added
- Added logging to submit action, capturing form content immediately and saving to logs.

### Changed
- Allow both 0.3.0 and 0.4.0 guzzlehttp/oauth-subscriber. (thanks @verbeeksteven).

### Fixed
- Fixed sub-fields (Name, Address, etc) not working for pre-populating values.
- Fixed value of checkbox fields for integrations when mapping to a plain text field. Field values are now sent as comma-separated.
- Fixed InfusionSoft sending incorrect Phone Number payload values. (thanks @dubcanada).
- Fixed submission error logs not saving log information for ajax forms.
- Fixed Salesforce duplicate leads throwing an error.

## 1.3.19.1 - 2021-02-24

### Added
- Added `populateFormValues ` GraphQL argument to pass options into `templateHtml` render function. (thanks @jaydensmith).
- Added `options` GraphQL argument to pass options into `templateHtml` render function. (thanks @jaydensmith).

### Fixed
- Fixed validation error for new Email Address fields, introduced in 1.3.19.

## 1.3.19 - 2021-02-24

### Added
- Added support for `populateFormValues` to Group fields.
- Added support for `populateFormValues` to Repeater fields.
- Added “Order By” setting for all element fields to control the order options are rendered by.
- Added Checkbox Toggle to Checkboxes fields, providing the ability to toggle all checkbox fields at once.
- Added “Usage Count” column to Forms, to show the number of elements relating to each form.
- Added “Validate Domain (DNS)” setting for email address fields.
- Added “Blocked Domains” setting for email address fields.
- Added tags to Agile CRM integration. (thanks @jaydensmith).

### Fixed
- Fixed element fields not rendering correctly in email notifications, when including a single field token.
- Fixed “Resend” button when editing a sent notification.
- Fixed provider errors for all integrations getting truncated text.
- Fixed potential issue with `siteRootUrl` on site setups with redirects setup to include trailing slashes in URLs.
- Fixed a HubSpot integration form error when some context values (IP) isn’t always available.
- Fixed “Save as a new Form” not generating a nice, sequential handle.
- Fixed an issue where a failed “Save as new form” would retain the incorrect form settings.
- Fixed form errors not showing full error text in control panel.
- Fixed Repeater and Group field styling when editing a submission in the control panel.
- Fixed JavaScript captcha when using template caching not working.

## 1.3.18 - 2021-02-20

### Added
- Added support for HubSpot form integration.
- Added opt-in field support to all CRM integrations.
- Added support for field options defined in HubSpot (for dropdown, select, etc).
- Added custom field support for Sendinblue integration.
- Added `Min File Size` setting for File Upload fields.
- Added support for global variables in Spam Keywords.

### Changed
- Update some email marketing integrations to fetch custom list fields more efficiently.
- Prevent email notifications sending 0kb file uploads. This can lead to spam filters marking the email as invalid.

### Fixed
- Fixed "minutes" to be given a retention length. (thanks @nickdunn).
- Fixed `endpoint` and `method` properties missing from `SendIntegrationPayloadEvent` for Email Marketing and CRM integrations.
- Fixed nested fields (Group, Repeater) not having inner field JS initialized properly.
- Fixed spam checks for some field types.
- Fixed tag fields throwing an error when set to “dropdown” and editing a submission.
- Fixed case insensitivity (not working) for spam keywords.
- Fixed multiple fields with JS config not initialising correctly.
- Fixed Group or Repeater nested fields not getting unqiue handles when cloning.
- Fixed Repeater fields not having their inner fields’ JS initialized properly.
- Fixed some fields (Element, Repeater, Group) not extracting content for spam keyword checks.
- Fixed submission success messages including submission content not working.
- Fixed existing fields not appearing for the form builder.
- Fixed some fields (plain text and other simple fields) not having their labels correctly translated for email notifications.

## 1.3.17 - 2021-02-13

### Added
- Added better caching to `getParsedValue`, which fix a few rare issues with field rendering stale content.
- Allow `formieInclude()` to allow multiple templates (array syntax) to be passed in to be resolve.
- Added support for Integration settings for Stencils.
- Added support for paginated lists for Sendinblue integration.
- Added error logging for email delivery.
- Added ability to set the default value for all Element fields.
- Added checkboxes and radio button display types for Element fields.
- Added support to Element fields to customise the content used for labels, instead of just title.
- Added `submission` to `Submission::EVENT_DEFINE_RULES` event.
- Added Tracking, Account and Campaign IDs to SharpSpring CRM integration.
- Added `configJson` and `templateHtml` to FormInterface for GraphQL.
- Added `ModifyFormRenderOptionsEvent`. (thanks @jaydensmith).
- Added `getIsTextInput` method to Phone field. (thanks @jaydensmith).
- Added `formConfig` to the `initForm()` JS function to provide an object with the form config, rather than rely on the DOM to set it (using `setAttribute('data-config’)`).

### Changed
- Changed `Field::getEmailHtml()` to require a notification model.
- Any globally-enabled captchas will be automatically enabled on new forms.
- Any globally-enabled captchas will be automatically enabled on new stencils.

### Fixed
- Fixed an error for email notifications if its conditions contained an element field, set to `contains` as a condition.
- Fixed Variant field not providing data to integrations correctly.
- Fixed Categories field not providing data to integrations correctly.
- Fixed Entries field not providing data to integrations correctly.
- Fixed File Upload field not providing data to integrations correctly.
- Fixed Products field not providing data to integrations correctly.
- Fixed Tags field not providing data to integrations correctly.
- Fixed Users field not providing data to integrations correctly.
- Fixed email notification conditions not properly testing against element field values (entries, categories, etc).
- Fixed checkbox and radio fields having JS validation checks bound multiple times in a form.
- Fixed checkbox fields producing multiple errors.
- Fixed some reCAPTCHA plugin settings not saving correctly.
- Fixed reCAPTCHA not initializing when the surrounding form was initially hidden (for example, in a modal).
- Fixed multiple forms on a single page not having their associated field JS initialized properly.
- Fixed migration error for Postgres.
- Fixed security warnings of using `eval()` in front-end JS, despite it being safe to call.
- Fixed edit field button in control panel sizing being too small.
- Fixed assuming `TEMPLATE_MODE_SITE` when rendering templates for forms, pages and fields.
- Fixed template conditional that could cause the form to be hidden if “Hide Form” was set, but “Action on Submit” was set to “Display a message”.
- Fixed a JS error with some fields (address auto-complete, multi-line) in some instances.
- Fixed minor `e.g.` typo for stencils.
- Fixed `getValue` method on BaseOptionsField not returning correctly. (thanks @javangriff).

## 1.3.16.1 - 2021-01-31

### Fixed
- Fix an error with `ModifyFieldValueForIntegrationEvent`.

## 1.3.16 - 2021-01-31

### Added
- Added autocomplete value to Address field in email templates.
- Added `SubmissionExport::EVENT_MODIFY_FIELD_EXPORT` to allow modification of values for fields when exporting submissions.
- Added `Submission::EVENT_MODIFY_FIELD_VALUE_FOR_INTEGRATION` to allow modification of submission field values before they’re used in Integrations.
- Added `minutes` as an option for submission data retention.
- Added more feedback for garbage-collection tasks when run directly from the CLI.

### Fixed
- Fixed an error when creating new email templates in an empty directory.
- Fixed email and form templates not retaining “Copy Templates” value after validation.
- Fixed Users field not setting “All users” as default sources when creating a new field.
- Fixed Variant field not exporting correctly.
- Fixed Users field not exporting correctly.
- Fixed Tags field not exporting correctly.
- Fixed Products field not exporting correctly.
- Fixed File Upload field not exporting correctly.
- Fixed Entries field not exporting correctly.
- Fixed Categories field not exporting correctly.
- Fixed Checkboxes field not exporting correctly.
- Fixed Agree field not exporting correctly.
- Fixed an error when trying to delete submissions from the CLI.
- Fixed an error when disconnecting an OAuth-based integration.
- Fixed Salesforce and Zoho CRM integrations not persisting values returned from provider authentication.
- Ensure error message is logged for failed pruning of submission tasks.

## 1.3.15 - 2021-01-29

### Added
- Added more logging info for email notifications, when failed to send.

### Fixed
- Fixed an error when viewing sent notifications, when the submission was deleted.
- Fixed some errors for sent notifications for a deleted submission or form.
- Fixed Zoho integration where the authentication response didn’t contain the required `api_domain`.
- Fixed Salesforce integration where the authentication response didn’t contain the required `instance_url`.
- Fixed failed email notifications not showing the full error in the control panel, when running email notifications via queues.

## 1.3.14 - 2021-01-28

### Added
- Add `completeSubmission` param to forms, to allow providing a full payload and complete the submission.

### Changed
- Saving spam submissions is now enabled by default.
- Agree field description static translation is now defined in `site.php`.

### Fixed
- Fixed IE11 compatibility with some front-end fields (Address, Phone, Repeater).
- Fixed hidden fields not having input attributes setting.
- Fixed description for Agree field being translated twice.
- Fixed error in Craft 3.6+.
- Fixed submissions failing if `spamKeywords` setting was invalid.
- Fixed error when querying form settings using GraphQL

## 1.3.13 - 2021-01-24

### Added
- Added support to Mailchimp integration for tags.

### Fixed
- Fixed an error with Webhook integrations.

## 1.3.12 - 2021-01-23

### Added
- Added support for selecting existing notifications from stencils, when adding a notification to a form.
- Added support for Webhook integration URLs to contain submission variables through shorthand Twig.
- Added support for Freeform and Sprout Form migrations to be run when `allowAdminChanges = false`.

### Fixed
- Fixed existing notifications not appearing when editing a stencil.
- Fixed some potential errors with Sprout Forms migration and address/name fields.
- Fixed Freeform migration not migrating fields correctly.
- Fixed Sprout Forms migration not migrating fields correctly.
- Fixed Sprout Forms migration for Agree field, where the message description would be blank.
- Fixed Sprout Forms migration for File Upload field, where the selected volume wasn’t migrated.
- Fixed Sprout Forms migration for Categories, Entries, Tags and User fields, not setting the `selectionLabel` to the `placeholder` value. 
- Fixed some potential errors with Sprout Forms migration and phone fields.
- Fixed “Save as Stencil” not saving data correctly, by not stripping out page, row and field IDs.

## 1.3.11 - 2021-01-21

### Added
- Added a ‘pageIndex’ param to the submit action. (thanks @joshuabaker).
- Added pagination-helper for ActiveCampaign integrations. The integration will now automatically fetch greater-than 100 resources such as lists.

### Changed
- Changed ActiveCampaign integrations to fetch at least 100 of each resource.

### Fixed
- Fixed deprecation notice for Repeater field. (thanks @danieladarve).
- Fixed ajax forms not redirecting correctly, when overriding `redirectUrl` in templates.
- Fixed HTML field not parsing Twig content in some instances.
- Fixed Entry mapping throwing an error when setting the author to an Entry field.
- Fixed ActiveCampaign integration not showing connection status correctly.
- Fixed sub-fields (Name, Address, Phone) not having their values concatenated for integrations, when not selecting a sub-field.

## 1.3.10 - 2021-01-16

### Added
- File Upload fields now show a summary of uploaded files for multi-page forms. When navigating back to a page with the field, you'll see this summary.
- Ajax-enabled multi-page forms now smooth-scrolls to the top of the loaded page when going to the previous or next pages.
- Front-end validation now adds a `fui-error` class on the `fui-field` element, when a validation error occurs, instead of just on the input.

### Fixed
- Fixed an error when using checkboxes in email notification conditions.
- Fixed a potential error when viewing a submission in the control panel.
- Fixed Address field auto-complete value not persisting on front-end submissions.
- Fixed being unable to set the `siteId` of submissions through GraphQL.
- Fixed submissions not being able to be mutated through GraphQL for non-public schemas.
- Fixed Group and Repeater fields not saving content correctly for non-primary sites.
- Fixed flicker in Safari for the form builder, when hovering over draggable elements.
- Fixed Phone field exporting not formatting international phone numbers correctly.
- Fixed Phone field not always remembering the country dial code selected.
- Fixed Phone field triggering content change warnings for country-enabled fields. This was due to numbers being formatted on-load..

## 1.3.9 - 2021-01-12

### Added
- Added support for Gatsby Source Plugin and Form elements.
- Added support for Gatsby Source Plugin and Submission elements.
- Added support to set the `formId` for a form. This is used as the unique identifier for the `id` attribute and connection JS to the form.

### Changed
- User element integrations now clear any field content mapped to the password field, once the user element is created.

### Fixed
- Fixed name and address fields not showing content in columns, when editing a submission in the control panel.
- Fixed some fields (checkboxes) throwing errors in email notifications.
- Fixed non-multiple name field causing an error in email notifications.
- Fixed non-utf8 characters in email notification email values causing errors.
- Fixed email notification logging message to properly include the template it tried to resolve.

## 1.3.8 - 2021-01-10

### Added
- Added “Country Code” to Phone model, allowing the raw country code (eg “+1”) to be used in email notifications and submissions for a Phone field
- Added `form.setFieldSettings()` function to provide render-time overrides to form fields and their settings.

### Changed
- Change email notification variable output to only contain the value for the field, instead of including the field label/name.

### Fixed
- Fixed default stencil’s “To” and “Reply To” variable fields not being correct.
- Fixed email notification preview not working for stencils
- Fixed form templates with custom template path not saving correctly
- Fixed JavaScript captcha not working correctly for multiple instances of the same form on a page.
- Fixed an error with the HTML field
- Fixed server-side validation errors with Phone field
- Fixed phone numbers not being created as international numbers, when previewing an email notification

## 1.3.7 - 2020-12-23

### Fixed
- Fixed Agile CRM mapping email, website and phone to contacts. 
- Fixed deleting a submission via GraphQL not returning the correct success/fail state.
- Fixed deleting a submission via GraphQL not working for non-default sites.

## 1.3.6 - 2020-12-22

### Fixed
- Fixed element integrations not having their error messages translated correctly (and not containing the required logging detail).

## 1.3.5 - 2020-12-22

### Added
- Added ability to provide htmlpurifier config JSON files for HTML fields.
- Added `ModifyPurifierConfigEvent`.
- Added Agile CRM integration.
- Added Copper CRM integration.
- Added Capsule CRM integration.
- Added all global sets into variable-enabled fields.

### Changed
- Improve performance for very large forms and fields.

### Fixed
- Fixed an error when viewing a trashed submission, with custom fields selected in columns.
- Fixed no captchas appearing in plugin settings.
- Fixed potential error thrown, when trying to catch _other_ errors during older updates.
- Fixed Oauth-based integrations not allowing connection when `'allowAdminChanges' => false`.
- Fixed an error with the recipients field.
- Fixed form permissions not always being run for new forms.
- Fixed “Save as a new form” not working in some cases.
- Fixed multi-page form submissions incorrectly validating fields when going back to a previous page.
- Fixed some fields not displaying correctly in notification emails.
- Fixed sent notifications not always showing the HTML body content.
- Fixed form and email templates not resolving to single template files correctly.
- Fixed an error when trying to delete a submission using GraphQL.

## 1.3.4 - 2020-12-16

### Added
- Added `formie.cp.submissions.edit` template hook.
- Added `formie.cp.submissions.edit.content` template hook.
- Added `formie.cp.submissions.edit.details` |template hook.
- Added `formie.cp.sentNotifications.edit` template hook.
- Added `formie.cp.sentNotifications.edit.content` template hook.
- Added `formie.cp.sentNotifications.edit.details` template hook.
- Update Autopilot integration to include more default fields and fix list-subscribing.
- Added ability to add soft line-breaks to email notifications and other rich-text enable fields.

### Changed
- Pages now have a unique ID, inherited from the form’s `formId`.

### Fixed
- Fixed rendering the same form multiple times on a page not working correctly.
- Fixed “Unknown Integration” error message when trying to connect an integration with `allowAdminChanges = false`.
- Fixed captcha settings resetting when saving plugin settings.
- Fixed the `siteRootUrl` to trim the trailing slash if present. This is an issue on some systems (Servd) where URLs with a trailing slash are redirected.
- Fixed field/notification edit modals not getting properly reset when hidden.
- Fixed HTML field errors when the vendor folder didn’t have write permissions (such as Servd).

## 1.3.3 - 2020-12-06

> {warning} If you are overriding templates for `field.html`, you **must** update your template to include `data-field-config="{{ field.getConfigJson(form) | json_encode | raw }}"`. This is the new and improved method for fields to define their config settings, picked up by JavaScript classes. Without making this change, field JS will not work. Refer to [this commit change](https://github.com/verbb/formie/commit/c5d5eda10b39063e1cf782b38f84bebe0da6fdf9#diff-ba26d5dbf9dcd3281c9b0b3c16f822eff1d2943c2134518d4ecea26d10907be4R90-R92).

### Added
- Added `defaultState` for GraphQL queries for Agree fields. This replaces `defaultValue`.
- Added `defaultDate` for GraphQL queries for Date fields. This replaces `defaultValue`.
- Added “Current URL” to hidden field default value options.
- Added `data-field-config` attribute to all fields that require JS.
- Added `getConfigJson()` for all fields to define settings for JS modules.

### Changed
- Formie now requires Craft 3.5+.
- Form queries via GraphQL are now no longer automatically included in the public schema.
- Submission queries via GraphQL are now no longer automatically included in the public schema.
- Submission mutations via GraphQL are now no longer automatically included in the public schema.
- When (soft) deleting a form, any submissions will also be (soft) deleted. These are also restored if the form is restored.
- Refactor JS behaviour for fields that require it. We now use a `data-field-config` attribute on the field to store JS module settings. This is then initialized once the JS has been lazy-loaded. This allows us to split configuration from initialization and may also help with custom JS.
- Renamed `Field::getFrontEndJsVariables()` to `Field::getFrontEndJsModules()`.
- Improve handling of multi-page non-ajax forms, where some fields required JS. Formie now detects what JS needs to be used for the current page for a page-reload form, or the entire form for an ajax form.
- Improve field JS to stop relying on IDs or classes to hook into field functionality. It now determines this through `data-field-config` attribute on the field wrapper element. This should allow for greater template flexibility.
- Submissions now make use of the same JS/CSS code that the front-end does.

### Fixed
- Fixed errors when garbage collection is called for sent notifications.
- Fixed when deleting a form, the submissions for that form weren't also deleted.
- Fixed an error when trying to view a submission on a deleted form.
- Fixed some GraphQL attributes not being cast to the correct type.
- Fixed some GraphQL errors for some fields.
- Fixed an error when trying to permanently delete a form.
- Fixed an error with date field using a default value.
- Fixed console error for multi-page non-ajax forms containing a phone field.
- Fixed repeater and group fields not initializing their inner fields’ JS.
- Fixed JS module code for fields being loaded multiple times when initially loading the page.
- Fixed an error for address providers when used in a Repeater field.
- Fixed address providers not checking if their provider JS is loaded correctly, in some instances.
- Fixed multi-line rich text fields loading Font Awesome multiple times.
- Fixed checkbox/radio fields not validation correctly inside a Repeater field.
- Fixed warnings/errors for JS fields, where their inputs might not exist on a page.
- Fixed Algolia Places not working correctly.
- Fixed issue where multiple ajax-based forms on a single page would have validation triggered across all forms.
- Fixed incorrect error being shown when custom server-side errors for fields are defined.
- Fixed an error when an email notification's sender email wasn't properly filtered.
- Fixed incorrect output in email notifications when using date fields.

## 1.3.2 - 2020-11-28

### Added
- Added support for using the submission ID, Title and Status in notification conditions.

### Fixed
- Fixed notification conditions not saving correctly when a field with options (dropdown, etc) was selected.
- Fixed “Submission Message” and “Error Message Position” form message parsing HTML incorrectly in some cases.
- Fixed agree field description parsing HTML incorrectly in some cases.
- Fix an error when editing stencils.
- Fix minor error handling for GQL mutations.

## 1.3.1 - 2020-11-26

### Added
- Allow field type classes to provide their own GQL attribute mappings for attributes.

### Fixed
- Fixed Ajax submissions not resolving to the correct current site when using sub-directories for sites, causing translation issues.
- Fixed agree field description not translating correctly.
- Fixed error when querying `allowedKinds` as an attribute on a file upload field with GQL.
- Fixed lack of server-side email validation for email fields.

## 1.3.0 - 2020-11-25

### Added
- Added Sent Notifications section, providing information on sent email notifications. Each Sent Notification contains delivery information and the ability to preview what was sent.
- Added resend Sent Notifications, allowing you to either resend the notification to their original recipients or nominated new ones.
- Added bulk resend Sent Notifications, either to their original recipients or nominated new ones.
- Added support for default field values to contain variable tags for autofilling user info.
- Added pre-populate setting to fields, allowing you to specify a query string param to pre-populate the field with a value.
- Added conditions to notifications. Build complex conditional rules on when to send (or not send) email notifications.
- Added better support for countries in Phone fields, now with a nicer UI for the front-end.
- Added country flags and international/national validation to Phone fields.
- Added new MultiSelect Vue component, for use in custom field schema settings.
- Added ability to control whether form submissions are stored permanently or not.
- Added settings for form submission data retention for hours, days, weeks, months and years.
- Added indicator when editing a submission when it's associated with a user.
- Added `submission->getUser()`.
- Added support for when deleted a user, any submissions associated to them can be transferred to another user, or deleted.
- Added when deleting a user, a summary of their submissions (if any) is shown in the prompt.
- Added support for when restoring a deleted user, we restore any associated submissions.
- Added settings for form submission data retention for uploaded files.
- Added `maxSentNotificationsAge` plugin setting to control sent notification pruning.
- Added `formie/gc/delete-orphaned-fields` console command.
- Added `formie/gc/prune-syncs` console command.
- Added `formie/gc/prune-incomplete-submissions` console command.
- Added `formie/gc/prune-data-retention-submissions` console command.
- Added `formie/gc/prune-content-tables` console command.
- Added variable tags to form “Submission Message” rich text field setting, allowing for the use of submission variables in the submission success message.
- Added ability to use submission attributes and fields in redirect URLs for Ajax forms.

### Changed
- Refactored Phone fields to no longer use a separate dropdown for country code.
- When deleting a user, any form submissions related to that user will be deleted, or transferred to a user of your choice. This only applies if you use the "Collect User" setting for your forms.
- Lock `fakerphp/faker` at 1.9.1 due to PHP compatibility. Hopefully also fix some composer issues when updating with `./craft update all`.

### Fixed
- Fixed critical errors when a fields' setting was removed before migration can take place (looking at you `descriptionHtml` attribute).
- Fixed `registerFormieValidation` JS event not working correctly.
- Fixed a potential error in `craft.formie.getParsedValue()`.
- Fixed error with Postgres and viewing the forms index.
- Fixed error with Postgres and viewing the submissions index.
- Fixed agree field description not outputting line breaks.
- Fixed “Submission Message” and “Error Message Position” form messages not outputting line breaks.
- Fixed form messages not being translated correctly when the form is set as Ajax submit.
- Fixed submit message not showing correctly when set to show at the bottom of the form and the form is hidden on success.
- Fixed error with sending test email notifications in some instances.

### Removed
- The following attributes on Phone fields have been removed: `showCountryCode`, `validate`, `validateType`, `countryCollapsed`, `countryLabel`, `countryPlaceholder`, `countryPrePopulate`, `numberCollapsed`, `numberLabel`, `numberPlaceholder`, `numberDefaultValue`, `numberPrePopulate`.

## 1.2.28 - 2020-11-19

### Added
- Added per-form form permissions for users.
- Added per-form submission permissions for users.

### Changed
- Change `fzaninotto/faker` to non-abandoned `fakerphp/faker`.
- Increase stencil and form settings database column sizes, for large forms.

### Fixed
- Fixed error when submitting a form on a non-primary site, when it contained a group or repeater field.
- Fixed Agree field’s description not translating correctly when using special characters.
- Fixed HTML-based form settings not translating correctly when using special characters.
- Fixed Mercury CRM not mapping email and mobile fields correctly.
- Fixed email notifications incorrectly showing element queries, when trying to output an element field’s value.
- Ensure rich text fields don’t convert underscores to italics, when using as part of field handles.
- Fixed fatal error being thrown when viewing stencils, if a stencil had invalid data.

## 1.2.27 - 2020-11-16

### Added
- Allow captchas to set a `spamReason` property, providing details on why a submission was marked as spam.
- Added “Minimum Submit Time” to Javascript captcha.

### Changed
- Remove table-padding in plugin settings.

### Fixed
- Fixed potential error when processing Monday integrations.
- Fixed front-end JS console error thrown for some fields (table, repeater) for multi-page non-ajax forms.
- Fixed Table and Repeater fields sometimes throwing an incorrect error for min/max rows when not set.
- Fixed checkbox and radio field instructions not working well when set to “Above Input” or “Below Input”.
- Fixed date fields incorrectly converting to the system timezone.
- Fixed potential issue with Name field being used in integrations.
- Fixed spam reason not showing when editing a submission in the control panel.

## 1.2.26 - 2020-11-10

### Added
- Added `afterIncompleteSubmission` event.

### Changed
- Allow incomplete submissions to be used in trigger integrations queue job

### Fixed
- Fixed error when saving a field in Postgres.
- Fixed multiple recaptchas on the same page not working correctly.
- Fixed Postgres error when deleting or restoring forms.
- Fixed date fields storing time incorrectly when a submission is saved in the control panel.
- Fixed date fields not showing the time field in the control panel when editing a submission.
- Fixed table field dropdown column options not saving.

## 1.2.25 - 2020-10-28

### Added
- Added `getIsVisible()` to all field classes.
- Added `craft.formie.getVisibleFields(row)`. For any given row, will return whether there are any visible fields.
- Added `submitActionMessagePosition` to forms to control the position of success messages.
- Added more base-field level attributes for GraphQL `FieldInterface`. No need to supply inline fragments for common attributes.
- Added `redirectUrl` to JS variables, for consistency.
- Added `redirectUrl` to form settings, allowing full override of the URL when redirecting on submission success.
- Added “Badge” setting for ReCAPTCHA V3.
- Added support for element fields in integrations mapping to string-like fields.
- Added `parseMappedFieldValue` event for integrations, allowing modification of the form submission values from Formie to the integration provider.
- Added remove row button for Table field's front-end templates.

### Changed
- Ensure row classes aren’t outputted when there are no visible fields for a given row.
- Ensure eager-loaded fields have the correct content table set.
- Minimum table field rows now create rows when initially loading the form.

### Fixed
- Fixed incorrect submission error logging.
- Fixed Campaign integration and some custom fields (like checkboxes). Be sure to re-save your form's integration settings for this to take effect.
- Fixed checkboxes field validation not working correctly.
- Fixed GraphQL `containerAttributes` and `inputAttributes` properties.
- Fixed phone number sub-field label position not working correctly.
- Fixed address, date, name and phone sub-field labels not displaying correctly for left/right alignments.
- Fixed `onFormieSubmitError` JS event not firing for server-side errors.
- Fixed submissions not showing preview of element field content for submission index columns.
- Fixed stencil notifications showing unsaved.
- Fixed error when saving a new stencil.
- Fixed error when saving a Table field in some cases.
- Fixed some min/max row checks with Table fields.

### Removed
- Removed `redirectEntry` from JS variables.
- Removed `submitActionUrl` from JS variables.

## 1.2.24 - 2020-10-20

### Added
- Added `referrer` property to integrations, to provide the URL where the submission came from.

### Fixed
- Fixed Campaign integration and error thrown when the referrer was missing.
- Fixed `beforeSendNotification` and `beforeTriggerIntegration` events not working consistently across queue jobs and non-queue.
- Fixed parsing `userIp` twice, when used in variable tags.
- Fixed an error when sending notification emails via queue jobs.
- Fixed Integration settings for forms were wiped when an integrations was disabled.
- Fixed synced fields not saving correctly when moved immediately after being added.
- Fixed file upload files defaulting to a single file being allowed to be uploaded.
- Fixed file upload fields not being able to handle multiple files uploaded.

## 1.2.23.1 - 2020-10-16

### Fixed
- Fix Agree field `descriptionHtml` error, introduced in 1.2.20.

## 1.2.23 - 2020-10-16

### Changed
- File upload fields now render links to their control-panel assets in email templates, in addition to being attachments.

### Fixed
- Fixed group fields displaying incorrectly in email previews.
- Fixed group fields not checking for nested field’s `hasLabel` attribute in email content.
- Fixed repeater fields not checking for nested field’s `hasLabel` attribute in email content.
- Fixed form settings now saving correctly when users with limited permissions save forms.
- Fixed an error with entry integrations and author.
- Element integrations now correctly translate Formie fields to Craft fields.

## 1.2.22 - 2020-10-15

### Added
- Added `descriptionHtml` for GraphQL querying Agree fields’ description.
- Table, Repeater and Rich Text JS now provide access to their JS classes on field DOM elements.
- Repeater field’s JS now triggers an `init` event.

### Fixed
- Fix integrations throwing errors when opting-out of the integration (through events or opt-in field).
- When `Validate When Typing` is set on a form, ensure that the global form error message is removed after errors are fixed (when typing).
- Fix Agree fields’ description being incorrectly formatted when calling through GraphQL.

## 1.2.21 - 2020-10-13

### Added
- Added `enableUnloadWarning` plugin setting, to control the “unload” warning for front-end forms. This warning is used to prompt users their form has changed for good UX.
- Added `renderJs` and `renderCss` options to `renderForm()`, to allow for enable/disable of resources on specific render calls.

### Fixed
- Fixed potential error with Agree field descriptions.
- Fixed incorrectly exporting all form submissions site-wide when a specific form’s submissions were selected.
- Fixed submission exports not resolving the content table correctly.
- Fixed submission element index not showing available custom fields to customise with.

## 1.2.20 - 2020-10-12

### Added
- Added more logging for webhook integrations when troubleshooting.
- Added `siteId` to submissions. Now makes it possible to know which site a submission was made on.
- Added multi-site support for submissions.
- Allow fields in submissions to be searchable.
- Added “Error Message Position” option for forms to control where form-wide errors are shown.
- Twig template code can now be included inside a HTML field.
- Setup template roots to allow for much easier template overrides.
- Allow form includes to be overridden individually.
- Allow field hooks to override field settings.
- Added `fui-next` to all forward-progressing submit buttons, except the final submit button, for multi-page forms.
- Added `inputAttributes` and `containerAttributes` for submit buttons.
- Added `defaultLabelPosition` and `defaultInstructionsPosition` to plugin settings for site-wide defaults.
- Added time label to date field.
- Address sub-fields can now be set to hidden. Assists with using only autocomplete field.
- Added User element integration.

### Changed
- Integration settings can now be viewed read-only when `allowAdminChanges` is false.
- Agree field’s description now uses HTML content.
- Re-organised front-end form includes.
- Extract submit button front-end template to its own include, to allow easier overriding.
- Recipients field label position is set to “Hidden” by default (because the default field is set to hidden).
- Address fields can now have only the autocomplete block enabled.
- Forms now no longer forces `novalidate`. HTML5 validation will trigger when Formie’s JS validation is not triggered.

### Fixed
- Fixed file upload fields in nested fields not attaching to email notifications.
- Fixed fields in nested fields showing in email notifications when their field settings don't allow it.
- Fixed an error with email notification preview, preventing CSS bleeding into the control panel.
- Fixed webhook integrations not always using the per-form defined Webhook URL.
- Fixed Formie's front-end templates, as Sass variables not being able to be overwritten. (thanks @leevigraham).
- Fixed submission field data not exporting, when exporting from “All Forms”.
- Fixed “Site Name” for variable picker reflect the site the submission was made on.
- Fixed attributes and custom fields for submissions not being able to be searched.
- Fixed rich text content not having access to all available fields in the variable picker.
- Fixed a few issues with Address field and auto-complete behaving inconsistently with other sub-fields.
- Fixed autocomplete field for Address fields not showing for submissions.
- Remove `<small>` HTML elements from instructions for front-end templates. Produced invalid HTML.

## 1.2.19 - 2020-09-26

### Added
- Allow Redirect URL for a form's settings to contain Twig.
- Added Submission ID as an available variable to pick from in notifications.

### Fixed
- Fixed an error when an Ajax-enabled form's Redirect URL setting contained Twig.
- Fixed submission variables incorrectly caching when sending multiple notifications.

## 1.2.18 - 2020-09-25

### Added
- Added `endpoint` and `method` to payload events for integrations.

### Fixed
- Fixed serialization of phone fields for integrations.
- Fixed being unable to modify payload in `beforeSendPayload` event.
- Fixed a number of fields (checkbox, radio, agree, date, phone) where the custom error message wasn't working.
- Fixed error when connecting with AutoPilot.
- Fixed image uploads not attaching to email notifications for non-local volumes.
- Ensure nested fields (group and repeater) respect MySQL table name limits. Prevents errors when saving a very long field name.
- Fixed an error when editing a form when a user doesn’t have permission for the primary site.

## 1.2.17 - 2020-09-23

### Added
- Added support for group fields and subfield-enabled fields (name, address) to be used as variable tags.
- Added ability to override form settings, classes and attributes in templates.

### Fixed
- Fixed `isJsonObject` error for Craft 3.4.

## 1.2.16 - 2020-09-22

### Fixed
- Fixed name field serializing non-multiple fields for integrations
- Fixed some fields (name, date, phone) always allowing mapping for their subfields for integrations.
- Fixed front-end submission editing not working for multi-page forms.

## 1.2.15 - 2020-09-21

### Added
- Added `status` and `statusId` to be used in GQL mutations for submissions.

### Changed
- Refactor field serialization for integrations. Provides better support for array-like data.
- Recipients field settings now no longer enforce unique values for options.
- Allow table fields in field settings to add new rows with spacebar (for accessibility).
- Switch reCAPTCHA verification servers to `recaptcha.net`. Hopefully to improve global reach, when access to `google.com` isn't allowed.

### Fixed
- Fixed checkboxes fields not serializing correctly for integrations.
- Fixed table validation rules firing for all rules, instead of the specific defined ones per its field settings.
- Fixed initialising multiple forms manually, with the JS API on the same page.
- Fixed calling `destroyForm` in the JS API not destroying event listeners correctly.
- Fixed integrations not saving when setting as disabled.
- Fixed custom fields not being registered correctly.
- Fixed submissions created via mutations in GraphQL not validating correctly.
- Fixed submissions created via mutations in GraphQL not sending email notifications.
- Fixed submissions created via mutations in GraphQL not triggering integrations.

## 1.2.14 - 2020-09-17

### Added
- Added name/address support for GraphQL mutations.
- Added Mercury CRM integration.

### Changed
- Rename `Submission URL` to `Submission CP URL` for better clarity for field variables in email notifications.

### Fixed
- Fixed Entry fields not rendering their values correctly in email notifications, when their section had no URL settings.
- Fixed Category fields not rendering their values correctly in email notifications, when their group had no URL settings.
- Fixed Product fields not rendering their values correctly in email notifications, when their product type had no URL settings.
- Fixed Tag fields not rendering their values correctly in email notifications.
- Fixed User fields not rendering their values correctly in email notifications.
- Fixed Variant fields not rendering their values correctly in email notifications, when their product type had no URL settings.
- Fixed incorrect validation when saving a recipients field with the display type set to hidden.

## 1.2.13 - 2020-09-17

### Fixed
- Fixed error when trying to submit with multiple checkboxes, in some instances.
- Ensure existing project config data (if any) is applied when installing Formie for the first time.
- Fixed error when no statuses exist. Usually caused by a project config mishap, or an incorrect installation.

## 1.2.12 - 2020-09-16

### Added
- Added recipients field. Display a field as a hidden/select/radio/checkboxes to allow dynamic recipient emails to be used in email notifications. Raw emails also aren't exposed in rendered templates.
- Added Freshsales CRM integration.

### Fixed
- Fixed error when deleting a form due to incorrect redirects.
- Fixed rare issue of being unable to create new pages if a form had zero pages.
- Fixed stencil never applying template.
- Fix email notifications being incorrectly deleted after saving the form as a new form.

## 1.2.11 - 2020-09-11

### Added
- Added CSRF controller to allow static-cached sites to handle CSRF re-generation.

### Fixed
- Fixed captchas allowing payload-sending when they don't support it, causing errors on submissions.

## 1.2.10 - 2020-09-10

### Fixed
- Fixed Entry fields not rendering their values correctly for multi-sites in email notifications.
- Fixed Category fields not rendering their values correctly for multi-sites in email notifications.
- Fixed Product fields not rendering their values correctly for multi-sites in email notifications.
- Fixed Tag fields not rendering their values correctly for multi-sites in email notifications.
- Fixed User fields not rendering their values correctly for multi-sites in email notifications.
- Fixed Variant fields not rendering their values correctly for multi-sites in email notifications.

## 1.2.9 - 2020-09-10

### Added
- Added `EVENT_MODIFY_WEBHOOK_PAYLOAD` event for Webhook integrations. Allows modification of the payload sent to webhook URLs.
- Added `EVENT_MODIFY_FIELD` event Freeform and Sprout Forms migrations. This can be used to modify the field-mapping of Freeform and Sprout Forms fields to Formie fields. Particularly useful for custom-built fields.

### Changed
- Webhook integrations URL is now optional when creating the integration.
- File upload fields now serialize the entire asset element for webhook payloads.

### Fixed
- Fixed refreshing CSRF token field triggering a changed form notice on the front-end.
- Fixed tag fields triggering a changed form notice on the front-end.

## 1.2.8 - 2020-09-10

### Added
- Integration settings now support `.env` variables.
- Entry fields can now restrict their sources to entry types.

### Changed
- Entry fields now restrict their element query to only include elements from the current site - for multi-sites.
- Category fields now restrict their element query to only include elements from the current site - for multi-sites.
- Product fields now restrict their element query to only include elements from the current site - for multi-sites.
- Variant fields now restrict their element query to only include elements from the current site - for multi-sites.

### Fixed
- Fixed composer autoload deprecations.
- Lower `league/oauth2-client` requirement to prevent incompatibility with other plugins.
- Fixed Entry fields not restricting to its sources in some instances.
- Fixed Category fields not restricting to its sources in some instances.
- Fixed Product fields not restricting to its sources in some instances.
- Fixed User fields not restricting to its sources in some instances.
- Fixed Variant fields not restricting to its sources in some instances.
- Fix hidden field throwing errors in queue jobs, for emails and notifications.
- Fixed Entry fields not restricting correctly when selecting multiple sources.
- Fixed Product fields not restricting correctly when selecting multiple sources.
- Fixed User fields not restricting correctly when selecting multiple sources.

## 1.2.7.1 - 2020-09-07

### Fixed
- Fixed a potential error when saving an integration.
- Fixed fields not always showing as available to be mapped for integrations.

## 1.2.7 - 2020-09-07

### Added
- Added plugin setting to set the default form template for new forms.
- Added plugin setting to set the default email template for new email notifications.
- Added plugin setting to set the default volume for new file upload fields.
- Added plugin setting to set the display type for new date fields.
- Added plugin setting to set the default value for new date fields.
- Integration field mapping now supports repeater and group nested fields.

### Fixed
- Fixed stencils not showing validation errors for invalid fields.
- Fixed stencils not showing validation errors for notifications.
- Fixed email notifications preview error when viewed in a stencil.
- Fixed email notifications for stencils showing as unsaved.
- Fixed error with integrations event and `isNew`.

## 1.2.6 - 2020-09-06

### Added
- Added SharpSpring CRM integration.
- Added [Campaign Plugin](https://plugins.craftcms.com/campaign) email marketing integration.
- Added Font Awesome to front-end rich text field (multi-line), for much better consistency.
- Added align options to front-end rich text field (multi-line).
- Added clear formatting option to front-end rich text field (multi-line).

### Fixed
- Fixed minor JS issue for webhook integrations in the control panel.
- Improve Multi-line WYSIWYG front-end field styles, so they aren't overridden. Lists for example now show correctly in all circumstances.

## 1.2.5 - 2020-09-04

### Fixed
- Fixed repeater and table fields duplicating new rows.

## 1.2.4 - 2020-09-03

### Added
- Allow hidden fields to be used in email-only variable fields.

### Fixed
- Fixed users field not restricting to its chosen sources.

## 1.2.3 - 2020-09-03

### Added
- Provide easier shortcuts for editing a submission on the front-end.
- Ensure all field attributes for email and form templates are translated for the front-end.

### Fixed
- Downgrade `guzzlehttp/oauth-subscriber` package to be compatible with Social Poster.

## 1.2.2 - 2020-09-02

### Fixed
- Fixed rendering issue for category fields with children.
- Fixed rendering issue for tags fields.
- Fixed JS error when rendering a tag field.
- Fixed JS error when rendering a repeater field.

## 1.2.1 - 2020-09-02

### Added
- Entry fields now support custom element sources when outputting their list of available elements.
- Category fields now support custom element sources when outputting their list of available elements.
- Product fields now support custom element sources when outputting their list of available elements.
- User fields now support custom element sources when outputting their list of available elements.
- Variant fields now support custom element sources when outputting their list of available elements.
- Added `EVENT_MODIFY_ELEMENT_QUERY` to allow modification of the query used by element fields.

### Changed
- Update modal edit windows to show “Apply” instead of “Save” - as actions aren’t immediately saved until you save the form.

### Fixed
- Fixed a minor layout issue for the email notification preview with long text values.
- Fixed rich text-enabled multi-line text fields showing raw HTML in submission.
- Fixed rich text-enabled multi-line text fields not retaining their value after an error.

## 1.2.0 - 2020-09-01

### Added
- Added Email Marketing integrations category.
- Added ActiveCampaign, Autopilot, AWeber, Benchmark, Campaign Monitor, Constant Contact, ConvertKit, Drip, GetResponse, iContact, Mailchimp, MailerLite, Moosend, Omnisend, Ontraport, Sender, Sendinblue Email Marketing integrations.
- Added CRM integrations category.
- Added ActiveCampaign, Avochato, Freshdesk, HubSpot, Infusionsoft, Insightly, Pipedrive, Pipeliner, Salesflare, Salesforce, Scoro, vCita, Zoho CRM integrations.
- Added Webhooks integrations category.
- Added Generic Webhook, Zapier Webhooks integrations.
- Added Miscellaneous integrations category.
- Added Google Sheets, Monday, Slack, Trello Miscellaneous integrations.
- Added `useQueueForNotifications` and `useQueueForIntegrations` plugin settings, to control if queue jobs should be used to send emails and trigger integrations.

### Changed
- Element and Address Provider integrations can now have multiple instances created with different settings.

## 1.1.8 - 2020-08-27

### Added
- Provide `onAfterFormieSubmit` event with content about each submission.
- Added `fieldNamespace` render option for forms.

### Fixed
- Fixed freeform migration using an array for default value for email fields.
- Fixed potential issue that a submission could get “stuck” in a completed form.

## 1.1.7 - 2020-08-20

## Added
- Added `form.settings` for GraphQL requests.
- Added badge to new notifications to prompt the need to save the form.

## Fixed
- Fixed changed notifications not prompting for changed form when trying to navigate away.
- Fixed newly created notifications getting out of sync when continuing to edit the form.

## 1.1.6 - 2020-08-20

### Added
- Added `craft.formie.populateFormValues()`. See [docs](https://verbb.io/craft-plugins/formie/docs/template-guides/available-variables).
- Added translation strings for all translatable text.
- Added setting spam state element action for submissions.
- Allow spam state to be toggled when editing a submission.

### Fixed
- Fixed GQL mutations error in Craft 3.4.
- Fixed multi-line column limit not allowing for content greater than 255 characters. Please re-save any form that uses a multi-line text field to get this change.

## 1.1.5 - 2020-08-18

### Fixed
- Fixed error introduced in Craft 3.5.5 when editing a form.
- Fixed repeater fields inner fields not using export-handling.

## 1.1.4.1 - 2020-08-18

### Fixed
- Fixed submission exports not normalising columns for repeater/table fields across multiple submissions.

## 1.1.4 - 2020-08-18

### Added
- Added GraphQL mutation support for submissions. [See docs](https://verbb.io/craft-plugins/formie/docs/developers/graphql#mutations).

### Changed
- Update exports to not split repeater/table into new rows.

### Fixed
- Fixed table field columns getting incorrect format when re-saving after a validation error.

## 1.1.3 - 2020-08-17

### Added
- Added `form.formId` and `form.configJson` shortcuts for templates.

## 1.1.2 - 2020-08-16

### Fixed
- Fixed form outputting CSS and JS, even when disabled.
- Fixed repeater field minimum instances not pre-populating the defined number of blocks.
- Fixed repeater field allowing to go below the set minimum instances.
- Fixed repeater field add block button not disabling when min and max instances are the same.
- Fixed repeater field add block not toggling disabled state correctly.
- Fixed submission exporting providing all values.
- Fixed submission CSV exports not splitting sub-field-enabled fields (address, multi-name) into multiple columns for their sub-fields.
- Fixed submission CSV exports not splitting complicated fields (repeater, table) into new rows to convey collection of data. JSON/XML exports are unchanged and show collections as arrays.

## 1.1.1.1 - 2020-08-11

### Fixed
- Fixed stencils not saving.

## 1.1.1 - 2020-08-10

### Added
- Added `outputJsBase` option for form templates.
- Added `outputJsTheme` option for form templates.
- Added `outputCssLocation` option for form templates.
- Added `outputJsLocation` option for form templates.
- Form templates can now control where CSS and JS is outputted on the page.
- Added `craft.formie.renderFormCss()` to manually render a form's CSS in your templates.
- Added `craft.formie.renderFormJs()` to manually render a form's JS in your templates.
- Added JavaScript API's and [documentation](https://verbb.io/craft-plugins/formie/docs/developers/javascript-api). Better handling with Vue.js/React.js and more.
- Allow Formie's JS to be imported into JavaScript modules.
- Improve JavaScript loading performance, by lazy-loading JS.
- Improve JavaScript by loading a single file - `formie.js`.

### Changed
- Update form template select to show correct loading indicator.

### Fixed
- Fixed reCAPTCHA JS not loading in some circumstances.
- Slightly improve email testing error message.
- Fixed date field missing subfield classes and hooks.
- Fixed error when “Output JavaScript” is set to false for a custom form template.
- Fixed multiple form error messages appearing in some cases.
- Fixed phone number field not validating server-side.
- Fixed form JS not initialising when using Vue.js as an async module.
- Fixed incorrect `columnWidth` GraphQL type.
- Fixed Form and Submission fields for GraphQL queries.

## 1.1.0 - 2020-08-03

### Added
- Added email notification testing.
- Added email notification preview.
- Added support for email notification duplication.
- Added Rich Text front-end appearance option for multi-text fields.
- Added preset options to hidden field.
- Added Element integration support.
- Added Entry element integration support.
- Added Address Providers integrations.
- Added Google Places, Algolia and Address Finder Address Providers.
- Added email alerts for failed email notifications.
- Added warning to file upload field for server-set upload limit.
- Added Ability to set "Today" as the default date for Date/Time fields.

### Changed
- HTML fields now have their content purified when output.

### Fixed
- Fixed HTML field showing ‘null’ or ‘undefined’ when no value set
- Fixed missing status when applying stencil from project config.
- Fixed incorrect email template crumb.
- Fixed issue where captchas weren't working.
- Ensure we return the current settings for failed plugin saving.
- Fix incorrect redirection when an error occurred saving the plugin settings.

## 1.0.9.1 - 2020-07-30

### Fixed
- Fixed another instance where multiple forms on the same page weren't working.

## 1.0.9 - 2020-07-30

### Added
- Added the ability to select existing notifications, made on other forms.

### Fixed
- Fixed JS error when multiple forms are on the same page.
- Fixed error messages showing for multiple forms on a single page.
- Fixed submission data not working correctly for multiple forms on a single page.

## 1.0.8 - 2020-07-30

### Fixed
- Fixed issue where “Save form as” did not properly clone repeater subfields.
- Fixed default label position class on form.
- Fixed raw submit action message being rendered for non-ajax forms.
- Ensure query string in URL is preserved when showing a success message for a form.
- Fixed front-end field error messages being overwritten on subsequent checks.

## 1.0.7 - 2020-07-28

### Fixed
- Remove unused `HtmlSanitizer` package.
- Fixed installation issue on PHP 7.0.

## 1.0.6 - 2020-07-27

### Fixed
- Allow deleting last stencil.
- Ensure form/email templates are processed in project config before stencils, preventing project config errors when applying stencils.
- Only allow “Save as a new stencil” if `allowAdminChanges` is enabled.

## 1.0.5 - 2020-07-26

### Added
- Added Rich Text field configuration plugin-wide. Provide a config object for available buttons, like you might for [Redactor](https://plugins.craftcms.com/redactor). See [docs](https://verbb.io/craft-plugins/formie/docs/get-started/configuration#rich-text-configuration).
- Added error message rich text field for form/stencil settings.
- Added GraphQL support for Submissions.
- Added support for “Save as new stencil” from a form.

### Changed
- Form/stencil submission message now support rich text.
- Form/stencil submission message is now stored as a prosemirror-compatible object.

### Fixed
- Fixed IP Address not saving for submissions.
- Fixed form change warning when submitting an Ajax form and redirecting.
- Fixed submissions query and `form` parameter not working correctly.
- Fixed “Save as new form” not redirecting to the newly created form.
- Fixed “Save as new stencil” new stencil generates a sequential handle, rather than a random handle.
- Fixed “Submission Message” error message not appearing.
- Fixed rich text fields not having their model values (resulting JSON) populated immediately.

## 1.0.4 - 2020-07-23

### Added
- Added warning to form template if using custom template.
- Added page button hooks and [docs](https://verbb.io/craft-plugins/formie/docs/developers/hooks).
- Added page index to page data attributes.
- Added `craft.formie.registerAssets` for template-cached forms. See [docs](https://verbb.io/craft-plugins/formie/docs/template-guides/cached-forms).

### Changed
- Moved template validation rule to base template so both email and form templates are validated.

### Fixed
- Fixed incorrect hooks on label-less fields (such as hidden field).
- Fixed missing button container classes.
- Fixed adding existing field always adding to first page.
- Fixed page spacing issue on multi-page Ajax forms.
- Fixed ajax-based multi-page forms validating entire form.
- Fixed CSS/JS issue with forms, when using the `{% cache %}` tag.

## 1.0.3 - 2020-07-22

### Added
- Added GraphQL support. See [docs](https://verbb.io/craft-plugins/formie/docs/developers/graphql).
- Added class to word and character limit text.

### Changed
- Cleaned up form and email templates, changing minor text, fixing some translations, typos and better field feedback.

### Fixed
- Fixed minor CSS causing field edit modal not to show when when clicking on the field label.
- Fixed missing error messages on name and address fields.
- Fixed “Save as a new form” button not saving a new form.
- Fixed checkbox-select Vue component not working correctly.

## 1.0.2 - 2020-07-21

### Added
- Added subfield hooks for name and date fields.

### Fixed
- Added missing style for left/right submit buttons.
- Fixed template theme CSS from being outputted when the layout is disabled.

## 1.0.1 - 2020-07-21

### Added
- Added hooks to address and phone subfields.

### Changed
- Move field errors outside of input containers.
- Hide required option for HTML field.

### Fixed
- Fixed db exception when saving form.
- Only send notifications for fully complete submissions.
- Fixed HTML field not wrapping in submissions edit page.
- Fixed bug where an empty repeater field with required subfields wouldn’t validate.
- Render email templates using `TEMPLATE_MODE_CP`.

## 1.0.0 - 2020-07-20

- Initial release.
