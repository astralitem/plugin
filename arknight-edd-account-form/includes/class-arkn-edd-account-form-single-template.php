<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Arknight_EDD_Account_Form_Single_Template {
	const TEMPLATE_FILE = 'templates/single-arkn-endfield-account.php';

	/**
	 * Register single template hooks.
	 */
	public function hooks() {
		add_filter( 'template_include', array( $this, 'load_single_download_template' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * @param string $template Current selected template.
	 * @return string
	 */
	public function load_single_download_template( $template ) {
		if ( ! $this->is_target_single_download() ) {
			return $template;
		}

		$custom_template = ARKN_EDD_FORM_DIR . self::TEMPLATE_FILE;
		if ( file_exists( $custom_template ) ) {
			return $custom_template;
		}

		return $template;
	}

	/**
	 * Register/enqueue assets for the single product template.
	 */
	public function enqueue_assets() {
		wp_register_style( 'arkn-account-single-style', ARKN_EDD_FORM_URL . 'assets/css/single-account.css', array(), ARKN_EDD_FORM_VERSION );
		wp_register_script( 'arkn-account-single-script', ARKN_EDD_FORM_URL . 'assets/js/single-account.js', array(), ARKN_EDD_FORM_VERSION, true );

		if ( $this->is_target_single_download() ) {
			wp_enqueue_style( 'arkn-account-single-style' );
			wp_enqueue_script( 'arkn-account-single-script' );
		}
	}

	/**
	 * @return bool
	 */
	private function is_target_single_download() {
		if ( ! is_singular( 'download' ) ) {
			return false;
		}

		if ( ! taxonomy_exists( Arknight_EDD_Account_Form_Frontend::DOWNLOAD_CATEGORY_TAXONOMY ) ) {
			return false;
		}

		return has_term(
			Arknight_EDD_Account_Form_Frontend::DOWNLOAD_CATEGORY_SLUG,
			Arknight_EDD_Account_Form_Frontend::DOWNLOAD_CATEGORY_TAXONOMY,
			get_queried_object_id()
		);
	}
}
