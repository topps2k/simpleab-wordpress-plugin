# Changelog
All notable changes to the Simple A/B WordPress plugin will be documented in this file.

## [1.1.0] - 2023-05-28
### Changed
- Switched from JavaScript SDK to PHP SDK for improved performance and server-side processing.
- Updated shortcode functionality to use PHP SDK instead of JavaScript.
- Modified PHP functions for getting treatments and tracking metrics to use the new SDK.
- Updated README.md with new installation and usage instructions.

### Removed
- Removed simpleab-init.js file as it's no longer needed with the PHP SDK.

## [1.0.0] - 2023-05-21
### Added
- Initial release of the Simple A/B WordPress plugin.
- Support for A/B/n testing using shortcodes.
- Admin settings page for API configuration.
- Uninstall script for clean removal of plugin data.