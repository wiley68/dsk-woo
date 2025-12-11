let old_vnoski;

/**
 * Convert price string to dot decimal format.
 * Handles various number formats (1.234,56 or 1,234.56).
 *
 * @param {string} price - Price string to convert.
 * @returns {string} Price with dot as decimal separator.
 */
function dskapiConvertToDotDecimal(price) {
  price = price.trim();
  if (price.includes(".") && price.includes(",")) {
    if (price.lastIndexOf(",") < price.lastIndexOf(".")) {
      price = price.replace(/,/g, "");
    } else {
      price = price.replace(/\./g, "").replace(/,/g, ".");
    }
  } else if (price.includes(",")) {
    if (price.split(",").length - 1 === 1) {
      price = price.replace(/,/g, ".");
    } else {
      price = price.replace(/,/g, "");
    }
  }
  return price;
}

/**
 * Create CORS-compatible XMLHttpRequest.
 *
 * @param {string} method - HTTP method (GET, POST).
 * @param {string} url - Request URL.
 * @returns {XMLHttpRequest|null} XMLHttpRequest object or null.
 */
function createCORSRequest(method, url) {
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
 * Get variation price from WooCommerce variation price element.
 * Extracts price from regular or sale price spans.
 *
 * @param {string} fallbackPrice - Default price if variation not found.
 * @returns {string} Extracted price string.
 */
function dskapiGetVariationPrice(fallbackPrice) {
  let price = fallbackPrice;

  const variationDiv = document.getElementsByClassName(
    "woocommerce-variation-price"
  );
  if (typeof variationDiv[0] === "undefined") {
    return price;
  }

  const variationSpan1 = variationDiv[0].getElementsByTagName("span");
  if (typeof variationSpan1[0] === "undefined") {
    return price;
  }

  // Check for regular price
  const variationSpan2 = variationSpan1[0].getElementsByTagName("span");
  if (typeof variationSpan2[0] !== "undefined") {
    const tps = variationSpan2[0].innerHTML.split("&");
    price = tps[0];
  }

  // Check for sale price (inside <ins> tag)
  const variationIns = variationSpan1[0].getElementsByTagName("ins");
  if (typeof variationIns[0] !== "undefined") {
    const variationSpan3 = variationIns[0].getElementsByTagName("span");
    if (typeof variationSpan3[0] !== "undefined") {
      const tps = variationSpan3[0].innerHTML.split("&");
      price = tps[0];
    }
  }

  return price;
}

/**
 * Get current quantity from quantity input field.
 *
 * @returns {number} Quantity value or 1 if not found.
 */
function dskapiGetQuantity() {
  const quantityInputs = document.getElementsByName("quantity");
  if (quantityInputs !== null && quantityInputs.length > 0) {
    return parseFloat(quantityInputs[0].value) || 1;
  }
  return 1;
}

/**
 * Convert price based on EUR settings.
 *
 * @param {number} price - Price to convert.
 * @param {number} eurSetting - EUR conversion setting (0, 1, or 2).
 * @param {string} currencyCode - Current currency code (EUR or BGN).
 * @returns {number} Converted price.
 */
function dskapiConvertCurrency(price, eurSetting, currencyCode) {
  const EUR_BGN_RATE = 1.95583;

  switch (eurSetting) {
    case 1:
      if (currencyCode === "EUR") {
        return price * EUR_BGN_RATE;
      }
      break;
    case 2:
      if (currencyCode === "BGN") {
        return price / EUR_BGN_RATE;
      }
      break;
  }
  return price;
}

/**
 * Get price with all options applied (variation, quantity, currency).
 * Updates the price display field and returns calculated total.
 *
 * @returns {number} Calculated total price.
 */
function dskapiGetPriceWithOptions() {
  const dskapi_price = document.getElementById("dskapi_price");
  if (!dskapi_price) return 0;

  const dskapi_eur = parseInt(
    document.getElementById("dskapi_eur")?.value || "0"
  );
  const dskapi_currency_code =
    document.getElementById("dskapi_currency_code")?.value || "BGN";

  // Get variation price if available
  let price = dskapiGetVariationPrice(dskapi_price.value);

  // Clean and convert price format
  price = price.replace(/[^\d.,]/g, "");
  price = dskapiConvertToDotDecimal(price);

  // Get quantity
  const quantity = dskapiGetQuantity();

  // Calculate total
  let dskapi_priceall = parseFloat(price) * quantity;

  // Convert currency if needed
  dskapi_priceall = dskapiConvertCurrency(
    dskapi_priceall,
    dskapi_eur,
    dskapi_currency_code
  );

  // Update price display
  const dskapi_price_txt = document.getElementById("dskapi_price_txt");
  if (dskapi_price_txt) {
    dskapi_price_txt.value = dskapi_priceall.toFixed(2);
  }

  return dskapi_priceall;
}

/**
 * Store previous installment value for validation.
 *
 * @param {number} _old_vnoski - Previous installment count.
 */
function dskapi_pogasitelni_vnoski_input_focus(_old_vnoski) {
  old_vnoski = _old_vnoski;
}

/**
 * Handle installment count change - fetch new payment data from API.
 * Updates installment amount, total payment, GPR and button text displays.
 */
function dskapi_pogasitelni_vnoski_input_change() {
  const vnoskiInput = document.getElementById(
    "dskapi_pogasitelni_vnoski_input"
  );
  const priceTxt = document.getElementById("dskapi_price_txt");
  const cidInput = document.getElementById("dskapi_cid");
  const liveUrlInput = document.getElementById("DSKAPI_LIVEURL");
  const productIdInput = document.getElementById("dskapi_product_id");

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

  var xmlhttpro = createCORSRequest(
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
              document.getElementById("dskapi_vnoska");
            const dskapi_gpr = document.getElementById("dskapi_gpr");
            const dskapi_obshtozaplashtane_input = document.getElementById(
              "dskapi_obshtozaplashtane"
            );

            if (dskapi_vnoska_input) {
              dskapi_vnoska_input.value = dsk_vnoska.toFixed(2);
            }
            if (dskapi_obshtozaplashtane_input) {
              dskapi_obshtozaplashtane_input.value = (
                dsk_vnoska * dskapi_vnoski
              ).toFixed(2);
            }
            if (dskapi_gpr) {
              dskapi_gpr.value = dsk_gpr.toFixed(2);
            }

            // Update button text displays
            const dskapi_vnoski_txt =
              document.getElementById("dskapi_vnoski_txt");
            if (dskapi_vnoski_txt) {
              dskapi_vnoski_txt.textContent = dskapi_vnoski;
            }

            const dskapi_vnoska_txt =
              document.getElementById("dskapi_vnoska_txt");
            if (dskapi_vnoska_txt) {
              dskapi_vnoska_txt.textContent = dsk_vnoska.toFixed(2);
            }

            old_vnoski = dskapi_vnoski;
          } else {
            alert("Избраният брой погасителни вноски е под минималния.");
            vnoskiInput.value = old_vnoski;
          }
        } else {
          alert("Избраният брой погасителни вноски е над максималния.");
          vnoskiInput.value = old_vnoski;
        }
      } catch (e) {
        console.error("DSKAPI: Error parsing response", e);
      }
    }
  };
  xmlhttpro.send();
}

