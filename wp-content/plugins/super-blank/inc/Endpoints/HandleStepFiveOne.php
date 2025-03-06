<?php

namespace SuperBlank\Endpoints;

if (!defined('ABSPATH')) {
    exit;
}

use WP_REST_Response;
use WP_Error;

class HandleStepFiveOne
{

    public function __construct()
    {

        add_action('wp_ajax_super_blank_step5_1', [$this, 'handle_step']);
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

        if (!empty($_POST['headerMenuData'])) {

            // Create Header Menu
            $this->createHeaderMenu(wp_unslash($_POST['headerMenuData']));
        }

        if (!empty($_POST['footerMenuData'])) {

            // Create Footer Menu
            $this->createFooterMenu(wp_unslash($_POST['footerMenuData']));
        }

        // Success
        echo wp_json_encode(new WP_REST_Response([
            'success' => true,
            'message' => 'Menu Creation...'
        ], 200));

        wp_die();
    }

    public function createHeaderMenu($headerMenuData)
    {

        if (empty($headerMenuData['name']) || empty($headerMenuData['slug'])) return;

        $menuName = sanitize_text_field($headerMenuData['name']);

        $existingMenu = wp_get_nav_menu_object($menuName);

        if (!$existingMenu) {
            // Create the menu
            $menuId = wp_create_nav_menu($menuName);

            // Check if the menu was created successfully
            if (!is_wp_error($menuId)) {

                update_option('super_blank_primary_menu_id', intval($menuId));

                // Set the menu slug
                $menuObject = wp_get_nav_menu_object($menuId);

                if ($menuObject) {

                    $menuObject->slug = sanitize_key($headerMenuData['slug']);

                    $menuArray = (array) $menuObject;

                    $menuArray['menu-name'] = $menuName;

                    wp_update_nav_menu_object($menuId, $menuArray);
                }
            }
        }
    }

    public function createFooterMenu($footerMenuData)
    {

        if (empty($footerMenuData['name']) || empty($footerMenuData['slug'])) return;

        $menuName = sanitize_text_field($footerMenuData['name']);

        $existingMenu = wp_get_nav_menu_object($menuName);

        if (!$existingMenu) {

            // Create the menu
            $menuId = wp_create_nav_menu($menuName);

            // Check if the menu was created successfully
            if (!is_wp_error($menuId)) {

                update_option('super_blank_footer_menu_id', intval($menuId));

                // Set the menu slug
                $menuObject = wp_get_nav_menu_object($menuId);

                if ($menuObject) {

                    $menuObject->slug = sanitize_key($footerMenuData['slug']);

                    $menuArray = (array) $menuObject;

                    $menuArray['menu-name'] = $menuName;

                    wp_update_nav_menu_object($menuId, $menuArray);
                }
            }
        }
    }
}
