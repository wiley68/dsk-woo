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
    "dskapi_cart_pogasitelni_vnoski_input"
  );
  const priceTxt = document.getElementById("dskapi_cart_price_txt");
  const cidInput = document.getElementById("dskapi_cart_cid");
  const liveUrlInput = document.getElementById("DSKAPI_CART_LIVEURL");
  const productIdInput = document.getElementById("dskapi_cart_product_id");

  // Validate all required elements exist
  if (
    !vnoskiInput ||
    !priceTxt ||
    !cidInput ||
    !liveUrlInput ||
    !productIdInput
  ) {
    return;
  }

  const dskapi_vnoski = parseFloat(vnoskiInput.value);
  const dskapi_price = parseFloat(priceTxt.value);
  const dskapi_cid = cidInput.value;
  const DSKAPI_LIVEURL = liveUrlInput.value;
  const dskapi_product_id = productIdInput.value;

  // Build API request URL
  var xmlhttpro = createCORSRequestCart(
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

  /**
   * Handle API response.
   * Updates UI elements or shows validation error.
   */
  xmlhttpro.onreadystatechange = function () {
    if (this.readyState == 4) {
      try {
        const response = JSON.parse(this.response);
        var options = response.dsk_options;
        var dsk_vnoska = parseFloat(response.dsk_vnoska);
        var dsk_gpr = parseFloat(response.dsk_gpr);
        var dsk_is_visible = response.dsk_is_visible;

        if (dsk_is_visible) {
          if (options) {
            // Update popup input fields
            const dskapi_vnoska_input =
              document.getElementById("dskapi_cart_vnoska");
            const dskapi_gpr_input = document.getElementById("dskapi_cart_gpr");
            const dskapi_obshtozaplashtane_input = document.getElementById(
              "dskapi_cart_obshtozaplashtane"
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
              "dskapi_cart_vnoski_txt"
            );
            if (dskapi_vnoski_txt) {
              dskapi_vnoski_txt.textContent = dskapi_vnoski;
            }

            const dskapi_vnoska_txt = document.getElementById(
              "dskapi_cart_vnoska_txt"
            );
            if (dskapi_vnoska_txt) {
              dskapi_vnoska_txt.textContent = dsk_vnoska.toFixed(2);
            }

            // Store new value as current for future rollbacks
            old_vnoski_cart = dskapi_vnoski;
          } else {
            // Installments below minimum - show error and revert
            alert("Избраният брой погасителни вноски е под минималния.");
            vnoskiInput.value = old_vnoski_cart;
          }
        } else {
          // Installments above maximum - show error and revert
          alert("Избраният брой погасителни вноски е над максималния.");
          vnoskiInput.value = old_vnoski_cart;
        }
      } catch (e) {
        console.error("DSKAPI Cart: Error parsing response", e);
      }
    }
  };
  xmlhttpro.send();
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
    document.getElementById("dskapi_cart_checkout_url")?.value || "/checkout/";
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
function dskapiCartButtonClick() {
  const dskapi_button_status = parseInt(
    document.getElementById("dskapi_cart_button_status")?.value || "0"
  );

  // Direct checkout mode - skip popup entirely
  if (dskapi_button_status == 1) {
    dskapiCartGoToCheckout();
    return;
  }

  // Get current cart price
  const dskapi_price = parseFloat(
    document.getElementById("dskapi_cart_price")?.value || "0"
  );

  // Get maximum allowed credit price
  const dskapi_maxstojnost = document.getElementById("dskapi_cart_maxstojnost");
  const maxPrice = parseFloat(dskapi_maxstojnost?.value || "999999");

  // Validate price is within allowed range
  if (dskapi_price <= maxPrice) {
    // Show popup
    const dskapiCartPopupContainer = document.getElementById(
      "dskapi-cart-popup-container"
    );
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
        " е надвишена!"
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
    "dskapi-cart-popup-container"
  );
  if (dskapiCartPopupContainer) {
    dskapiCartPopupContainer.style.display = "none";
  }
}

/**
 * Global click event listener using event delegation.
 *
 * Handles clicks for elements that may be dynamically loaded/replaced
 * after WooCommerce AJAX cart updates. Event delegation ensures handlers
 * work even after DOM elements are replaced.
 *
 * Handled elements:
 * - #btn_dskapi_cart: Main DSK credit button
 * - #dskapi_cart_buy_credit: Buy on credit button in popup
 * - #dskapi_cart_back_credit: Back/Cancel button in popup
 *
 * @listens document#click
 */
document.addEventListener("click", function (e) {
  // Main DSK cart button
  if (e.target.closest("#btn_dskapi_cart")) {
    e.preventDefault();
    dskapiCartButtonClick();
    return;
  }

  // Buy credit button in popup
  if (e.target.closest("#dskapi_cart_buy_credit")) {
    e.preventDefault();
    dskapiCartGoToCheckout();
    return;
  }

  // Back/Cancel button in popup
  if (e.target.closest("#dskapi_cart_back_credit")) {
    e.preventDefault();
    dskapiCartClosePopup();
    return;
  }
});

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
  // Check if localized vars are available
  if (typeof dskapi_cart_vars === "undefined") {
    console.log("DSKAPI Cart: vars not available");
    return;
  }

  // Make AJAX request to refresh button
  fetch(dskapi_cart_vars.ajax_url, {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body:
      "action=dskapi_refresh_cart_button&nonce=" +
      encodeURIComponent(dskapi_cart_vars.nonce),
    credentials: "same-origin",
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success && data.data && data.data.html) {
        const container = document.getElementById(
          "dskapi-cart-button-container"
        );
        if (container) {
          // Create temp element to parse new HTML
          const temp = document.createElement("div");
          temp.innerHTML = data.data.html;
          const newContainer = temp.querySelector(
            "#dskapi-cart-button-container"
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
