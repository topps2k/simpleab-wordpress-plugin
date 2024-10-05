=== Captchify Simple A/B ===
Contributors: captchify
Tags: ab testing, experimentation, optimization
Requires at least: 5.0
Tested up to: 6.6
Stable tag: 1.1.0
Requires PHP: 7.2
License: GPL-3.0+
License URI: http://www.gnu.org/licenses/gpl-3.0.txt

Integrate Captchify Simple A/B PHP SDK for A/B/n testing into your WordPress site. Supports both dimension-based and segment-based experiments.

== Description ==

Simple A/B is a WordPress plugin that integrates the SimpleAB PHP SDK for A/B/n testing into your WordPress site. It supports both dimension-based and segment-based experiments, allowing you to optimize your website content and improve user experience.

Key features:

* Segment-based experiments
* Dimension-based experiments
* Easy-to-use shortcodes for creating A/B/n tests
* Client-side metric tracking
* PHP functions for advanced usage

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/simpleab` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Use the 'Settings' -> 'SimpleAB' screen to configure the plugin. Enter your SimpleAB API Key and save the settings.

== Usage ==

= Shortcodes =

The plugin provides two shortcodes for creating A/B/n tests in your posts or pages:

1. `[simpleab_test]` (Segment-based)

Example:
[simpleab_test experiment_id="exp_123" stage="Prod"]
Variant A content
||
Variant B content
||
Variant C content
[/simpleab_test]

2. `[simpleab_test_custom]` (Dimension-based)

Example:
[simpleab_test_custom experiment_id="exp_123" stage="Prod" dimension="desktop"]
Variant A content
||
Variant B content
||
Variant C content
[/simpleab_test_custom]

= Client-side Metric Tracking =

You can track custom metrics using data attributes on HTML elements within your variant content:

<button data-simpleab-metric="button_clicks" data-simpleab-aggregation="sum" data-simpleab-value="1" data-simpleab-events="click">
  Click me!
</button>

= PHP Functions =

The plugin also provides PHP functions for advanced usage. Refer to the plugin documentation for more details.

== Frequently Asked Questions ==

= How do I create an experiment? =

Experiments are created and managed through the SimpleAB dashboard. This plugin allows you to integrate those experiments into your WordPress site.

= Can I use this plugin with other A/B testing services? =

No, this plugin is specifically designed to work with the SimpleAB service.

== Changelog ==

= 1.1.0 - 2023-05-28 =
* Changed: Switched from JavaScript SDK to PHP SDK for improved performance and server-side processing.
* Changed: Updated shortcode functionality to use PHP SDK instead of JavaScript.
* Changed: Modified PHP functions for getting treatments and tracking metrics to use the new SDK.
* Changed: Updated README.md with new installation and usage instructions.
* Removed: Removed simpleab-init.js file as it's no longer needed with the PHP SDK.

= 1.0.0 - 2023-05-21 =
* Added: Initial release of the Simple A/B WordPress plugin.
* Added: Support for A/B/n testing using shortcodes.
* Added: Admin settings page for API configuration.
* Added: Internationalization support.
* Added: Uninstall script for clean removal of plugin data.

== Upgrade Notice ==

= 1.1.0 =
This version switches to the PHP SDK for improved performance and server-side processing. Please review the updated documentation for any changes in usage.

== Additional Information ==

For more detailed information on usage and configuration, please visit [https://www.captchify.com](https://www.captchify.com).