/**
 * Get product ID from form or hidden input.
 *
 * @returns {number} Product ID.
 */
function dskapiGetProductId() {
  // Try hidden input first
  const hiddenInput = document.getElementById("dskapi_product_id");
  if (hiddenInput && hiddenInput.value) {
    return parseInt(hiddenInput.value) || 0;
  }

  // Try form input
  const form = document.querySelector("form.cart");
  if (form) {
    const addToCartInput = form.querySelector('button[name="add-to-cart"]');
    if (addToCartInput && addToCartInput.value) {
      return parseInt(addToCartInput.value) || 0;
    }

    const hiddenAddToCart = form.querySelector('input[name="add-to-cart"]');
    if (hiddenAddToCart && hiddenAddToCart.value) {
      return parseInt(hiddenAddToCart.value) || 0;
    }
  }

  return 0;
}

/**
 * Get selected variation ID from WooCommerce variation form.
 *
 * @returns {number} Variation ID or 0 if not found/not a variable product.
 */
function dskapiGetVariationId() {
  const variationInput = document.querySelector(
    'input[name="variation_id"], input.variation_id'
  );
  if (variationInput && variationInput.value) {
    return parseInt(variationInput.value) || 0;
  }
  return 0;
}

/**
 * Get selected variation attributes from WooCommerce variation form.
 *
 * @returns {Object} Object with attribute names and values.
 */
function dskapiGetVariationAttributes() {
  const attributes = {};
  const attributeSelects = document.querySelectorAll(
    ".variations select, .variations input[type='radio']:checked"
  );

  attributeSelects.forEach((element) => {
    const name = element.name || element.getAttribute("name");
    const value = element.value;
    if (name && value) {
      attributes[name] = value;
    }
  });

  return attributes;
}

/**
 * Redirect to checkout with DSK payment method preselected.
 * Uses AJAX to add product to cart, then redirects to checkout.
 */
