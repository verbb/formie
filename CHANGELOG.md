# Changelog

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
