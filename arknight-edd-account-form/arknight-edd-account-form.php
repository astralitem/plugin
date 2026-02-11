<?php
/**
 * Plugin Name: Arknight EDD Account Form
 * Description: فرم حرفه‌ای ثبت آگهی اکانت Arknight و تبدیل داده‌ها به متای محصول Easy Digital Downloads.
 * Version: 1.1.1
 * Author: Arknnight Dev
 * Text Domain: arknight-edd-account-form
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ARKN_EDD_FORM_VERSION', '1.1.1' );
define( 'ARKN_EDD_FORM_FILE', __FILE__ );
define( 'ARKN_EDD_FORM_DIR', plugin_dir_path( __FILE__ ) );
define( 'ARKN_EDD_FORM_URL', plugin_dir_url( __FILE__ ) );
define( 'ARKN_EDD_IMG_SERVER_PATH', '/domains/gamebani.ir/public_html/wp-content/plugins/arknight-edd-account-form/assets/img' );
define( 'ARKN_EDD_IMG_BASE_URL', trailingslashit( ARKN_EDD_FORM_URL . 'assets/img' ) );

require_once ARKN_EDD_FORM_DIR . 'includes/class-arkn-edd-account-form-plugin.php';
require_once ARKN_EDD_FORM_DIR . 'includes/class-arkn-edd-account-form-admin.php';
require_once ARKN_EDD_FORM_DIR . 'includes/class-arkn-edd-account-form-frontend.php';
require_once ARKN_EDD_FORM_DIR . 'includes/class-arkn-edd-account-form-product-cards.php';
require_once ARKN_EDD_FORM_DIR . 'includes/class-arkn-edd-account-form-single-template.php';
Arknight_EDD_Account_Form_Plugin::instance();