function dskapiGoToCheckout() {
  const popup = document.getElementById("dskapi-product-popup-container");
  if (popup) popup.style.display = "none";

  const productId = dskapiGetProductId();
  const quantity = dskapiGetQuantity();
  const variationId = dskapiGetVariationId();
  const variationAttributes = dskapiGetVariationAttributes();
  const checkoutUrl =
    document.getElementById("dskapi_checkout_url")?.value || "/checkout/";
  const paymentMethod =
    document.getElementById("dskapi_payment_method")?.value || "dskapipayment";

  if (!productId) {
    alert("Грешка: Не може да се определи продукта.");
    return;
  }

  // Build form data for add to cart
  const formData = new FormData();
  formData.append("add-to-cart", productId);
  formData.append("quantity", quantity);

  if (variationId > 0) {
    formData.append("variation_id", variationId);
    formData.append("product_id", productId);
    // Add variation attributes
    for (const [key, value] of Object.entries(variationAttributes)) {
      formData.append(key, value);
    }
  }

  // Add selected installments count
  const vnoskiInput = document.getElementById(
    "dskapi_pogasitelni_vnoski_input"
  );
  if (vnoskiInput) {
    formData.append("dskapi_vnoski", vnoskiInput.value);
  }

  // Mark as DSK checkout
  formData.append("dskapi_checkout", "1");
  formData.append("dskapi_gateway", paymentMethod);

  // Show loading state
  const buyBtn = document.getElementById("dskapi_buy_credit");
  if (buyBtn) {
    buyBtn.disabled = true;
    buyBtn.style.opacity = "0.5";
  }

  // Make AJAX request to add to cart
  fetch(window.location.href.split("?")[0], {
    method: "POST",
    body: formData,
    credentials: "same-origin",
  })
    .then((response) => {
      // Redirect to checkout regardless of response
      // WooCommerce will handle the cart
      const separator = checkoutUrl.includes("?") ? "&" : "?";
      window.location.href =
        checkoutUrl +
        separator +
        "payment_method=" +
        encodeURIComponent(paymentMethod);
    })
    .catch((error) => {
      console.error("DSKAPI Error:", error);
      // Still try to redirect
      const separator = checkoutUrl.includes("?") ? "&" : "?";
      window.location.href =
        checkoutUrl +
        separator +
        "payment_method=" +
        encodeURIComponent(paymentMethod);
    });
}

document.addEventListener("DOMContentLoaded", function () {
  const btn_dskapi = document.getElementById("btn_dskapi");
  if (btn_dskapi !== null) {
    const dskapi_button_status = parseInt(
      document.getElementById("dskapi_button_status")?.value || "0"
    );
    const dskapiProductPopupContainer = document.getElementById(
      "dskapi-product-popup-container"
    );
    const dskapi_back_credit = document.getElementById("dskapi_back_credit");
    const dskapi_maxstojnost = document.getElementById("dskapi_maxstojnost");

    // Main button click handler
    btn_dskapi.addEventListener("click", (event) => {
      if (dskapi_button_status == 1) {
        // Direct to checkout mode - skip popup
        dskapiGoToCheckout();
        return;
      }

      // Calculate and display price with options
      const dskapi_priceall = dskapiGetPriceWithOptions();

      // Check max price limit
      const maxPrice = parseFloat(dskapi_maxstojnost?.value || "999999");
      if (dskapi_priceall <= maxPrice) {
        if (dskapiProductPopupContainer) {
          dskapiProductPopupContainer.style.display = "block";
        }
        dskapi_pogasitelni_vnoski_input_change();
      } else {
        alert(
          "Максимално позволената цена за кредит " +
            maxPrice.toFixed(2) +
            " е надвишена!"
        );
      }
    });

    // Back button - close popup
    if (dskapi_back_credit) {
      dskapi_back_credit.addEventListener("click", (event) => {
        if (dskapiProductPopupContainer) {
          dskapiProductPopupContainer.style.display = "none";
        }
      });
    }

    // Buy credit button - add to cart and go to checkout
    document.addEventListener("click", (e) => {
      const btn = e.target.closest("#dskapi_buy_credit");
      if (!btn) return;
      e.preventDefault();

      dskapiGoToCheckout();
    });

    // Dynamic update on quantity change
    const quantityInputs = document.querySelectorAll('[name="quantity"]');
    if (quantityInputs.length > 0) {
      quantityInputs[0].addEventListener("change", () => {
        dskapiGetPriceWithOptions();
        dskapi_pogasitelni_vnoski_input_change();
      });
    }

    // Dynamic update on variation change using MutationObserver
    const targetNode = document.querySelector(
      "div.woocommerce-variation.single_variation"
    );
    if (targetNode !== null && targetNode instanceof Node) {
      const observer = new MutationObserver(function (mutationsList, observer) {
        dskapiGetPriceWithOptions();
        dskapi_pogasitelni_vnoski_input_change();
      });

      const config = {
        childList: true,
        subtree: true,
      };

      observer.observe(targetNode, config);
    }
  }
});
