/**
 * DSK API Payment Gateway - WooCommerce Blocks Integration
 *
 * Registers the DSK payment method for WooCommerce block-based checkout.
 * Handles payment method display and gateway pre-selection via URL parameters.
 *
 * @package DSK_POS_Loans
 * @since   1.0.0
 */

/**
 * Payment gateway settings retrieved from WooCommerce.
 *
 * @type {Object}
 */
const settings_dskapi = window.wc.wcSettings.getSetting(
  "dskapipayment_data",
  {}
);

/**
 * Payment gateway display label.
 * Falls back to 'DSK Credit' if no title is configured.
 *
 * @type {string}
 */
const label_dskapi =
  window.wp.htmlEntities.decodeEntities(settings_dskapi.title) || "DSK Credit";

/**
 * Shorthand reference to WordPress createElement function.
 *
 * @type {Function}
 */
const elDskapi = window.wp.element.createElement;

/**
 * Content component for the DSK payment method.
 *
 * Renders the payment fields HTML received from the server.
 * Uses dangerouslySetInnerHTML to render the gateway's custom HTML content.
 *
 * @returns {Object} React element containing the payment description HTML.
 */
const Content_dskapi = () => {
  return elDskapi("div", {
    dangerouslySetInnerHTML: { __html: settings_dskapi.descriptiondskapi },
  });
};

/**
 * DSK Payment Gateway block configuration object.
 *
 * Defines the payment method properties required by WooCommerce Blocks.
 *
 * @type {Object}
 * @property {string} name - Unique identifier for the payment method.
 * @property {string} label - Display label shown to customers.
 * @property {Object} content - React element for checkout display.
 * @property {Object} edit - React element for block editor display.
 * @property {Function} canMakePayment - Callback determining if method is available.
 * @property {string} ariaLabel - Accessibility label for screen readers.
 * @property {Object} supports - Features supported by this payment method.
 */
const Block_Gateway_Dskapi = {
  name: "dskapipayment",
  label: label_dskapi,
  content: Object(window.wp.element.createElement)(Content_dskapi, null),
  edit: Object(window.wp.element.createElement)(Content_dskapi, null),
  canMakePayment: () => true,
  ariaLabel: label_dskapi,
  supports: {
    features: settings_dskapi.supports,
  },
};

/**
 * Register the DSK payment method with WooCommerce Blocks.
 */
window.wc.wcBlocksRegistry.registerPaymentMethod(Block_Gateway_Dskapi);

/**
 * Gateway Pre-selection Handler (IIFE)
 *
 * Handles automatic gateway selection when a 'gateway' URL parameter is present.
 * This allows direct links to checkout with the DSK payment method pre-selected.
 *
 * @example URL: /checkout/?gateway=dskapipayment
 */
(function () {
  /**
   * URL parameters from the current page.
   *
   * @type {URLSearchParams}
   */
  const paramsDskapi = new URLSearchParams(window.location.search);

  /**
   * The desired gateway ID from URL parameter.
   *
   * @type {string|null}
   */
  const wantedDskapi = paramsDskapi.get("gateway");

  // Exit if no gateway parameter specified
  if (!wantedDskapi) return;

  /**
   * WooCommerce Blocks registry reference.
   *
   * @type {Object|undefined}
   */
  const registryDskapi = window.wc && window.wc.wcBlocksRegistry;

  /**
   * Function to register payment method extension callbacks.
   *
   * @type {Function|undefined}
   */
  const registerDskapiPaymentMethodExtensionCallbacks =
    registryDskapi &&
    registryDskapi.registerDskapiPaymentMethodExtensionCallbacks;

  // Exit if the registration function is not available
  if (typeof registerDskapiPaymentMethodExtensionCallbacks !== "function") {
    return;
  }

  /**
   * Register extension callbacks to filter available payment methods.
   * Only shows the payment method matching the URL parameter.
   */
  registerDskapiPaymentMethodExtensionCallbacks("dskapipayment/ext", {
    "*": (methodId) => ({
      canMakePayment: () => {
        if (!wantedDskapi) return true;
        return methodId === wantedDskapi;
      },
    }),
  });
})();
