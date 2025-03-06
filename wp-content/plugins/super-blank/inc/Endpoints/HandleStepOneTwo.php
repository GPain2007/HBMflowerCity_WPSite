<?php

namespace SuperBlank\Endpoints;

if (!defined('ABSPATH')) {
    exit;
}

use WP_REST_Response;
use WP_Error;

class HandleStepOneTwo
{

    public function __construct()
    {

        add_action('wp_ajax_super_blank_step1_2', [$this, 'handle_step']);
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

        $this->deleteOptions();

        $this->deletePostsData();

        // Success
        echo wp_json_encode(new WP_REST_Response([
            'success' => true,
            'message' => 'Deep cleanup...'
        ], 200));

        wp_die();
    }

    public function deleteOptions()
    {

        $options = [
            'site_logo',
            'site_icon'
        ];

        superBlankDeleteExactOptions($options);

        $patterns = [
            'astra%',
            '%astra',
            '%astra%',
            'elementor%',
            '%elementor',
            '%elementor%',
            'wpforms%',
            '%wpforms',
            '%wpforms%',
            'woocommerce%',
            '%woocommerce',
            '%woocommerce%',
            'jetpack%',
            '%jetpack',
            '%jetpack%',
        ];

        superBlankDeleteOptionsByPattern($patterns);
    }

    public function deletePostsData()
    {

        // Delete attachments
        superBlankCleanAttachmentsWithFiles();

        // Delete posts
        superBlankCleanPosts();

        // Delete specific posts
        superBlankCleanSpecificPosts();

        // Set permalinks
        superBlankSetPermalinkStructure('/%postname%/');

        // Delete all categories and tags
        superBlankCleanTerms();

        // Delete all comments
        superBlankCleanComments();

        // Delete Termmeta trash
        superBlankDeleteLostRecordsTermmeta();

        // Delete Postmeta trash
        superBlankDeleteLostRecordsPostmeta();
    }
}
