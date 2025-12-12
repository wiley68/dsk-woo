/**
 * DSK API Payment Gateway - Product Page JavaScript
 *
 * Handles credit button functionality on WooCommerce product pages.
 * Manages credit calculation popup, dynamic price updates based on
 * variations and quantity, and direct checkout flow.
 *
 * @package DSK_POS_Loans
 * @since   1.0.0
 */

/**
 * Stores the previous installment count for validation/rollback.
 *
 * @type {number|undefined}
 */
let old_vnoski;

/**
 * Converts a price string to dot decimal format.
 *
 * Handles various international number formats:
 * - European format: 1.234,56 → 1234.56
 * - US format: 1,234.56 → 1234.56
 * - Single comma as decimal: 123,45 → 123.45
 * - Multiple commas as thousands: 1,234,567 → 1234567
 *
 * @param {string} price - Price string to convert.
 * @returns {string} Price with dot as decimal separator.
 */
function dskapiConvertToDotDecimal(price) {
  price = price.trim();
  if (price.includes(".") && price.includes(",")) {
    // Both separators present - determine which is decimal
    if (price.lastIndexOf(",") < price.lastIndexOf(".")) {
      // US format: comma is thousands, dot is decimal
      price = price.replace(/,/g, "");
    } else {
      // European format: dot is thousands, comma is decimal
      price = price.replace(/\./g, "").replace(/,/g, ".");
    }
  } else if (price.includes(",")) {
    if (price.split(",").length - 1 === 1) {
      // Single comma - treat as decimal separator
      price = price.replace(/,/g, ".");
    } else {
      // Multiple commas - treat as thousands separators
      price = price.replace(/,/g, "");
    }
  }
  return price;
}

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
 * Extracts variation price from WooCommerce variation price element.
 *
 * Handles both regular and sale prices by checking:
 * 1. Regular price span
 * 2. Sale price inside <ins> tag (takes precedence)
 *
 * @param {string} fallbackPrice - Default price if variation not found.
 * @returns {string} Extracted price string.
 */
function dskapiGetVariationPrice(fallbackPrice) {
  let price = fallbackPrice;

  // Find variation price container
  const variationDiv = document.getElementsByClassName(
    "woocommerce-variation-price"
  );
  if (typeof variationDiv[0] === "undefined") {
    return price;
  }

  // Get price spans
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

  // Check for sale price (inside <ins> tag) - overrides regular price
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
 * Gets the current quantity from the product quantity input field.
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
 * Converts price based on EUR/BGN currency settings.
 *
 * Conversion modes (eurSetting):
 * - 0: No conversion
 * - 1: Convert EUR to BGN (multiply by rate)
 * - 2: Convert BGN to EUR (divide by rate)
 *
 * Uses fixed EUR/BGN exchange rate of 1.95583.
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
      // Setting 1: Store displays EUR, bank needs BGN
      if (currencyCode === "EUR") {
        return price * EUR_BGN_RATE;
      }
      break;
    case 2:
      // Setting 2: Store displays BGN, bank needs EUR
      if (currencyCode === "BGN") {
        return price / EUR_BGN_RATE;
      }
      break;
  }
  return price;
}

/**
 * Calculates total price with all options applied.
 *
 * Combines:
 * - Base product price or variation price
 * - Quantity multiplier
 * - Currency conversion (EUR/BGN)
 *
 * Updates the hidden price display field for API requests.
 *
 * @returns {number} Calculated total price.
 */
