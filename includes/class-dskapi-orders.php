<?php

/**
 * DSK API Orders Helper Class
 * 
 * Handles all database operations for DSK payment orders.
 *
 * @package DSK_POS_Loans
 * @since 1.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Dskapi_Orders
 * 
 * Helper class for managing DSK payment orders in the database.
 */
class Dskapi_Orders
{
    /**
     * Database table name (without prefix).
     *
     * @var string
     */
    private static $table = 'dskpayment_orders';

    /**
     * Order status labels.
     *
     * @var array
     */
    private static $status_labels = [
        0 => 'Създадена Апликация',
        1 => 'Избрана финансова схема',
        2 => 'Попълнена Апликация',
        3 => 'Изпратен Банка',
        4 => 'Неуспешен контакт с клиента',
        5 => 'Анулирана апликация',
        6 => 'Отказана апликация',
        7 => 'Подписан договор',
        8 => 'Усвоен кредит',
    ];

    /**
     * Get the full table name with prefix.
     *
     * @return string
     */
    public static function get_table_name()
    {
        global $wpdb;
        return $wpdb->prefix . self::$table;
    }

    /**
     * Get order by WooCommerce order ID.
     *
     * @param int $order_id WooCommerce order ID.
     * @return object|null Order row or null if not found.
     */
    public static function get_by_order_id($order_id)
    {
        global $wpdb;
        $table = self::get_table_name();

        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE order_id = %d", $order_id)
        );
    }

    /**
     * Get order status by WooCommerce order ID.
     *
     * @param int $order_id WooCommerce order ID.
     * @return int|null Status code or null if not found.
     */
    public static function get_status($order_id)
    {
        $order = self::get_by_order_id($order_id);
        return $order ? (int) $order->order_status : null;
    }

    /**
     * Get order status label by WooCommerce order ID.
     *
     * @param int $order_id WooCommerce order ID.
     * @return string Status label or empty string if not found.
     */
    public static function get_status_label($order_id)
    {
        $status = self::get_status($order_id);
        return self::status_to_label($status);
    }

    /**
     * Convert status code to label.
     *
     * @param int|null $status Status code.
     * @return string Status label.
     */
    public static function status_to_label($status)
    {
        if ($status === null) {
            return '';
        }
        return self::$status_labels[$status] ?? self::$status_labels[0];
    }

    /**
     * Get all status labels.
     *
     * @return array
     */
    public static function get_status_labels()
    {
        return self::$status_labels;
    }

    /**
     * Create a new order record.
     *
     * @param int $order_id WooCommerce order ID.
     * @param int $status   Initial status (default 0).
     * @return int|false The number of rows inserted, or false on error.
     */
    public static function create($order_id, $status = 0)
    {
        global $wpdb;
        $table = self::get_table_name();

        // Check if already exists
        if (self::get_by_order_id($order_id)) {
            return self::update_status($order_id, $status);
        }

        return $wpdb->insert(
            $table,
            [
                'order_id' => $order_id,
                'order_status' => $status,
            ],
            ['%d', '%d']
        );
    }

    /**
     * Update order status.
     *
     * @param int $order_id WooCommerce order ID.
     * @param int $status   New status code.
     * @return int|false The number of rows updated, or false on error.
     */
    public static function update_status($order_id, $status)
    {
        global $wpdb;
        $table = self::get_table_name();

        return $wpdb->update(
            $table,
            ['order_status' => $status],
            ['order_id' => $order_id],
            ['%d'],
            ['%d']
        );
    }

    /**
     * Delete order record.
     *
     * @param int $order_id WooCommerce order ID.
     * @return int|false The number of rows deleted, or false on error.
     */
    public static function delete($order_id)
    {
        global $wpdb;
        $table = self::get_table_name();

        return $wpdb->delete(
            $table,
            ['order_id' => $order_id],
            ['%d']
        );
    }

    /**
     * Check if order exists.
     *
     * @param int $order_id WooCommerce order ID.
     * @return bool
     */
    public static function exists($order_id)
    {
        return self::get_by_order_id($order_id) !== null;
    }

    /**
     * Get orders by status.
     *
     * @param int $status Status code.
     * @param int $limit  Maximum number of results (default 100).
     * @return array
     */
    public static function get_by_status($status, $limit = 100)
    {
        global $wpdb;
        $table = self::get_table_name();

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE order_status = %d ORDER BY updated_at DESC LIMIT %d",
                $status,
                $limit
            )
        );
    }
}
