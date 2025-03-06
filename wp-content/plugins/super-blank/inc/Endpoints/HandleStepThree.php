<?php

namespace SuperBlank\Endpoints;

if (!defined('ABSPATH')) {
    exit;
}

use WP_REST_Response;
use WP_Error;
use SuperBlank\Quiet_Skin;
use Theme_Upgrader;

class HandleStepThree
{

    public function __construct()
    {

        add_action('wp_ajax_super_blank_step3', [$this, 'handle_step']);
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
        $use_theme = 'astra';

        // Extract Theme
        $this->installTheme($use_theme);

        // Success
        echo wp_json_encode(new WP_REST_Response([
            'success' => true,
            'message' => 'Activating Astra theme...' //'Theme activated successfully'
        ], 200));

        wp_die();
    }

    public function installTheme($theme_slug)
    {

        $default_theme = $theme_slug ? $theme_slug : 'twentytwentyfour';

        $default_theme_object = wp_get_theme($default_theme);

        if (!$default_theme_object->exists()) {

            $skin = new Quiet_Skin();
            $upgrader = new Theme_Upgrader($skin);
            $result = $upgrader->install("https://downloads.wordpress.org/theme/{$default_theme}.zip");

            if (is_wp_error($result)) {

                echo wp_json_encode(new WP_Error('error_data', "Failed to install {$default_theme}: " . $result->get_error_message(), array('status' => 404)));

                wp_die();
            }
        }

        switch_theme($default_theme);

        $themes = wp_get_themes();

        foreach ($themes as $theme_name => $theme) {

            if ($theme_name !== $default_theme) {

                delete_theme($theme_name);
            }
        }
    }
}
