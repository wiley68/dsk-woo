/**
 * DSK API Payment Gateway - Cart Page JavaScript
 *
 * Handles credit button functionality on the WooCommerce cart page.
 * Uses event delegation to handle dynamically loaded content after AJAX updates.
 *
 * @package DSK_POS_Loans
 * @since   1.2.0
 */

/**
 * Stores the previous installment count for validation/rollback.
 *
 * @type {number|undefined}
 */
let old_vnoski_cart;

/**
 * Creates a CORS-compatible XMLHttpRequest object.
 *
 * Handles cross-browser compatibility for CORS requests,
 * including fallback to XDomainRequest for older IE versions.
 *
 * @param {string} method - HTTP method (GET, POST).
 * @param {string} url - Request URL.
 * @returns {XMLHttpRequest|XDomainRequest|null} Request object or null if CORS not supported.
 */
function createCORSRequestCart(method, url) {
	var xhr = new XMLHttpRequest();
	if ("withCredentials" in xhr) {
		xhr.open(method, url, true);
	} else if (typeof XDomainRequest != "undefined") {
		xhr = new XDomainRequest();
		xhr.open(method, url);
	} else {
		xhr = null;
	}
	return xhr;
}

/**
 * Stores the previous installment value for validation purposes.
 *
 * Called on focus of the installment select to store current value
 * before user changes it. Used to rollback if API validation fails.
 *
 * @param {number} _old_vnoski - Previous installment count value.
 * @returns {void}
 */
function dskapi_cart_pogasitelni_vnoski_input_focus(_old_vnoski) {
	old_vnoski_cart = _old_vnoski;
}

/**
 * Handles installment count change event.
 *
 * Fetches new payment data from DSK Bank API when user changes
 * the installment count. Updates all display fields with new values:
 * - Monthly installment amount
 * - Total payment amount
 * - Annual Percentage Rate (GPR)
 * - Button text displays
 *
 * Shows error alerts and reverts to previous value if:
 * - Selected installments below minimum
 * - Selected installments above maximum
 *
 * @returns {void}
 */
function dskapi_cart_pogasitelni_vnoski_input_change() {
	const vnoskiInput = document.getElementById(
		"dskapi_cart_pogasitelni_vnoski_input",
	);
	const priceTxt = document.getElementById("dskapi_cart_price_txt");
	const cidInput = document.getElementById("dskapi_cart_cid");
	const productIdInput = document.getElementById("dskapi_cart_product_id");

	// Validate all required elements exist
	if (!vnoskiInput || !priceTxt || !cidInput || !productIdInput) {
		return;
	}

	const dskapi_vnoski = parseFloat(vnoskiInput.value);
	const dskapi_price = parseFloat(priceTxt.value);
	const dskapi_cid = cidInput.value;
	const dskapi_product_id = productIdInput.value;

	dskapiFetchProductCustom(
		{
			cid: dskapi_cid,
			price: dskapi_price,
			product_id: dskapi_product_id,
			dskapi_vnoski: dskapi_vnoski,
		},
		function (xhr) {
			if (xhr.status !== 200) {
				console.error(
					"DSKAPI Cart: calculation request failed",
					xhr.status,
				);
				return;
			}

			try {
				const response = JSON.parse(xhr.responseText);
				var options = response.dsk_options;
				var dsk_vnoska = parseFloat(response.dsk_vnoska);
				var dsk_gpr = parseFloat(response.dsk_gpr);
				var dsk_is_visible = response.dsk_is_visible;

				if (dsk_is_visible) {
					if (options) {
						// Update popup input fields
						const dskapi_vnoska_input =
							document.getElementById("dskapi_cart_vnoska");
						const dskapi_gpr_input =
							document.getElementById("dskapi_cart_gpr");
						const dskapi_obshtozaplashtane_input =
							document.getElementById(
								"dskapi_cart_obshtozaplashtane",
							);

						if (dskapi_vnoska_input) {
							dskapi_vnoska_input.value = dsk_vnoska.toFixed(2);
						}
						if (dskapi_obshtozaplashtane_input) {
							dskapi_obshtozaplashtane_input.value = (
								dsk_vnoska * dskapi_vnoski
							).toFixed(2);
						}
						if (dskapi_gpr_input) {
							dskapi_gpr_input.value = dsk_gpr.toFixed(2);
						}

						// Update button text displays
						const dskapi_vnoski_txt = document.getElementById(
							"dskapi_cart_vnoski_txt",
						);
						if (dskapi_vnoski_txt) {
							dskapi_vnoski_txt.textContent = dskapi_vnoski;
						}

						const dskapi_vnoska_txt = document.getElementById(
							"dskapi_cart_vnoska_txt",
						);
						if (dskapi_vnoska_txt) {
							dskapi_vnoska_txt.textContent =
								dsk_vnoska.toFixed(2);
						}

						// Store new value as current for future rollbacks
						old_vnoski_cart = dskapi_vnoski;
					} else {
						// Installments below minimum - show error and revert
						alert(
							"Избраният брой погасителни вноски е под минималния.",
						);
						vnoskiInput.value = old_vnoski_cart;
					}
				} else {
					// Installments above maximum - show error and revert
					alert(
						"Избраният брой погасителни вноски е над максималния.",
					);
					vnoskiInput.value = old_vnoski_cart;
				}
			} catch (e) {
				console.error("DSKAPI Cart: Error parsing response", e);
			}
		},
	);
}

