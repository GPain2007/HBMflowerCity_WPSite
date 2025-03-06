<?php

if (!defined('ABSPATH')) {
    exit;
}

use SuperBlank\Elementor_Sections;

// Init Elementor sections
add_action('plugins_loaded', function () {

    new Elementor_Sections();
});
