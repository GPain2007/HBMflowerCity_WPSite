<?php

namespace SuperBlank;

if (!defined('ABSPATH')) {
    exit;
}

require_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');

use WP_Upgrader_Skin;

class Quiet_Skin extends WP_Upgrader_Skin
{

    public function feedback($string, ...$args) {}
    public function header() {}
    public function footer() {}
}