/**
 * Redirects to checkout with DSK payment method preselected.
 *
 * Unlike the product page version, this does not add anything to cart
 * since the user is already on the cart page with items in cart.
 * Simply closes popup and redirects with payment_method parameter.
 *
 * @returns {void}
 */
function dskapiCartGoToCheckout() {
	// Close popup if open
	const popup = document.getElementById("dskapi-cart-popup-container");
	if (popup) popup.style.display = "none";

	// Get checkout URL and payment method from hidden inputs
	const checkoutUrl =
		document.getElementById("dskapi_cart_checkout_url")?.value ||
		"/checkout/";
	const paymentMethod =
		document.getElementById("dskapi_cart_payment_method")?.value ||
		"dskapipayment";

	// Build redirect URL with payment method parameter
	const separator = checkoutUrl.includes("?") ? "&" : "?";
	window.location.href =
		checkoutUrl +
		separator +
		"payment_method=" +
		encodeURIComponent(paymentMethod);
}

/**
 * Handles the main DSK cart button click event.
 *
 * Behavior depends on button status setting:
 * - Status 1: Direct checkout mode - skips popup and redirects immediately
 * - Status 0: Shows popup with credit calculation options
 *
 * Also validates cart total against maximum allowed credit amount.
 *
 * @returns {void}
 */
/**
 * Moves the cart popup to document.body so block themes cannot clip it.
 *
 * @returns {HTMLElement|null}
 */
function dskapiCartEnsurePopupInBody() {
	const popup = document.getElementById("dskapi-cart-popup-container");
	if (!popup) {
		return null;
	}

	if (popup.parentNode !== document.body) {
		document.body.appendChild(popup);
	}

	return popup;
}

/**
 * Whether the click target is inside the DSK cart credit button area.
 *
 * @param {EventTarget|null} target Click target.
 * @returns {boolean}
 */
function dskapiCartIsMainButtonClick(target) {
	if (!target || typeof target.closest !== "function") {
		return false;
	}

	return !!target.closest(
		"#dskapi-cart-button-container .dskapi_btn_click, #dskapi-cart-button-container .dskapi_table_img, #dskapi-cart-button-container .dskapi_button_div_txt",
	);
}

function dskapiCartButtonClick() {
	const dskapi_button_status = parseInt(
		document.getElementById("dskapi_cart_button_status")?.value || "0",
	);

	// Direct checkout mode - skip popup entirely
	if (dskapi_button_status == 1) {
		dskapiCartGoToCheckout();
		return;
	}

	// Get current cart price
	const dskapi_price = parseFloat(
		document.getElementById("dskapi_cart_price")?.value || "0",
	);

	// Get maximum allowed credit price
	const dskapi_maxstojnost = document.getElementById(
		"dskapi_cart_maxstojnost",
	);
	const maxPrice = parseFloat(dskapi_maxstojnost?.value || "999999");

	// Validate price is within allowed range
	if (dskapi_price <= maxPrice) {
		// Show popup
		const dskapiCartPopupContainer = dskapiCartEnsurePopupInBody();
		if (dskapiCartPopupContainer) {
			dskapiCartPopupContainer.style.display = "block";
		}
		// Trigger initial calculation
		dskapi_cart_pogasitelni_vnoski_input_change();
	} else {
		// Price exceeds maximum - show error
		alert(
			"Максимално позволената цена за кредит " +
				maxPrice.toFixed(2) +
				" е надвишена!",
		);
	}
}

/**
 * Closes the cart credit popup.
 *
 * @returns {void}
 */
function dskapiCartClosePopup() {
	const dskapiCartPopupContainer = document.getElementById(
		"dskapi-cart-popup-container",
	);
	if (dskapiCartPopupContainer) {
		dskapiCartPopupContainer.style.display = "none";
	}
}

/**
 * Global click listener (capture) for cart button and popup actions.
 *
 * Capture phase runs before WooCommerce Cart block handlers that may
 * stop propagation on bubble phase. Works with dynamically replaced markup.
 *
 * @listens document#click
 */
