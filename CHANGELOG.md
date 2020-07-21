# Changelog

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
