/**
 * Shared WordPress AJAX helpers (server-side nonce + installment calculation).
 *
 * @package DSK_POS_Loans
 * @since   1.2.2
 */

/**
 * Fetches a fresh nonce from WordPress (same session as subsequent AJAX calls).
 *
 * @returns {Promise<string>}
 */
function dskapiGetNonce() {
	if (typeof dskapi_ajax_vars === "undefined" || !dskapi_ajax_vars.ajax_url) {
		return Promise.reject(
			new Error("DSKAPI: dskapi_ajax_vars is not configured."),
		);
	}

	return fetch(dskapi_ajax_vars.ajax_url, {
		method: "POST",
		headers: {
			"Content-Type": "application/x-www-form-urlencoded",
		},
		body: "action=dskapi_get_nonce",
		credentials: "same-origin",
	})
		.then((response) => response.json())
		.then((res) => {
			if (res && res.success && res.data && res.data.nonce) {
				dskapi_ajax_vars.nonce = res.data.nonce;
				return res.data.nonce;
			}
			throw new Error("DSKAPI: Could not fetch nonce");
		});
}

/**
 * Fetches getproductcustom data through the store (cached in DB).
 *
 * @param {Object}   params     Request parameters.
 * @param {string}   params.cid Calculator ID.
 * @param {number}   params.price Product price.
 * @param {string|number} params.product_id Product ID.
 * @param {number}   params.dskapi_vnoski Installment count.
 * @param {Function} onComplete Callback when request finishes (xhr).
 * @returns {void}
 */
function dskapiFetchProductCustom(params, onComplete) {
	if (typeof dskapi_ajax_vars === "undefined" || !dskapi_ajax_vars.ajax_url) {
		console.error("DSKAPI: dskapi_ajax_vars is not configured.");
		return;
	}

	dskapiGetNonce()
		.then((nonce) => {
			const query = new URLSearchParams({
				action: "dskapi_get_product_custom",
				nonce: nonce,
				cid: params.cid,
				price: params.price,
				product_id: params.product_id,
				dskapi_vnoski: params.dskapi_vnoski,
			});

			const xhr = new XMLHttpRequest();
			xhr.open(
				"GET",
				dskapi_ajax_vars.ajax_url + "?" + query.toString(),
				true,
			);
			xhr.onreadystatechange = function () {
				if (xhr.readyState === 4) {
					onComplete(xhr);
				}
			};
			xhr.send();
		})
		.catch((error) => {
			console.error("DSKAPI:", error);
		});
}