document.addEventListener(
	"click",
	function (e) {
		if (e.target.closest("#dskapi_cart_buy_credit")) {
			e.preventDefault();
			dskapiCartGoToCheckout();
			return;
		}

		if (e.target.closest("#dskapi_cart_back_credit")) {
			e.preventDefault();
			dskapiCartClosePopup();
			return;
		}

		if (dskapiCartIsMainButtonClick(e.target)) {
			e.preventDefault();
			dskapiCartButtonClick();
		}
	},
	true,
);

/**
 * Installment select handlers (inline attributes are stripped in Cart block HTML).
 */
document.addEventListener("change", function (e) {
	if (e.target && e.target.id === "dskapi_cart_pogasitelni_vnoski_input") {
		dskapi_cart_pogasitelni_vnoski_input_change();
	}
});

document.addEventListener(
	"focus",
	function (e) {
		if (
			e.target &&
			e.target.id === "dskapi_cart_pogasitelni_vnoski_input"
		) {
			dskapi_cart_pogasitelni_vnoski_input_focus(e.target.value);
		}
	},
	true,
);

/**
 * Refreshes the DSK cart button via AJAX.
 *
 * Called after WooCommerce updates the cart (quantity change, coupon, etc.)
 * to fetch fresh button HTML with recalculated totals and credit values.
 *
 * Uses WordPress AJAX endpoint with nonce verification for security.
 * Replaces the button container content with new HTML from server.
 *
 * @returns {void}
 */
function dskapiRefreshCartButton() {
	if (typeof dskapiGetNonce !== "function") {
		console.log("DSKAPI Cart: dskapiGetNonce not available");
		return;
	}

	dskapiGetNonce()
		.then((nonce) => {
			return fetch(dskapi_ajax_vars.ajax_url, {
				method: "POST",
				headers: {
					"Content-Type": "application/x-www-form-urlencoded",
				},
				body:
					"action=dskapi_refresh_cart_button&nonce=" +
					encodeURIComponent(nonce),
				credentials: "same-origin",
			});
		})
		.then((response) => response.json())
		.then((data) => {
			if (data.success && data.data && data.data.html) {
				const container = document.getElementById(
					"dskapi-cart-button-container",
				);
				if (container) {
					// Create temp element to parse new HTML
					const temp = document.createElement("div");
					temp.innerHTML = data.data.html;
					const newContainer = temp.querySelector(
						"#dskapi-cart-button-container",
					);
					if (newContainer) {
						container.innerHTML = newContainer.innerHTML;
					} else {
						container.innerHTML = data.data.html;
					}
				}
			}
		})
		.catch((error) => {
			console.error("DSKAPI Cart: Refresh error", error);
		});
}

/**
 * WooCommerce cart update event listeners.
 *
 * Listens for WooCommerce jQuery events that fire after cart is updated:
 * - updated_cart_totals: Fired after cart totals are recalculated
 * - updated_wc_div: Fired after cart fragments are updated
 *
 * Triggers button refresh to sync with new cart totals.
 */
if (typeof jQuery !== "undefined") {
	jQuery(document.body).on("updated_cart_totals updated_wc_div", function () {
		// Cart was updated via AJAX - refresh DSK button with new totals
		dskapiRefreshCartButton();
	});
}

/**
 * Refresh DSK button when WooCommerce Cart block updates totals via Store API.
 */
function dskapiInitBlockCartRefresh() {
	if (!document.querySelector(".wp-block-woocommerce-cart")) {
		return;
	}

	if (
		typeof wp === "undefined" ||
		!wp.data ||
		typeof wp.data.subscribe !== "function"
	) {
		return;
	}

	let signature = "";
	let debounceTimer = null;

	wp.data.subscribe(() => {
		try {
			const cartStore = wp.data.select("wc/store/cart");
			if (!cartStore || typeof cartStore.getCartData !== "function") {
				return;
			}

			const cartData = cartStore.getCartData();
			if (!cartData || !cartData.totals) {
				return;
			}

			const nextSignature =
				String(cartData.totals.total_price) +
				":" +
				String(cartData.items_count);

			if (nextSignature === signature) {
				return;
			}

			signature = nextSignature;

			if (!document.getElementById("dskapi-cart-button-container")) {
				return;
			}

			clearTimeout(debounceTimer);
			debounceTimer = setTimeout(() => {
				dskapiRefreshCartButton();
			}, 300);
		} catch (error) {
			// Store not ready yet.
		}
	});
}

if (document.readyState === "loading") {
	document.addEventListener("DOMContentLoaded", dskapiInitBlockCartRefresh);
} else {
	dskapiInitBlockCartRefresh();
}
