/**
 * DSK Credit API - Cart Page JavaScript
 * Handles credit button functionality on cart page.
 * Uses event delegation to handle dynamically loaded content.
 */

let old_vnoski_cart;

/**
 * Create CORS-compatible XMLHttpRequest.
 *
 * @param {string} method - HTTP method (GET, POST).
 * @param {string} url - Request URL.
 * @returns {XMLHttpRequest|null} XMLHttpRequest object or null.
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
 * Store previous installment value for validation.
 *
 * @param {number} _old_vnoski - Previous installment count.
 */
function dskapi_cart_pogasitelni_vnoski_input_focus(_old_vnoski) {
  old_vnoski_cart = _old_vnoski;
}

/**
 * Handle installment count change - fetch new payment data from API.
 * Updates installment amount, total payment, GPR and button text displays.
 */
function dskapi_cart_pogasitelni_vnoski_input_change() {
  const vnoskiInput = document.getElementById(
    "dskapi_cart_pogasitelni_vnoski_input"
  );
  const priceTxt = document.getElementById("dskapi_cart_price_txt");
  const cidInput = document.getElementById("dskapi_cart_cid");
  const liveUrlInput = document.getElementById("DSKAPI_CART_LIVEURL");
  const productIdInput = document.getElementById("dskapi_cart_product_id");

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
            // Update popup fields
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

            old_vnoski_cart = dskapi_vnoski;
          } else {
            alert("Избраният брой погасителни вноски е под минималния.");
            vnoskiInput.value = old_vnoski_cart;
          }
        } else {
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
 * Redirect directly to checkout with DSK payment method preselected.
 * No need to add to cart - we're already on cart page.
 */
function dskapiCartGoToCheckout() {
  const popup = document.getElementById("dskapi-cart-popup-container");
  if (popup) popup.style.display = "none";

  const checkoutUrl =
    document.getElementById("dskapi_cart_checkout_url")?.value || "/checkout/";
  const paymentMethod =
    document.getElementById("dskapi_cart_payment_method")?.value ||
    "dskapipayment";

  // Redirect directly to checkout with payment method
  const separator = checkoutUrl.includes("?") ? "&" : "?";
  window.location.href =
    checkoutUrl +
    separator +
    "payment_method=" +
    encodeURIComponent(paymentMethod);
}

/**
 * Handle main DSK cart button click.
 * Shows popup or redirects to checkout based on button status.
 */
function dskapiCartButtonClick() {
  const dskapi_button_status = parseInt(
    document.getElementById("dskapi_cart_button_status")?.value || "0"
  );

  if (dskapi_button_status == 1) {
    // Direct to checkout mode - skip popup
    dskapiCartGoToCheckout();
    return;
  }

  // Get current price
  const dskapi_price = parseFloat(
    document.getElementById("dskapi_cart_price")?.value || "0"
  );

  // Check max price limit
  const dskapi_maxstojnost = document.getElementById("dskapi_cart_maxstojnost");
  const maxPrice = parseFloat(dskapi_maxstojnost?.value || "999999");

  if (dskapi_price <= maxPrice) {
    const dskapiCartPopupContainer = document.getElementById(
      "dskapi-cart-popup-container"
    );
    if (dskapiCartPopupContainer) {
      dskapiCartPopupContainer.style.display = "block";
    }
    dskapi_cart_pogasitelni_vnoski_input_change();
  } else {
    alert(
      "Максимално позволената цена за кредит " +
        maxPrice.toFixed(2) +
        " е надвишена!"
    );
  }
}

/**
 * Close the cart popup.
 */
function dskapiCartClosePopup() {
  const dskapiCartPopupContainer = document.getElementById(
    "dskapi-cart-popup-container"
  );
  if (dskapiCartPopupContainer) {
    dskapiCartPopupContainer.style.display = "none";
  }
}

// Use event delegation on document - works even after AJAX updates
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
 * Refresh DSK cart button via AJAX.
 * Called after WooCommerce updates the cart to get fresh button with new totals.
 */
function dskapiRefreshCartButton() {
  if (typeof dskapi_cart_vars === "undefined") {
    console.log("DSKAPI Cart: vars not available");
    return;
  }

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

// Listen for WooCommerce cart update events to refresh button
if (typeof jQuery !== "undefined") {
  jQuery(document.body).on("updated_cart_totals updated_wc_div", function () {
    // Cart was updated via AJAX - refresh DSK button with new totals
    dskapiRefreshCartButton();
  });
}
