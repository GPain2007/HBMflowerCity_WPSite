<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('superBlankCustomFrontendStyle')) {
    /**
     * Enqueue frontend scripts.
     * 
     * @return void
     */
    function superBlankCustomFrontendStyle() {

        wp_enqueue_style('super-blank-frontend-css', SUPER_BLANK_PLUGIN_URL . 'assets/css/frontend-styles.css', [], SUPER_BLANK_PLUGIN_VERSION, 'all');
    }
}

add_action('wp_enqueue_scripts', 'superBlankCustomFrontendStyle');

if (!function_exists('superBlankCustomAdminStyle')) {
    /**
     * Enqueue admin scripts.
     * 
     * @return void
     */
    function superBlankCustomAdminStyle() {

        wp_enqueue_style('super-blank-admin-css', SUPER_BLANK_PLUGIN_URL . 'assets/css/admin-styles.css', [], SUPER_BLANK_PLUGIN_VERSION, 'all');        

        wp_enqueue_script('super-blank-admin-js', SUPER_BLANK_PLUGIN_URL . 'assets/js/scripts.js', [], SUPER_BLANK_PLUGIN_VERSION, true);

        // Localize the script with new data
		wp_localize_script(
			'super-blank-admin-js',
			'superBlankLocalizer',
			[
				'plugin_version' => SUPER_BLANK_PLUGIN_VERSION,
				'nonce' => wp_create_nonce('install_super_blank'),
				'ajax_url' => admin_url('admin-ajax.php'),
				'site_url' => home_url(),
				'productionMode' => SUPER_BLANK_PRODUCTION,
                'headerMenuData' => [
                    'name' => 'Primary',
                    'slug' => 'primary',
                    'locations' => [
                        'primary',
                        'mobile_menu',
                    ]
                ],
                'footerMenuData' => [
                    'name' => 'Footer Menu',
                    'slug' => 'footer-menu',
                    'locations' => [
                        'footer_menu'
                    ]
                ],
                'translation' => [
                    'starting_fresh_site' => __('Starting fresh site...', 'super-blank'),
                    'deep_cleanup' => __('Deep cleanup...', 'super-blank'),
                    'removing_extra_tables' => __('Removing extra tables...', 'super-blank'),
                    'getting_design_ready' => __('Getting design ready...', 'super-blank'),
                    'activating_astra_theme' => __('Activating Astra theme...', 'super-blank'),
                    'installing_elementor_plugin' => __('Installing Elementor plugin...', 'super-blank'),
                    'installing_wpforms_plugin' => __('Installing WP Forms plugin...', 'super-blank'),
                    'creating_menu' => __('Creating menu...', 'super-blank'),
                    'creating_pages' => __('Creating pages...', 'super-blank'),
                    'website_settings' => __('Website settings...', 'super-blank'),

                    'please_confirm_import' => __('Please, confirm that you understand this action will delete and replace your entire website.', 'super-blank'),
                    'are_you_sure_import_title' => __('Are you sure you want to proceed with the import?', 'super-blank'),

                    'please_refresh_page' => __('Please refresh this page and try again. If you keep getting this error, go to tyler.com and click Feedback on the left to get assistance from us. We reply within 24 hours.', 'super-blank'),

                    'all_done_title' => __('All done! Your new website is ready.', 'super-blank'),

                    'content_imported_successfully' => __('Content Imported successfully!', 'super-blank'),

                    'view_website' => __('View website', 'super-blank'),
                    'import_done' => __('Done!', 'super-blank'),
                    'import_failed' => __('Failed', 'super-blank'),
                    'installing_progress' => __('Installing', 'super-blank'),

                ]
            ]
		);
    }
}

add_action('admin_enqueue_scripts', 'superBlankCustomAdminStyle');