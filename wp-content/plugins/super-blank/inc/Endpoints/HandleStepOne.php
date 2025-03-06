<?php

namespace SuperBlank\Endpoints;

use WP_REST_Response;
use WP_Error;
use SuperBlank\Quiet_Skin;
use Theme_Upgrader;

if (!defined('ABSPATH')) {
    exit;
}

class HandleStepOne
{

    public function __construct()
    {

        add_action('wp_ajax_super_blank_step1', [$this, 'handle_step']);
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

        $this->resetTheme();

        $this->deactivateAllPlugins();

        // Success
        echo wp_json_encode(new WP_REST_Response([
            'success' => true,
            'message' => 'Starting fresh site...'
        ], 200)); 

        wp_die();
    }

    public function resetTheme()
    {

        $default_theme = 'twentytwentyfour';
        $default_theme_object = wp_get_theme($default_theme);

        // Ensure Twenty Twenty-Four is installed and start treating it as the default theme
        if (!$default_theme_object->exists()) {

            $skin = new Quiet_Skin();
            $upgrader = new Theme_Upgrader($skin);
            $result = $upgrader->install("https://downloads.wordpress.org/theme/{$default_theme}.zip");

            if (is_wp_error($result)) {

                echo wp_json_encode(new WP_Error('error_data', "Failed to install {$default_theme}: " . $result->get_error_message(), array('status' => 404)));

                wp_die();
            }
        }

        // Set Twenty Twenty-Four as the active theme
        switch_theme($default_theme);

        // Remove all themes except Twenty Twenty-Four
        $themes = wp_get_themes();

        foreach ($themes as $theme_name => $theme) {

            if ($theme_name !== $default_theme) {

                delete_theme($theme_name);
            }
        }

        // Remove Gutenberg-related content
        $parts = get_posts(['post_type' => ['wp_template_part', 'wp_navigation', 'wp_template', 'wp_global_styles'], 'posts_per_page' => -1, 'post_status' => 'any']);

        foreach ($parts as $part) {

            wp_delete_post($part->ID, true);
        }
    }

    public function deactivateAllPlugins()
    {

        $active_plugins = get_option('active_plugins');
        $plugins_to_keep = [
            'super-blank/super-blank.php',
            'elementor-json-to-php/elementor-json-to-php.php',
            'super-blank-options-cleanup/super-blank-options-cleanup.php',
        ];

        foreach ($active_plugins as $plugin) {

            if (!in_array($plugin, $plugins_to_keep)) {

                if (!function_exists('deactivate_plugins')) {

                    require_once(ABSPATH . 'wp-admin/includes/plugin.php');
                }

                if (function_exists('deactivate_plugins')) {

                    deactivate_plugins($plugin);
                }
            }
        }

        wp_cache_flush();
    }
}
