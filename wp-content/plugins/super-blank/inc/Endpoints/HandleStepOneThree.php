<?php

namespace SuperBlank\Endpoints;

if (!defined('ABSPATH')) {
    exit;
}

use WP_REST_Response;
use WP_Error;

class HandleStepOneThree
{

    public function __construct()
    {

        add_action('wp_ajax_super_blank_step1_3', [$this, 'handle_step']);
    }

    public function handle_step()
    {

        // Checked POST nonce is not empty.
        if (empty($_POST['nonce'])) wp_die('0');

        $nonce = sanitize_key(wp_unslash($_POST['nonce']));

        if (!wp_verify_nonce($nonce, 'install_super_blank')) {

            echo wp_json_encode(new WP_Error('error_data', 'Invalid nonce', array('status' => 403)));

            wp_die();
        }

        /**
         * Execution code here
         */
        // Delete extra tables
        $this->deleteExtraTables();

        // Success
        echo wp_json_encode(new WP_REST_Response([
            'success' => true,
            'message' => 'Removing extra tables...'
        ], 200));

        wp_die();
    }

    public function deleteExtraTables()
    {

        global $wpdb;

        $tables = array(
            'woocommerce_order_items',
            'woocommerce_order_itemmeta',
            'woocommerce_tax_rates',
            'woocommerce_tax_rate_locations',
            'woocommerce_shipping_zones',
            'woocommerce_shipping_zone_locations',
            'woocommerce_shipping_zone_methods',
            'woocommerce_payment_tokens',
            'woocommerce_payment_tokenmeta',
            'woocommerce_log',
            'wc_product_meta_lookup',
            'wc_tax_rate_classes',
            'wc_webhooks',
            'wc_download_log',
            'wc_order_stats',
            'wc_order_product_lookup',
            'wc_order_tax_lookup',
            'wc_order_coupon_lookup',
            'wc_admin_notes',
            'wc_admin_note_actions',
            'wc_customer_lookup',
            'wc_category_lookup',
            'wc_order_addresses',
            'wc_order_operational_data',
            'wc_orders',
            'wc_orders_meta',
            'wc_product_attributes_lookup',
            'wc_product_download_directories',
            'wc_rate_limits',
            'wc_reserved_stock',
            'woocommerce_api_keys',
            'woocommerce_attribute_taxonomies',
            'woocommerce_downloadable_product_permissions',
            'woocommerce_sessions',
            'wpforms_logs',
            'wpforms_payment_meta',
            'wpforms_payments',
            'wpforms_tasks_meta',
        );

        foreach ($tables as $table) {

            $table_name = $wpdb->prefix . $table;

            $table_exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(1) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
                    DB_NAME,
                    $table_name
                )
            );

            if ($table_exists) {

                $wpdb->query(
                    $wpdb->prepare("DROP TABLE IF EXISTS `%s`", $table_name)
                );
            }
        }
    }
}
