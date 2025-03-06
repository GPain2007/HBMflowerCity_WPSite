<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('superBlankCustomAdminMenu')) {
    /**
     * Add admin menu.
     * 
     * @return void
     */
    function superBlankCustomAdminMenu()
    {

        $admin_icon = SUPER_BLANK_PLUGIN_URL . 'assets/images/admin-icon.svg';

        add_menu_page(
            'Super Blank',
            'Super Blank',
            'manage_options',
            'super-blank-page',
            'superBlankPageContent',
            esc_url($admin_icon)
        );
    }
}

add_action('admin_menu', 'superBlankCustomAdminMenu');

if (!function_exists('superBlankPageContent')) {
    /**
     * Admin page content.
     * 
     * @return void
     */
    function superBlankPageContent()
    {
?>
        <div class="wrap super-blank-admin-page">

            <div class="super-blank-wrap">

                <span class="super-blank-heading"><?php esc_html_e('Super Blank will erase your website and...', 'super-blank'); ?></span>

                <p class="super-blank-description">
                    <?php esc_html_e('Add pages, install theme, configure your design, and more.', 'super-blank'); ?>
                </p>

                <div id="status-message" class="super-blank-warning">

                    <input type="checkbox" id="sb-import-confirmation-checkbox" />

                    <?php esc_html_e('I understand this will delete and replace my entire website!', 'super-blank'); ?>
                </div>

                <!-- Tools -->
                <div class="super-blank-tools-area">                    

                    <div class="super-blank-button">
                        <a href="#" class="button" id="super-blank-install"><?php esc_html_e("Let's Do This", 'super-blank'); ?></a>
                    </div>
                </div>
            </div>
        </div>
<?php
    }
}

if (!function_exists('superBlankAddSettingsLink')) {
    /**
     * Add settings link.
     * 
     * @return void
     */

    function superBlankAddSettingsLink($links)
    {

        $settings_link = '<a href="' . admin_url('admin.php?page=super-blank-page') . '">' . __('Start Here', 'super-blank') . '</a>';

        array_unshift($links, $settings_link);

        return $links;
    }
}

add_filter('plugin_action_links_super-blank/super-blank.php', 'superBlankAddSettingsLink');
