/**
 * DSK API Checkout Interest Rates Popup
 * Handles the interest rates popup on checkout page
 */

let old_vnoski_checkout;

/**
 * Create CORS request for API calls
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
 * Store old value on focus
 */
function dskapi_checkout_vnoski_input_focus(_old_vnoski) {
  old_vnoski_checkout = _old_vnoski;
}

/**
 * Handle installment change
 */
function dskapi_checkout_vnoski_input_change() {
  const priceEl = document.getElementById("dskapi_checkout_price_txt");
  const vnoskaEl = document.getElementById("dskapi_checkout_vnoska");
  const obshtoEl = document.getElementById("dskapi_checkout_obshtozaplashtane");
  const gprEl = document.getElementById("dskapi_checkout_gpr");
  const selectEl = document.getElementById("dskapi_checkout_vnoski_input");
  const cidEl = document.getElementById("dskapi_checkout_cid");
  const productIdEl = document.getElementById("dskapi_checkout_product_id");
  const liveurlEl = document.getElementById("DSKAPI_CHECKOUT_LIVEURL");

  if (!priceEl || !selectEl || !cidEl || !liveurlEl || !productIdEl) return;

  const dskapi_price = parseFloat(priceEl.value);
  const dskapi_vnoski = parseFloat(selectEl.value);
  const dskapi_cid = cidEl.value;
  const dskapi_product_id = productIdEl.value;
  const DSKAPI_LIVEURL = liveurlEl.value;

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
            if (vnoskaEl) {
              vnoskaEl.value = dsk_vnoska.toFixed(2);
            }
            if (obshtoEl) {
              obshtoEl.value = (dsk_vnoska * dskapi_vnoski).toFixed(2);
            }
            if (gprEl) {
              gprEl.value = dsk_gpr.toFixed(2);
            }

            old_vnoski_checkout = dskapi_vnoski;
          } else {
            alert("Избраният брой погасителни вноски е под минималния.");
            selectEl.value = old_vnoski_checkout;
          }
        } else {
          alert("Избраният брой погасителни вноски е над максималния.");
          selectEl.value = old_vnoski_checkout;
        }
      } catch (e) {
        console.error("DSKAPI Checkout: Error parsing response", e);
        if (selectEl) selectEl.value = old_vnoski_checkout;
      }
    }
  };

  xhr.onerror = function () {
    console.error("Request failed");
    if (selectEl) selectEl.value = old_vnoski_checkout;
  };

  xhr.send();
}

/**
 * Open popup
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
 * Close popup
 */
function dskapiCheckoutClosePopup() {
  const popup = document.getElementById("dskapi-checkout-popup-container");
  if (popup) {
    popup.style.display = "none";
  }
}

/**
 * Event delegation for clicks
 */
document.addEventListener("click", function (e) {
  // Open popup on link click
  if (e.target && e.target.id === "dskapi_checkout_interest_rates_link") {
    e.preventDefault();
    dskapiCheckoutOpenPopup();
  }

  // Close popup on close button click
  if (e.target && e.target.id === "dskapi_checkout_close_popup") {
    dskapiCheckoutClosePopup();
  }

  // Close popup on overlay click
  if (e.target && e.target.id === "dskapi-checkout-popup-container") {
    dskapiCheckoutClosePopup();
  }
});

/**
 * Close popup on Escape key
 */
document.addEventListener("keydown", function (e) {
  if (e.key === "Escape") {
    dskapiCheckoutClosePopup();
  }
});
