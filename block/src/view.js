/**
 * Use this file for JavaScript code that you want to run in the front-end
 * on posts/pages that contain this block.
 *
 * When this file is defined as the value of the `viewScript` property
 * in `block.json` it will be enqueued on the front end of the site.
 *
 * Example:
 *
 * ```js
 * {
 *   "viewScript": "file:./view.js"
 * }
 * ```
 *
 * If you're not making any changes to this file because your project doesn't need any
 * JavaScript running in the front-end, then you should delete this file and remove
 * the `viewScript` property from `block.json`.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/#view-script
 */

window.SmilitoIntegrationRegistered = window.SmilitoIntegrationRegistered || false;

// This stops issues occurring if the script is double enqueued.
if (!window.SmilitoIntegrationRegistered) {
	window.SmilitoIntegrationRegistered = true;

	// Contains an order ID if we are looking at a order summary/payment confirmation page.
	// This is set via a dynamic script added to the thank-you block.
	window.SmilitoIntegrationOrderId = window.SmilitoIntegrationOrderId || null;

	const isOnOrderReceivedPage =
		window.location.href.indexOf("/checkout/order-received/") > -1;
	const smilitoTargetElementSelector = "#smilito_integration";
	const smilitoClaimEnabled = isOnOrderReceivedPage ? true : false;

	const smilitoRunArgs = {
		targetSelector: smilitoTargetElementSelector,
		claim: smilitoClaimEnabled,
	};

	const hasTargetElement = (selector) => {
		return !!document.querySelector(selector)
	}

	const waitForTargetElement = (selector, callback) => {
		if (hasTargetElement(selector)) {
			callback();
			return;
		}
		const observer = new MutationObserver((mutations) => {
			for (const mutation of mutations) {
				if (hasTargetElement(selector)) {
					callback();
					observer.disconnect();
					break;
				}
			}
		});

		observer.observe(document, {childList: true, subtree: true});
	};

	const listenForChangesToSelector = (selector, callback) => {
		const ob = () => {
			const observer = new MutationObserver(callback);
			const el = document.querySelector(selector);
			observer.observe(el, {
				childList: true,
				subtree: true,
				attributes: true,
				characterData: true,
			})
		}
		if (!hasTargetElement(selector)) {
			waitForTargetElement(selector, function () {
				ob()
			})
			return;
		}
		ob();
	};

	const fetchBasketData = async () => {
		const orderId = SmilitoIntegrationOrderId || null;
		try {
			const endpoint = orderId
				? `/wp-json/smilito-integration/v1/basket-data?order-id=${orderId}`
				: `/wp-json/smilito-integration/v1/basket-data`;

			const response = await fetch(endpoint);
			if (!response.ok) {
				throw new Error("Response error");
			}
			return response.json();
		} catch (error) {
			console.error("Failed to fetch basket data", error);
			return null;
		}
	};

	const jwt = async () => {
		try {
			const response = await fetch("/wp-json/smilito-integration/v1/login", {
				method: "POST",
				headers: {
					"Content-Type": "application/json",
				},
			});

			if (!response.ok) {
				throw new Error("Login response error");
			}

			const data = await response.json();
			return data.jwt; // Assuming the JWT is in the 'token' field.
		} catch (error) {
			console.error("Failed to fetch JWT", error);
			return null;
		}
	};

	const getBasketValue = async () => {
		const data = await fetchBasketData();
		return data.basket_value;
	};

	const getBasketId = async () => {
		const data = await fetchBasketData();
		return data.basket_id;
	};

	const runSmilito = () => {
		waitForTargetElement(smilitoTargetElementSelector, () => {
			if (window.Smilito && window.Smilito.run) {
				window.Smilito.run(smilitoRunArgs);
			}
		})
	}

	const loadSmilitoScript = () => {
		const script = document.createElement("script");
		script.id = "smilito-integration-script";
		script.src =
			"https://typescript-integration.smilito.io/smilito-typescript-integration.js";
		script.type = "module";
		script.defer = true;
		script.onload = smilitoScriptOnLoad;
		document.body.appendChild(script);
	};

	const smilitoScriptOnPage = () => {
		return !!document.querySelector('script#smilito-integration-script')
	}

	const smilitoScriptOnLoad = async () => {
		const args = {
			basketValue: getBasketValue,
			basketId: getBasketId,
			jwt: jwt,
			callback: () => {
				runSmilito();
			},
		};
		try {
			window.Smilito.init(args);
		} catch (err) {
			console.error("Smilito could not be initialised...", err);
		}
	};

	const startSmilito = () => {
		if (smilitoScriptOnPage()) {
			runSmilito();
			return;
		}
		loadSmilitoScript();
	}

	jQuery(function ($) {
		const events = [
			'updated_cart_totals',
			// 'update_checkout',
			'updated_checkout',
			'added_to_cart',
			'removed_from_cart',
			'updated_wc_div',
			// 'country_to_state_changed',
			// 'updated_shipping_method',
			// 'applied_coupon',
			'removed_coupon',
			'wc_cart_emptied',
			'wc_fragments_refreshed',
		]
		events.forEach((val) => {
			$(document).on(val, function () {
				startSmilito();
			})
		})
		const selectors = [
			'.wc-block-components-totals-footer-item-tax-value',
		]
		selectors.forEach((val) => {
			// Above events don't seem to pick up on inc/dec on cart page...
			// Use DOM events instead.
			listenForChangesToSelector(val, function () {
				startSmilito();
			})
		})
	})

	document.addEventListener("DOMContentLoaded", async () => {
		startSmilito();
	});

}
