<?php

/**
 * DSK API Payment Gateway - Admin Menu Registration
 *
 * Registers the plugin's admin menu page in WordPress dashboard.
 *
 * @package DSK_POS_Loans
 * @since   1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registers the DSK API admin settings page.
 *
 * Adds a new options page under Settings menu in WordPress admin.
 * The page displays plugin configuration options.
 *
 * @since 1.0.0
 * @see add_options_page()
 * @return void
 */
function dskapi_admin_actions()
{
    add_options_page(
        'Банка ДСК покупки на Кредит - Настройки на модула', // Page title
        'Банка ДСК покупки на Кредит',                       // Menu title
        'manage_options',                                     // Capability required
        'dskapi-options',                                     // Menu slug
        'dskapi_admin_options'                                // Callback function
    );
}
