=== Smilito Rewards ===
Contributors:      SmilitoLtd
Tags:              block
Tested up to:      6.6
Stable tag:        0.1.6
License:           GPL-2.0-or-later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Integrate with the Smilito rewards platform.

== Description ==

Integrate with the Smilito rewards platform and offer your customers rewards for shopping with you.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/smilito-integration` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress.
1. Enter your credentials via the Smilito section on the wordpress admin.
1. Edit your woocommerce cart + checkout pages and add the Smilito Rewards block.

== Frequently Asked Questions ==

= How do I find my credentials? =

You should have received your credentials when you were onboarded with Smilito.
If you have lost them or they are no longer working, please reach out to your Smilito contact.

== Changelog ==

= 0.1.6 =
* Add handler for "woocommerce_checkout_create_order" hook.

= 0.1.5 =
* Add support for legacy woocommerce order meta hooks.

= 0.1.4 =
* Change when we clear out the basket ID from session.

= 0.1.3 =
* Add new action handler for woocommerce_checkout_update_order_meta.

= 0.1.2 =
* Reduce basket-data endpoint usage

= 0.1.1 =
* Improve plugin updater
* PHP 7.1 compatibility

= 0.1.0 =
* Initial release