function dskapiGetPriceWithOptions() {
  const dskapi_price = document.getElementById("dskapi_price");
  if (!dskapi_price) return 0;

  // Get currency settings
  const dskapi_eur = parseInt(
    document.getElementById("dskapi_eur")?.value || "0"
  );
  const dskapi_currency_code =
    document.getElementById("dskapi_currency_code")?.value || "BGN";

  // Get variation price if available, otherwise use base price
  let price = dskapiGetVariationPrice(dskapi_price.value);

  // Clean price string - remove currency symbols and whitespace
  price = price.replace(/[^\d.,]/g, "");
  price = dskapiConvertToDotDecimal(price);

  // Get quantity
  const quantity = dskapiGetQuantity();

  // Calculate total
  let dskapi_priceall = parseFloat(price) * quantity;

  // Apply currency conversion if needed
  dskapi_priceall = dskapiConvertCurrency(
    dskapi_priceall,
    dskapi_eur,
    dskapi_currency_code
  );

  // Update hidden price field for API
  const dskapi_price_txt = document.getElementById("dskapi_price_txt");
  if (dskapi_price_txt) {
    dskapi_price_txt.value = dskapi_priceall.toFixed(2);
  }

  return dskapi_priceall;
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
function dskapi_pogasitelni_vnoski_input_focus(_old_vnoski) {
  old_vnoski = _old_vnoski;
}

/**
 * Handles installment count change event.
 *
 * Fetches new payment data from DSK Bank API when user changes
 * the installment count. Updates all display fields:
 * - Monthly installment amount (popup and button)
 * - Total payment amount
 * - Annual Percentage Rate (GPR)
 * - Installment count display on button
 *
 * Shows error alerts and reverts to previous value if:
 * - Selected installments below minimum allowed
 * - Selected installments above maximum allowed
 *
 * @returns {void}
 */
function dskapi_pogasitelni_vnoski_input_change() {
  // Get all required DOM elements
  const vnoskiInput = document.getElementById(
    "dskapi_pogasitelni_vnoski_input"
  );
  const priceTxt = document.getElementById("dskapi_price_txt");
  const cidInput = document.getElementById("dskapi_cid");
  const liveUrlInput = document.getElementById("DSKAPI_LIVEURL");
  const productIdInput = document.getElementById("dskapi_product_id");

  // Validate required elements exist
  if (
    !vnoskiInput ||
    !priceTxt ||
    !cidInput ||
    !liveUrlInput ||
    !productIdInput
  ) {
    return;
  }

  // Extract values for API request
  const dskapi_vnoski = parseFloat(vnoskiInput.value);
  const dskapi_price = parseFloat(priceTxt.value);
  const dskapi_cid = cidInput.value;
  const DSKAPI_LIVEURL = liveUrlInput.value;
  const dskapi_product_id = productIdInput.value;

  // Build and send API request
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

  /**
   * Handle API response.
   * Updates UI elements with new values or shows validation error.
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

            // Store new value as current for future rollbacks
            old_vnoski = dskapi_vnoski;
          } else {
            // Installments below minimum - show error and revert
            alert("Избраният брой погасителни вноски е под минималния.");
            vnoskiInput.value = old_vnoski;
          }
        } else {
          // Installments above maximum - show error and revert
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
 * Gets the product ID from the page.
 *
 * Attempts to find product ID from multiple sources:
 * 1. Hidden input field (#dskapi_product_id)
 * 2. Add to cart button value
 * 3. Hidden add-to-cart input
 *
 * @returns {number} Product ID or 0 if not found.
 */
function dskapiGetProductId() {
  // Try hidden input first
  const hiddenInput = document.getElementById("dskapi_product_id");
  if (hiddenInput && hiddenInput.value) {
    return parseInt(hiddenInput.value) || 0;
  }

  // Try form elements
  const form = document.querySelector("form.cart");
  if (form) {
    // Check add-to-cart button
    const addToCartInput = form.querySelector('button[name="add-to-cart"]');
    if (addToCartInput && addToCartInput.value) {
      return parseInt(addToCartInput.value) || 0;
    }

    // Check hidden input
    const hiddenAddToCart = form.querySelector('input[name="add-to-cart"]');
    if (hiddenAddToCart && hiddenAddToCart.value) {
      return parseInt(hiddenAddToCart.value) || 0;
    }
  }

  return 0;
}

/**
 * Gets the selected variation ID from WooCommerce variation form.
 *
 * Looks for the variation_id input that WooCommerce populates
 * when a customer selects product options.
 *
 * @returns {number} Variation ID or 0 if not a variable product or no variation selected.
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
 * Gets selected variation attributes from WooCommerce variation form.
 *
 * Collects all selected attribute values from:
 * - Dropdown selects
 * - Radio button inputs (for swatches)
 *
 * @returns {Object} Object with attribute names as keys and selected values.
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
 * Adds product to cart via AJAX and redirects to checkout.
 *
 * Performs the following steps:
 * 1. Closes the popup if open
 * 2. Gathers product data (ID, quantity, variations)
 * 3. Submits add-to-cart request via AJAX
 * 4. Redirects to checkout with DSK payment method preselected
 *
 * Handles both simple and variable products.
 * Shows loading state on buy button during processing.
 *
 * @returns {void}
 */
function dskapiGoToCheckout() {
  // Close popup if open
  const popup = document.getElementById("dskapi-product-popup-container");
  if (popup) popup.style.display = "none";

  // Gather product data
  const productId = dskapiGetProductId();
  const quantity = dskapiGetQuantity();
  const variationId = dskapiGetVariationId();
  const variationAttributes = dskapiGetVariationAttributes();
  const checkoutUrl =
    document.getElementById("dskapi_checkout_url")?.value || "/checkout/";
  const paymentMethod =
    document.getElementById("dskapi_payment_method")?.value || "dskapipayment";

  // Validate product ID
  if (!productId) {
    alert("Грешка: Не може да се определи продукта.");
    return;
  }

  // Build form data for add to cart request
  const formData = new FormData();
  formData.append("add-to-cart", productId);
  formData.append("quantity", quantity);

  // Add variation data if applicable
  if (variationId > 0) {
    formData.append("variation_id", variationId);
    formData.append("product_id", productId);
    // Add each variation attribute
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

  // Mark request for DSK checkout flow
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
      // Still try to redirect on error
      const separator = checkoutUrl.includes("?") ? "&" : "?";
      window.location.href =
        checkoutUrl +
        separator +
        "payment_method=" +
        encodeURIComponent(paymentMethod);
    });
}

/**
 * Main initialization - runs when DOM is fully loaded.
 *
 * Sets up all event listeners and observers for:
 * - Main DSK credit button click
 * - Popup close button
 * - Buy on credit button
 * - Quantity input changes
 * - Variation changes (via MutationObserver)
 *
 * @listens document#DOMContentLoaded
 */
document.addEventListener("DOMContentLoaded", function () {
  const btn_dskapi = document.getElementById("btn_dskapi");
  if (btn_dskapi !== null) {
    // Get configuration elements
    const dskapi_button_status = parseInt(
      document.getElementById("dskapi_button_status")?.value || "0"
    );
    const dskapiProductPopupContainer = document.getElementById(
      "dskapi-product-popup-container"
    );
    const dskapi_back_credit = document.getElementById("dskapi_back_credit");
    const dskapi_maxstojnost = document.getElementById("dskapi_maxstojnost");

    /**
     * Main button click handler.
     *
     * Behavior depends on button status:
     * - Status 1: Direct checkout (skip popup)
     * - Status 0: Show credit calculation popup
     *
     * Validates price against maximum allowed credit amount.
     */
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
        // Show popup
        if (dskapiProductPopupContainer) {
          dskapiProductPopupContainer.style.display = "block";
        }
        // Trigger initial calculation
        dskapi_pogasitelni_vnoski_input_change();
      } else {
        // Price exceeds maximum - show error
        alert(
          "Максимално позволената цена за кредит " +
            maxPrice.toFixed(2) +
            " е надвишена!"
        );
      }
    });

    /**
     * Back/Cancel button click handler.
     * Closes the popup without any action.
     */
    if (dskapi_back_credit) {
      dskapi_back_credit.addEventListener("click", (event) => {
        if (dskapiProductPopupContainer) {
          dskapiProductPopupContainer.style.display = "none";
        }
      });
    }

    /**
     * Buy on credit button click handler.
     * Uses event delegation to handle dynamically updated button.
     */
    document.addEventListener("click", (e) => {
      const btn = e.target.closest("#dskapi_buy_credit");
      if (!btn) return;
      e.preventDefault();

      dskapiGoToCheckout();
    });

    /**
     * Quantity input change handler.
     * Recalculates price and installments when quantity changes.
     */
    const quantityInputs = document.querySelectorAll('[name="quantity"]');
    if (quantityInputs.length > 0) {
      quantityInputs[0].addEventListener("change", () => {
        dskapiGetPriceWithOptions();
        dskapi_pogasitelni_vnoski_input_change();
      });
    }

    /**
     * Variation change observer.
     *
     * Uses MutationObserver to detect when WooCommerce updates
     * the variation display (price, availability, etc.).
     * Triggers recalculation of price and installments.
     */
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
