<?php
/**
 * Podo Theme — точка входу.
 */

defined('ABSPATH') || exit;

define('PODO_VERSION', wp_get_theme()->get('Version'));

require_once get_template_directory() . '/inc/setup.php';
require_once get_template_directory() . '/inc/helpers.php';
require_once get_template_directory() . '/inc/enqueue.php';
require_once get_template_directory() . '/inc/cpt.php';
require_once get_template_directory() . '/inc/acf-options.php';
require_once get_template_directory() . '/inc/booking.php';
