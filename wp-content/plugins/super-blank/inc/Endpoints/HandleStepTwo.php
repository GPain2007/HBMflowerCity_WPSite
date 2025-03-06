<?php

namespace SuperBlank\Endpoints;

if (!defined('ABSPATH')) {
    exit;
}

use WP_REST_Response;
use WP_Error;

class HandleStepTwo
{

    public function __construct()
    {

        add_action('wp_ajax_super_blank_step2', [$this, 'handle_step']);
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

        // Download package
        $this->deleteInactivePlugins();

        // Success
        echo wp_json_encode(new WP_REST_Response([
            'success' => true,
            'message' => 'Getting design ready...'
        ], 200));

        wp_die();
    }

    public function deleteInactivePlugins()
    {

        $all_plugins = get_plugins();
        $active_plugins = get_option('active_plugins');

        foreach ($all_plugins as $plugin_path => $plugin_data) {

            if (!in_array($plugin_path, $active_plugins)) {

                $plugin_dir = WP_PLUGIN_DIR . '/' . dirname($plugin_path);
                $plugin_file = WP_PLUGIN_DIR . '/' . $plugin_path;

                if (dirname($plugin_path) === '.') {

                    wp_delete_file($plugin_file);
                } else {

                    superBlankDeleteDirectory($plugin_dir);
                }
            }
        }

        // Flush caches (prevent plugin folder not found errors)
        wp_clean_plugins_cache(true);
        wp_cache_flush();
    }
}
