/**
 * DSK API Payment Gateway - Checkout Page JavaScript
 *
 * Handles the interest rates popup functionality on the WooCommerce checkout page.
 * Allows customers to view and calculate different installment options
 * before completing their purchase with DSK Bank credit.
 *
 * @package DSK_POS_Loans
 * @since   1.2.0
 */

/**
 * Stores the previous installment count for validation/rollback.
 *
 * @type {number|undefined}
 */
let old_vnoski_checkout;

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
function createCORSRequestCheckout(method, url) {
  let xhr = new XMLHttpRequest();
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
function dskapi_checkout_vnoski_input_focus(_old_vnoski) {
  old_vnoski_checkout = _old_vnoski;
}

/**
 * Handles installment count change event.
 *
 * Fetches new payment data from DSK Bank API when user changes
 * the installment count in the checkout popup. Updates all display fields:
 * - Monthly installment amount
 * - Total payment amount
 * - Annual Percentage Rate (GPR)
 *
 * Shows error alerts and reverts to previous value if:
 * - Selected installments below minimum allowed
 * - Selected installments above maximum allowed
 *
 * @returns {void}
 */
function dskapi_checkout_vnoski_input_change() {
  // Get all required DOM elements
  const priceEl = document.getElementById("dskapi_checkout_price_txt");
  const vnoskaEl = document.getElementById("dskapi_checkout_vnoska");
  const obshtoEl = document.getElementById("dskapi_checkout_obshtozaplashtane");
  const gprEl = document.getElementById("dskapi_checkout_gpr");
  const selectEl = document.getElementById("dskapi_checkout_vnoski_input");
  const cidEl = document.getElementById("dskapi_checkout_cid");
  const productIdEl = document.getElementById("dskapi_checkout_product_id");
  const liveurlEl = document.getElementById("DSKAPI_CHECKOUT_LIVEURL");

  // Validate all required elements exist
  if (!priceEl || !selectEl || !cidEl || !liveurlEl || !productIdEl) return;

  // Extract values for API request
  const dskapi_price = parseFloat(priceEl.value);
  const dskapi_vnoski = parseFloat(selectEl.value);
  const dskapi_cid = cidEl.value;
  const dskapi_product_id = productIdEl.value;
  const DSKAPI_LIVEURL = liveurlEl.value;

  // Build and send API request
  const xhr = createCORSRequestCheckout(
    "GET",
    DSKAPI_LIVEURL +
      "/function/getproductcustom.php?cid=" +
      dskapi_cid +
      "&price=" +
      dskapi_price +
      "&product_id=" +
      dskapi_product_id +
      "&dskapi_vnoski=" +
      dskapi_vnoski
  );

  if (!xhr) {
    console.error("CORS not supported");
    return;
  }

  /**
   * Handle API response.
   * Updates UI elements with new values or shows validation error.
   */
  xhr.onreadystatechange = function () {
    if (this.readyState == 4) {
      try {
        const response = JSON.parse(this.responseText);
        const options = response.dsk_options;
        const dsk_vnoska = parseFloat(response.dsk_vnoska);
        const dsk_gpr = parseFloat(response.dsk_gpr);
        const dsk_is_visible = response.dsk_is_visible;

        if (dsk_is_visible) {
          if (options) {
            // Update popup display fields
            if (vnoskaEl) {
              vnoskaEl.value = dsk_vnoska.toFixed(2);
            }
            if (obshtoEl) {
              obshtoEl.value = (dsk_vnoska * dskapi_vnoski).toFixed(2);
            }
            if (gprEl) {
              gprEl.value = dsk_gpr.toFixed(2);
            }

            // Store new value as current for future rollbacks
            old_vnoski_checkout = dskapi_vnoski;
          } else {
            // Installments below minimum - show error and revert
            alert("Избраният брой погасителни вноски е под минималния.");
            selectEl.value = old_vnoski_checkout;
          }
        } else {
          // Installments above maximum - show error and revert
          alert("Избраният брой погасителни вноски е над максималния.");
          selectEl.value = old_vnoski_checkout;
        }
      } catch (e) {
        console.error("DSKAPI Checkout: Error parsing response", e);
        if (selectEl) selectEl.value = old_vnoski_checkout;
      }
    }
  };

  /**
   * Handle request error.
   * Reverts to previous installment value.
   */
  xhr.onerror = function () {
    console.error("Request failed");
    if (selectEl) selectEl.value = old_vnoski_checkout;
  };

  xhr.send();
}

/**
 * Opens the interest rates popup.
 *
 * Moves the popup element to document body before displaying
 * to avoid CSS restrictions from WooCommerce payment method container.
 * This ensures the popup displays at full size with proper positioning.
 *
 * @returns {void}
 */
function dskapiCheckoutOpenPopup() {
  const popup = document.getElementById("dskapi-checkout-popup-container");
  if (popup) {
    // Move popup to body to avoid WooCommerce container restrictions
    if (popup.parentElement !== document.body) {
      document.body.appendChild(popup);
    }
    popup.style.display = "block";
  }
}

/**
 * Closes the interest rates popup.
 *
 * @returns {void}
 */
function dskapiCheckoutClosePopup() {
  const popup = document.getElementById("dskapi-checkout-popup-container");
  if (popup) {
    popup.style.display = "none";
  }
}

/**
 * Global click event listener for popup interactions.
 *
 * Handles clicks for:
 * - Interest rates link: Opens the popup
 * - Close button: Closes the popup
 * - Overlay background: Closes the popup (click outside)
 *
 * @listens document#click
 */
document.addEventListener("click", function (e) {
  // Open popup on interest rates link click
  if (e.target && e.target.id === "dskapi_checkout_interest_rates_link") {
    e.preventDefault();
    dskapiCheckoutOpenPopup();
  }

  // Close popup on close button click
  if (e.target && e.target.id === "dskapi_checkout_close_popup") {
    dskapiCheckoutClosePopup();
  }

  // Close popup on overlay click (outside popup content)
  if (e.target && e.target.id === "dskapi-checkout-popup-container") {
    dskapiCheckoutClosePopup();
  }
});

/**
 * Keyboard event listener for popup.
 *
 * Closes the popup when user presses the Escape key,
 * providing standard modal accessibility behavior.
 *
 * @listens document#keydown
 */
document.addEventListener("keydown", function (e) {
  if (e.key === "Escape") {
    dskapiCheckoutClosePopup();
  }
});
