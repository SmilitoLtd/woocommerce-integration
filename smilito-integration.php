<?php

/**
 * Plugin Name:       Smilito Rewards
 * Description:       Integrate with the Smilito rewards platform.
 * Requires at least: 6.1
 * Requires PHP:      7.1
 * Version:           0.1.6
 * Author:            SmilitoLtd
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       smilito-integration
 * Requires Plugins:  woocommerce
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

$deps = [
	'Plugin.php',
	'ConfigManager.php',
	'BasketManager.php',
	'Api.php',
	'Block.php',
	'Admin.php',
	'Updater.php',
];
foreach ($deps as $dep) {
	require_once plugin_dir_path(__FILE__) . 'SmilitoLtd/' . $dep;
}

/**
 * This is just an entrypoint.
 * The Plugin class contains the rest of the logic.
 */
$plugin = new SmilitoLtd\Plugin(__FILE__);
$plugin->setup();
