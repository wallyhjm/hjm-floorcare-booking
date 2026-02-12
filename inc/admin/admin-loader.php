<?php
if ( ! defined('ABSPATH') ) exit;

// Only load admin code in admin
if ( ! is_admin() ) {
    return;
}

// Capacity management
require_once HJM_FLOORCARE_PATH . 'inc/admin/capacity/capacity-page.php';
