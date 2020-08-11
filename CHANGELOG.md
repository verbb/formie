# Changelog

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
