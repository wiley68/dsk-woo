<?php

/**
 * DSK API HTTP Client
 * 
 * Handles all HTTP requests to the DSK Bank API.
 *
 * @package DSK_POS_Loans
 * @since 1.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Dskapi_Client
 * 
 * HTTP client for DSK Bank API requests.
 */
class Dskapi_Client
{
    /**
     * Default request timeout in seconds.
     *
     * @var int
     */
    private static $timeout = 6;

    /**
     * Maximum redirects.
     *
     * @var int
     */
    private static $max_redirects = 2;

    /**
     * Make a GET request to the DSK API.
     *
     * @param string $endpoint API endpoint (e.g., '/function/getrek.php').
     * @param array  $params   Query parameters.
     * @param array  $options  Additional options (timeout, etc.).
     * @return array|null Decoded JSON response or null on failure.
     */
    public static function get($endpoint, $params = [], $options = [])
    {
        $url = self::build_url($endpoint, $params);
        return self::request($url, 'GET', null, $options);
    }

    /**
     * Make a POST request to the DSK API.
     *
     * @param string $endpoint API endpoint.
     * @param array  $data     POST data.
     * @param array  $options  Additional options.
     * @return array|null Decoded JSON response or null on failure.
     */
    public static function post($endpoint, $data = [], $options = [])
    {
        $url = self::build_url($endpoint);
        return self::request($url, 'POST', $data, $options);
    }

    /**
     * Make a POST request with JSON body.
     *
     * @param string $endpoint API endpoint.
     * @param array  $data     Data to encode as JSON.
     * @param array  $options  Additional options.
     * @return array|null Decoded JSON response or null on failure.
     */
    public static function post_json($endpoint, $data = [], $options = [])
    {
        $options['json'] = true;
        return self::post($endpoint, $data, $options);
    }

    /**
     * Build full URL with query parameters.
     *
     * @param string $endpoint API endpoint.
     * @param array  $params   Query parameters.
     * @return string Full URL.
     */
    private static function build_url($endpoint, $params = [])
    {
        $url = rtrim(DSKAPI_LIVEURL, '/') . '/' . ltrim($endpoint, '/');

        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        return $url;
    }

    /**
     * Execute HTTP request.
     *
     * @param string      $url     Full URL.
     * @param string      $method  HTTP method (GET, POST).
     * @param array|null  $data    Request data for POST.
     * @param array       $options Additional options.
     * @return array|null Decoded JSON response or null on failure.
     */
    private static function request($url, $method = 'GET', $data = null, $options = [])
    {
        $timeout = $options['timeout'] ?? self::$timeout;
        $max_redirects = $options['max_redirects'] ?? self::$max_redirects;
        $is_json = $options['json'] ?? false;

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_MAXREDIRS => $max_redirects,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');

            if ($is_json) {
                $json_data = json_encode($data);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($json_data),
                    'Cache-Control: no-cache',
                ]);
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
        }

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        // Log errors if WP_DEBUG is enabled
        if ($error && WP_DEBUG) {
            error_log('Dskapi_Client Error: ' . $error . ' | URL: ' . $url);
        }

        if ($response === false || $http_code >= 400) {
            return null;
        }

        $decoded = json_decode($response, true);

        return $decoded;
    }

    /**
     * Get calculator ID from options.
     *
     * @return string
     */
    public static function get_cid()
    {
        return (string) get_option('dskapi_cid', '');
    }

    /**
     * Check if plugin is enabled.
     *
     * @return bool
     */
    public static function is_enabled()
    {
        return get_option('dskapi_status') === 'on';
    }

    // =========================================================================
    // Convenience methods for common API calls
    // =========================================================================

    /**
     * Get advertisement data.
     *
     * @param string|null $cid Calculator ID (optional, uses saved option if null).
     * @return array|null
     */
    public static function get_reklama($cid = null)
    {
        $cid = $cid ?? self::get_cid();
        return self::get('/function/getrek.php', ['cid' => $cid]);
    }

    /**
     * Get min/max values for calculator.
     *
     * @param string|null $cid Calculator ID.
     * @return array|null
     */
    public static function get_minmax($cid = null)
    {
        $cid = $cid ?? self::get_cid();
        return self::get('/function/getminmax.php', ['cid' => $cid]);
    }

    /**
     * Get EUR conversion settings.
     *
     * @param string|null $cid Calculator ID.
     * @return array|null
     */
    public static function get_eur($cid = null)
    {
        $cid = $cid ?? self::get_cid();
        return self::get('/function/geteur.php', ['cid' => $cid]);
    }

    /**
     * Get product calculation data.
     *
     * @param float       $price      Product price.
     * @param int         $product_id Product ID.
     * @param string|null $cid        Calculator ID.
     * @return array|null
     */
    public static function get_product($price, $product_id, $cid = null)
    {
        $cid = $cid ?? self::get_cid();
        return self::get('/function/getproduct.php', [
            'cid' => $cid,
            'price' => $price,
            'product_id' => $product_id,
        ]);
    }

    /**
     * Submit order to DSK API.
     *
     * @param array $data Encrypted order data.
     * @return array|null
     */
    public static function add_order($data)
    {
        return self::post_json('/function/addorders.php', $data);
    }

    // =========================================================================
    // Utility methods
    // =========================================================================

    /**
     * Parse installment visibility bitmask into an array.
     * 
     * Converts a bitmask where bit N corresponds to month (N + 3) into
     * an array of visible months (3-48).
     *
     * @param int $bitmask       The visibility bitmask from API.
     * @param int $default_month The default selected month (always visible).
     * @return array Array with month numbers as keys and boolean visibility as values.
     */
    public static function parse_installment_visibility($bitmask, $default_month = 0)
    {
        $result = [];

        // Months range from 3 to 48, bit 0 = month 3, bit 1 = month 4, etc.
        for ($month = 3; $month <= 48; $month++) {
            $bit_position = $month - 3;
            $bit_value = 1 << $bit_position; // 2^bit_position

            // Visible if bit is set OR if it's the default month
            $result[$month] = (($bitmask & $bit_value) !== 0) || ($default_month === $month);
        }

        return $result;
    }

    /**
     * Check if current request is from a mobile device.
     *
     * @return bool
     */
    public static function is_mobile()
    {
        $useragent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        if (empty($useragent)) {
            return false;
        }

        $mobile_regex = '/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i';

        $mobile_regex_short = '/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i';

        return preg_match($mobile_regex, $useragent) || preg_match($mobile_regex_short, substr($useragent, 0, 4));
    }

    /**
     * Get product ID from cart items.
     * Returns product ID if cart contains only one unique product, otherwise returns 0.
     *
     * @return int Product ID or 0 if multiple products or empty cart.
     */
    public static function get_cart_product_id()
    {
        if (!function_exists('WC') || !WC()->cart) {
            return 0;
        }

        $cart_items = WC()->cart->get_cart();
        if (empty($cart_items)) {
            return 0;
        }

        $product_ids = array();
        foreach ($cart_items as $cart_item) {
            if (!isset($cart_item['product_id'])) {
                continue;
            }
            $product_ids[(int)$cart_item['product_id']] = true;
        }

        // Return product ID only if there's exactly one unique product
        if (count($product_ids) === 1) {
            reset($product_ids);
            return (int)key($product_ids);
        }

        return 0;
    }

    /**
     * Get cart total price.
     *
     * @return float Cart total or 0.
     */
    public static function get_cart_total()
    {
        if (!function_exists('WC') || !WC()->cart) {
            return 0;
        }

        return (float)WC()->cart->get_total('edit');
    }
}
