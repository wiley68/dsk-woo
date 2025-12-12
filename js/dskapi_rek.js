/**
 * DSK API Payment Gateway - Advertisement Banner JavaScript
 *
 * Handles the visibility toggle for the DSK Bank promotional
 * label/banner displayed on the storefront.
 *
 * @package DSK_POS_Loans
 * @since   1.0.0
 */

/**
 * Toggles the visibility of the DSK promotional label container.
 *
 * Uses CSS visibility and opacity properties for smooth fade transitions.
 * When hiding: applies 0.5s ease transition for fade-out effect.
 * When showing: instantly sets visibility and opacity to visible.
 *
 * @returns {void}
 */
function DskapiChangeContainer() {
  const dskapi_label_container = document.getElementsByClassName(
    "dskapi-label-container"
  )[0];

  if (dskapi_label_container.style.visibility == "visible") {
    // Hide with fade-out transition
    dskapi_label_container.style.visibility = "hidden";
    dskapi_label_container.style.opacity = 0;
    dskapi_label_container.style.transition =
      "visibility 0s, opacity 0.5s ease";
  } else {
    // Show immediately
    dskapi_label_container.style.visibility = "visible";
    dskapi_label_container.style.opacity = 1;
  }
}
