# Simple A/B WordPress Plugin

This WordPress plugin integrates the SimpleAB PHP SDK for A/B/n testing into your WordPress site. It supports both dimension-based and segment-based experiments.

## Version

1.0.1

## Installation

1. Download the plugin files and place them in your `/wp-content/plugins/simpleab` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.

## Configuration

1. After activation, go to the 'Settings' menu and click on 'Captchify Simple A/B'.
2. Enter your Captchify Simple A/B API Key.
3. Save the settings.

## Usage

### Shortcodes

The plugin provides two shortcodes for creating A/B/n tests in your posts or pages:

#### 1. `[simpleab_test]` (Segment-based)

This shortcode is used for segment-based experiments. It accepts the following attributes:

- `experiment_id`: (required) The ID of your experiment.
- `stage`: (required) The stage of the experiment (default: 'Prod').
- `allocation_key`: (optional) The allocation key for the experiment (default: user ID if logged in, or IP address).

Example:

```
[simpleab_test experiment_id="exp_123" stage="Prod"]
Variant C content
||
Variant T1 content
||
Variant T2 content
[/simpleab_test]
```

example with metrics:
```
[simpleab_test experiment_id="<Placeholder>" stage="Prod"]
<button data-simpleab-metric="button_clicks" data-simpleab-aggregation="sum" data-simpleab-value="1" data-simpleab-events="click,focus">Click Me - C!</button>
||
<button data-simpleab-metric="button_clicks" data-simpleab-aggregation="sum" data-simpleab-value="1" data-simpleab-events="click,focus">Click Me - T1!</button>
[/simpleab_test]
```


#### 2. `[simpleab_test_custom]` (Dimension-based)

This shortcode is used for dimension-based experiments. It accepts the following attributes:

- `experiment_id`: (required) The ID of your experiment.
- `stage`: (required) The stage of the experiment (default: 'Prod').
- `dimension`: (required) The dimension for the experiment.
- `allocation_key`: (optional) The allocation key for the experiment (default: user ID if logged in, or IP address).

Example:

```
[simpleab_test_custom experiment_id="exp_123" stage="Prod" dimension="desktop"]
Variant A content
||
Variant T1 content
||
Variant T2 content
[/simpleab_test_custom]
```

example with metrics:
```
[simpleab_test_custom experiment_id="<Placeholder>" stage="Prod"]
<button data-simpleab-metric="button_clicks" data-simpleab-aggregation="sum" data-simpleab-value="1" data-simpleab-events="click,focus">Click Me - C!</button>
||
<button data-simpleab-metric="button_clicks" data-simpleab-aggregation="sum" data-simpleab-value="1" data-simpleab-events="click,focus">Click Me - T1!</button>
[/simpleab_test_custom]
```

For both shortcodes, use `||` to separate different variants. The first variant is considered the control (C), and subsequent variants are treated as T1, T2, T3, etc.

### Client-side Metric Tracking

You can track custom metrics using data attributes on HTML elements. Add the following attributes to any element within your variant content:

- `data-simpleab-metric`: The name of the metric to track.
- `data-simpleab-aggregation`: (optional) The aggregation type (default: 'sum').
- `data-simpleab-value`: (optional) The value to track (default: 1).
- `data-simpleab-events`: (optional) Comma-separated list of events to trigger the metric (default: 'click').

Example:

```html
<button data-simpleab-metric="button_clicks" data-simpleab-aggregation="sum" data-simpleab-value="1" data-simpleab-events="click">
  Click me!
</button>
```

### PHP Functions

You can also use PHP functions to get treatments and track metrics:

#### Get Segment

The `getSegment` function is used to retrieve the segment information for the current user. This is particularly useful for segment-based experiments. Here's how to call it:

```php
<?php
global $simpleab_sdk;
$segment = $simpleab_sdk->getSegment([
    'ip' => $_SERVER['REMOTE_ADDR'],
    'userAgent' => $_SERVER['HTTP_USER_AGENT']
]);
?>
```

This function returns an object containing segment information, which can then be used in other SimpleAB SDK functions.

#### Get Treatment (Dimension-based)

```php
<?php
global $simpleab_sdk;
$treatment = $simpleab_sdk->getTreatment('experiment_id', 'stage', 'dimension', 'allocation_key');
?>
```

#### Get Treatment (Segment-based)

```php
<?php
global $simpleab_sdk;
$segment = $simpleab_sdk->getSegment([
    'ip' => $_SERVER['REMOTE_ADDR'],
    'userAgent' => $_SERVER['HTTP_USER_AGENT']
]);
$treatment = $simpleab_sdk->getTreatmentWithSegment('experiment_id', 'stage', $segment, 'allocation_key');
?>
```

#### Track Metric (Dimension-based)

```php
<?php
global $simpleab_sdk;
$simpleab_sdk->trackMetric([
    'experimentID' => 'experiment_id',
    'stage' => 'stage',
    'dimension' => 'dimension',
    'treatment' => 'treatment',
    'metricName' => 'metric_name',
    'metricValue' => $metric_value,
    'aggregationType' => 'aggregation_type'
]);
?>
```

#### Track Metric (Segment-based)

```php
<?php
global $simpleab_sdk;
$simpleab_sdk->trackMetricWithSegment([
    'experimentID' => 'experiment_id',
    'stage' => 'stage',
    'segment' => $segment,
    'treatment' => 'treatment',
    'metricName' => 'metric_name',
    'metricValue' => $metric_value,
    'aggregationType' => 'aggregation_type'
]);
?>
```

## File Structure

The plugin consists of the following files:

- `simpleab.php`: Main plugin file
- `simpleab-admin.php`: Admin functionality
- `SimpleABSDK.php`: SimpleAB PHP SDK
- `css/simpleab-admin.css`: Admin styles
- `languages/simpleab.pot`: Translation template
- `uninstall.php`: Cleanup script for plugin uninstallation

## Translation

The plugin is translation-ready. To translate it into your language:

1. Copy `languages/simpleab.pot` to `languages/simpleab-{locale}.po` (e.g., `simpleab-fr_FR.po` for French).
2. Translate the strings in the new .po file.
3. Generate the .mo file (e.g., `simpleab-fr_FR.mo`).
4. Place both .po and .mo files in the `languages` directory.

## Uninstallation

When the plugin is uninstalled, it will automatically remove all its options from the database. This cleanup is handled by the `uninstall.php` file.

## Support

For support and further documentation, please visit [https://www.captchify.com](https://www.captchify.com).

## License

This plugin is licensed under the GPL v2 or later.
